<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\ModuleRegistry;
use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\BaseRepository;
use App\Services\CalculationService;

final class ReportController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['admin']);
        $modules = ModuleRegistry::all();
        $this->render('reports/index', compact('modules'));
    }

    public function show(string $module): void
    {
        Auth::requireRole(['admin']);
        [$config, $rows, $columns, $relations] = $this->reportData($module);
        $summary = $this->summary($module, $rows);
        $this->render('reports/show', compact('module', 'config', 'rows', 'columns', 'relations', 'summary'));
    }

    public function export(string $module, string $type): void
    {
        Auth::requireRole(['admin']);
        [$config, $rows, $columns, $relations] = $this->reportData($module);
        $name = str_replace('-', '_', $module) . '_' . date('Ymd_His');
        $exportRows = $this->exportRows($rows, $columns, $relations);

        if ($type === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $name . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map(static fn (array $column): string => __($column['label']), $columns));
            foreach ($exportRows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
            return;
        }

        if ($type === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $name . '.xls"');
            $this->render('reports/table-export', compact('exportRows', 'columns'), 'layouts/blank');
            return;
        }

        $print = true;
        $summary = $this->summary($module, $rows);
        $this->render('reports/show', compact('module', 'config', 'rows', 'columns', 'relations', 'summary', 'print'), 'layouts/print');
    }

    private function reportData(string $module): array
    {
        $config = ModuleRegistry::get($module);
        if (!$config) {
            (new ErrorController())->notFound();
            exit;
        }
        [$where, $params] = $this->filters($module, $config);
        $rows = (new BaseRepository($config['table']))->all($where, $params);
        $relations = $this->relations($config);
        $columns = $this->columns($module, $config);
        return [$config, $rows, $columns, $relations];
    }

    private function filters(string $module, array $config): array
    {
        $clauses = ['1=1'];
        $params = [];
        $dateField = $this->dateField($config);
        $from = trim((string) ($_GET['date_from'] ?? ''));
        $to = trim((string) ($_GET['date_to'] ?? ''));
        if ($module === 'attendance' && $from === '' && $to === '') {
            $from = $to = date('Y-m-d');
        }
        if ($dateField !== null && $from !== '') {
            $clauses[] = $dateField . ' >= :date_from';
            $params['date_from'] = $from;
        }
        if ($dateField !== null && $to !== '') {
            $clauses[] = $dateField . ' <= :date_to';
            $params['date_to'] = $to;
        }
        if (!empty($_GET['status']) && isset($config['fields']['status'])) {
            $clauses[] = 'status = :status';
            $params['status'] = trim((string) $_GET['status']);
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $searchParam = '%' . $q . '%';
            if ($module === 'workers') {
                $clauses[] = '(full_name LIKE :q_full_name OR full_name_bn LIKE :q_full_name_bn OR mobile LIKE :q_mobile OR id_number LIKE :q_id_number)';
                $params += ['q_full_name' => $searchParam, 'q_full_name_bn' => $searchParam, 'q_mobile' => $searchParam, 'q_id_number' => $searchParam];
            } elseif ($module === 'projects') {
                $clauses[] = '(name_en LIKE :q_name_en OR name_bn LIKE :q_name_bn OR client_mobile LIKE :q_client_mobile)';
                $params += ['q_name_en' => $searchParam, 'q_name_bn' => $searchParam, 'q_client_mobile' => $searchParam];
            } elseif (isset($config['fields']['worker_id'])) {
                $clauses[] = 'worker_id IN (SELECT id FROM workers WHERE full_name LIKE :q_full_name OR full_name_bn LIKE :q_full_name_bn OR mobile LIKE :q_mobile OR id_number LIKE :q_id_number)';
                $params += ['q_full_name' => $searchParam, 'q_full_name_bn' => $searchParam, 'q_mobile' => $searchParam, 'q_id_number' => $searchParam];
            } elseif (isset($config['fields']['project_id'])) {
                $clauses[] = 'project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_name_en OR name_bn LIKE :q_name_bn OR client_mobile LIKE :q_client_mobile)';
                $params += ['q_name_en' => $searchParam, 'q_name_bn' => $searchParam, 'q_client_mobile' => $searchParam];
            }
        }

        return [implode(' AND ', $clauses), $params];
    }

    private function columns(string $module, array $config): array
    {
        $special = [
            'workers' => [
                ['field' => 'photo_path', 'label' => 'fields.photo', 'type' => 'photo'],
                ['field' => 'full_name', 'label' => 'fields.full_name'],
                ['field' => 'id_number', 'label' => 'fields.id_number'],
                ['field' => 'mobile', 'label' => 'fields.mobile'],
                ['field' => 'blood_group', 'label' => 'fields.blood_group'],
                ['field' => 'role', 'label' => 'fields.role'],
                ['field' => 'joining_date', 'label' => 'fields.joining_date'],
                ['field' => 'password_secure', 'label' => 'fields.password', 'type' => 'password'],
            ],
            'projects' => [
                ['field' => 'name_en', 'label' => 'fields.project_name_en'],
                ['field' => 'client_name', 'label' => 'fields.client_name'],
                ['field' => 'client_mobile', 'label' => 'fields.client_mobile'],
                ['field' => 'location', 'label' => 'fields.location'],
                ['field' => 'total_amount', 'label' => 'fields.total_amount'],
                ['field' => 'status', 'label' => 'fields.status'],
            ],
            'worker-projects' => [
                ['field' => 'worker_id', 'label' => 'fields.worker', 'type' => 'relation'],
                ['field' => 'worker_code', 'label' => 'fields.id_number', 'type' => 'worker_code'],
                ['field' => 'project_id', 'label' => 'fields.project', 'type' => 'relation'],
                ['field' => 'start_date', 'label' => 'fields.start_date'],
                ['field' => 'end_date', 'label' => 'fields.end_date'],
                ['field' => 'status', 'label' => 'fields.status'],
            ],
            'attendance' => [
                ['field' => 'project_id', 'label' => 'fields.project', 'type' => 'relation'],
                ['field' => 'worker_id', 'label' => 'fields.worker', 'type' => 'relation'],
                ['field' => 'worker_code', 'label' => 'fields.id_number', 'type' => 'worker_code'],
                ['field' => 'attendance_date', 'label' => 'fields.date'],
                ['field' => 'status', 'label' => 'fields.status'],
                ['field' => 'overtime_hours', 'label' => 'fields.overtime_hours'],
                ['field' => 'total_salary', 'label' => 'dashboard.salary'],
            ],
            'salary' => [
                ['field' => 'worker_id', 'label' => 'fields.worker', 'type' => 'relation'],
                ['field' => 'worker_code', 'label' => 'fields.id_number', 'type' => 'worker_code'],
                ['field' => 'project_id', 'label' => 'fields.project', 'type' => 'relation'],
                ['field' => 'transaction_date', 'label' => 'fields.date'],
                ['field' => 'amount', 'label' => 'fields.amount'],
                ['field' => 'overtime_hours', 'label' => 'fields.overtime_hours', 'type' => 'salary_overtime_hours'],
                ['field' => 'overtime_amount', 'label' => 'dashboard.overtime'],
                ['field' => 'advance', 'label' => 'dashboard.advance', 'type' => 'salary_advance'],
                ['field' => 'salary_total', 'label' => 'fields.total_amount', 'type' => 'salary_total'],
            ],
            'leave' => [
                ['field' => 'worker_photo', 'label' => 'fields.photo', 'type' => 'worker_photo'],
                ['field' => 'worker_id', 'label' => 'fields.worker', 'type' => 'relation'],
                ['field' => 'worker_code', 'label' => 'fields.id_number', 'type' => 'worker_code'],
                ['field' => 'leave_type', 'label' => 'fields.leave_type'],
                ['field' => 'start_date', 'label' => 'fields.start_date'],
                ['field' => 'end_date', 'label' => 'fields.end_date'],
                ['field' => 'status', 'label' => 'fields.status'],
            ],
            'audit-logs' => [
                ['field' => 'user_id', 'label' => 'fields.user', 'type' => 'relation'],
                ['field' => 'action', 'label' => 'fields.action'],
                ['field' => 'module', 'label' => 'fields.module'],
                ['field' => 'ip_address', 'label' => 'fields.ip_address'],
                ['field' => 'user_agent', 'label' => 'fields.user_agent'],
                ['field' => 'created_at', 'label' => 'fields.created_at'],
            ],
            'login-history' => [
                ['field' => 'user_id', 'label' => 'fields.user', 'type' => 'relation'],
                ['field' => 'login_identifier', 'label' => 'fields.login_identifier'],
                ['field' => 'success', 'label' => 'fields.success'],
                ['field' => 'ip_address', 'label' => 'fields.ip_address'],
                ['field' => 'user_agent', 'label' => 'fields.user_agent'],
                ['field' => 'created_at', 'label' => 'fields.created_at'],
            ],
        ];
        if (isset($special[$module])) {
            return $special[$module];
        }

        $columns = [];
        foreach ($config['fields'] as $field => $meta) {
            if ($field === 'id' || $field === 'record_id' || in_array($field, ['created_by', 'entered_by', 'attendance_id'], true)) {
                continue;
            }
            if (str_ends_with($field, '_id') && ($meta['type'] ?? '') !== 'relation') {
                continue;
            }
            $columns[] = ['field' => $field, 'label' => $meta['label'], 'type' => $meta['type'] ?? 'text'];
            if ($field === 'worker_id') {
                $columns[] = ['field' => 'worker_code', 'label' => 'fields.id_number', 'type' => 'worker_code'];
            }
        }
        return $columns;
    }

    private function relations(array $config): array
    {
        $relations = [];
        foreach ($config['fields'] as $field => $meta) {
            if (($meta['type'] ?? '') !== 'relation') {
                continue;
            }
            $relations[$field] = [
                'display' => $meta['display'],
                'rows' => (new BaseRepository($meta['source']))->all('1=1', [], ($meta['display'] ?? 'id') . ' ASC'),
            ];
        }
        if (!isset($relations['user_id'])) {
            $relations['user_id'] = ['display' => 'name', 'rows' => (new BaseRepository('users'))->all('1=1', [], 'name ASC')];
        }
        if (!isset($relations['worker_id'])) {
            $relations['worker_id'] = ['display' => 'full_name', 'rows' => (new BaseRepository('workers'))->all('1=1', [], 'full_name ASC')];
        }
        if (!isset($relations['project_id'])) {
            $relations['project_id'] = ['display' => 'name_en', 'rows' => (new BaseRepository('projects'))->all('1=1', [], 'name_en ASC')];
        }
        return $relations;
    }

    private function exportRows(array $rows, array $columns, array $relations): array
    {
        return array_map(function (array $row) use ($columns, $relations): array {
            return array_map(fn (array $column): string => $this->plainValue($column, $row, $relations), $columns);
        }, $rows);
    }

    private function plainValue(array $column, array $row, array $relations): string
    {
        $field = $column['field'];
        $type = $column['type'] ?? 'text';
        if ($type === 'relation') {
            return $this->relationValue($field, $row[$field] ?? null, $relations);
        }
        if ($type === 'worker_code') {
            $worker = $this->relationRow('worker_id', $row['worker_id'] ?? null, $relations);
            return (string) ($worker['id_number'] ?? '');
        }
        if ($type === 'salary_total') {
            return money($this->salaryTotal($row));
        }
        if ($type === 'salary_advance') {
            return money($this->salaryAdvance($row));
        }
        if ($type === 'salary_overtime_hours') {
            return number_format($this->salaryOvertimeHours($row), 2);
        }
        if ($type === 'password') {
            return __('reports.password_protected');
        }
        if (str_contains($field, 'amount') || str_contains($field, 'salary') || str_contains($field, 'price') || str_contains($field, 'cost')) {
            return money($row[$field] ?? 0);
        }
        return (string) ($row[$field] ?? '');
    }

    private function relationValue(string $field, mixed $id, array $relations): string
    {
        $row = $this->relationRow($field, $id, $relations);
        return (string) ($row[$relations[$field]['display'] ?? 'id'] ?? '');
    }

    private function relationRow(string $field, mixed $id, array $relations): ?array
    {
        foreach (($relations[$field]['rows'] ?? []) as $row) {
            if ((string) $row['id'] === (string) $id) {
                return $row;
            }
        }
        return null;
    }

    private function dateField(array $config): ?string
    {
        foreach (['expense_date', 'attendance_date', 'transaction_date', 'payment_date', 'purchase_date', 'date', 'start_date', 'created_at'] as $field) {
            if (isset($config['fields'][$field])) {
                return $field;
            }
        }
        return null;
    }

    private function summary(string $module, array $rows): array
    {
        if ($module === 'attendance') {
            return [
                'total_overtime_hours' => array_sum(array_map(static fn (array $row): float => (float) ($row['overtime_hours'] ?? 0), $rows)),
                'total_salary' => array_sum(array_map(static fn (array $row): float => (float) ($row['total_salary'] ?? 0), $rows)),
            ];
        }
        if ($module === 'salary') {
            $summary = [
                'total_amount' => 0.0,
                'total_overtime_hours' => 0.0,
                'total_overtime_amount' => 0.0,
                'total_advance' => 0.0,
            ];
            foreach ($rows as $row) {
                $summary['total_amount'] += $this->salaryTotal($row);
                $summary['total_overtime_hours'] += $this->salaryOvertimeHours($row);
                $summary['total_overtime_amount'] += (float) ($row['overtime_amount'] ?? 0);
                $summary['total_advance'] += $this->salaryAdvance($row);
            }
            return $summary;
        }
        return (new CalculationService())->dashboard();
    }

    private function salaryTotal(array $row): float
    {
        return (float) ($row['amount'] ?? 0) + (float) ($row['overtime_amount'] ?? 0) - $this->salaryAdvance($row);
    }

    private function salaryAdvance(array $row): float
    {
        return (new BaseRepository('advances'))->sum(
            'amount',
            'worker_id = :worker AND date = :advance_date AND status = :status AND deleted_at IS NULL',
            [
                'worker' => (int) ($row['worker_id'] ?? 0),
                'advance_date' => (string) ($row['transaction_date'] ?? ''),
                'status' => 'approved',
            ]
        );
    }

    private function salaryOvertimeHours(array $row): float
    {
        if (empty($row['attendance_id'])) {
            return 0.0;
        }
        $attendance = (new BaseRepository('attendance'))->find((int) $row['attendance_id']);
        return (float) ($attendance['overtime_hours'] ?? 0);
    }
}
