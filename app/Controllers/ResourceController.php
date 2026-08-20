<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\ModuleRegistry;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\ResourcePolicy;
use App\Repositories\BaseRepository;
use App\Services\AuditService;
use App\Services\CalculationService;
use App\Services\FileUploadService;
use App\Services\NotificationService;

final class ResourceController extends Controller
{
    private AuditService $audit;
    private CalculationService $calc;

    public function __construct()
    {
        $this->audit = new AuditService();
        $this->calc = new CalculationService();
    }

    public function index(string $module): void
    {
        [$key, $config] = $this->authorize($module);
        $repo = new BaseRepository($config['table']);
        [$where, $params] = $this->scope($key);
        [$filterWhere, $filterParams] = $this->filters($key, $config);
        if ($filterWhere !== '') {
            $where = '(' . $where . ') AND (' . $filterWhere . ')';
            $params = array_merge($params, $filterParams);
        }
        $rows = $repo->all($where, $params);
        $relations = $this->relations($config);
        $cards = match ($key) {
            'worker-projects' => $this->projectCards(),
            'attendance' => $this->attendanceCards(),
            default => [],
        };
        $this->render('resources/index', compact('key', 'config', 'rows', 'relations', 'cards'));
    }

    public function storeIndex(): void
    {
        Auth::requireRole(['admin', 'foreman']);
        $user = Auth::user();
        $today = date('Y-m-d');
        $fromDate = trim((string) ($_GET['from_date'] ?? $today));
        $toDate = trim((string) ($_GET['to_date'] ?? $today));
        $q = trim((string) ($_GET['q'] ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $fromDate = $today;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $toDate = $today;
        }
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $clauses = ['p.deleted_at IS NULL'];
        $params = [
            'store_to_date' => $toDate,
            'store_activity_to_date' => $toDate,
            'store_activity_from_date' => $fromDate,
        ];

        if (($user['role'] ?? '') === 'foreman') {
            $clauses[] = 'p.id IN (SELECT project_id FROM worker_projects WHERE worker_id = :store_worker AND status = "active")';
            $params['store_worker'] = $user['worker_id'];
        }
        if ($q !== '') {
            $clauses[] = '(s.material LIKE :store_q_material OR p.name_en LIKE :store_q_project OR p.name_bn LIKE :store_q_project_bn)';
            $params['store_q_material'] = '%' . $q . '%';
            $params['store_q_project'] = '%' . $q . '%';
            $params['store_q_project_bn'] = '%' . $q . '%';
        }

        // The table is a stock snapshot as of the selected To Date.
        // From/To Date controls which materials had activity in the selected period.
        $clauses[] = '(EXISTS (
            SELECT 1 FROM material_purchases fp
            WHERE fp.project_id = s.project_id
              AND fp.material = s.material
              AND COALESCE(fp.unit, "") = s.unit
              AND fp.purchase_date BETWEEN :store_activity_from_date AND :store_activity_to_date
              AND fp.deleted_at IS NULL
        ) OR EXISTS (
            SELECT 1 FROM material_usages fu
            WHERE fu.project_id = s.project_id
              AND fu.material = s.material
              AND COALESCE(fu.unit, "") = s.unit
              AND fu.use_date BETWEEN :store_activity_from_date_usage AND :store_activity_to_date_usage
              AND fu.deleted_at IS NULL
        ))';
        $params['store_activity_from_date_usage'] = $fromDate;
        $params['store_activity_to_date_usage'] = $toDate;

        $sql = 'SELECT s.project_id, p.name_en AS project_name, p.name_bn AS project_name_bn,
                       s.material, s.unit, s.received_quantity,
                       COALESCE(u.used_quantity, 0) AS used_quantity,
                       GREATEST(s.received_quantity - COALESCE(u.used_quantity, 0), 0) AS remaining_quantity
                FROM (
                    SELECT project_id, material, COALESCE(unit, "") AS unit, SUM(quantity) AS received_quantity
                    FROM material_purchases
                    WHERE purchase_date <= :store_to_date AND deleted_at IS NULL
                    GROUP BY project_id, material, COALESCE(unit, "")
                ) s
                INNER JOIN projects p ON p.id = s.project_id
                LEFT JOIN (
                    SELECT project_id, material, COALESCE(unit, "") AS unit, SUM(quantity) AS used_quantity
                    FROM material_usages
                    WHERE use_date <= :store_to_date_usage AND deleted_at IS NULL
                    GROUP BY project_id, material, COALESCE(unit, "")
                ) u ON u.project_id = s.project_id AND u.material = s.material AND u.unit = s.unit
                WHERE ' . implode(' AND ', $clauses) . '
                ORDER BY p.name_en ASC, s.material ASC';
        $params['store_to_date_usage'] = $toDate;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $stocks = $stmt->fetchAll();

        $this->render('store/index', compact('stocks', 'q', 'fromDate', 'toDate'));
    }

    public function storeUseForm(): void
    {
        Auth::requireRole(['admin', 'foreman']);
        $stocks = $this->availableStoreStocks();
        $this->render('store/use', compact('stocks'));
    }

    public function storeUse(): void
    {
        Auth::requireRole(['admin', 'foreman']);
        $this->guardCsrf();

        $stockKey = (string) ($_POST['stock_key'] ?? '');
        $quantity = (float) ($_POST['quantity'] ?? 0);
        $useDate = trim((string) ($_POST['use_date'] ?? date('Y-m-d')));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($quantity <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $useDate)) {
            flash('error', __('store.invalid_usage'));
            redirect('/' . Auth::user()['role'] . '/store/use');
        }

        $decoded = base64_decode($stockKey, true);
        $stock = $decoded !== false ? json_decode($decoded, true) : null;
        if (!is_array($stock) || !isset($stock['project_id'], $stock['material'])) {
            flash('error', __('store.invalid_usage'));
            redirect('/' . Auth::user()['role'] . '/store/use');
        }

        $projectId = (int) $stock['project_id'];
        $material = trim((string) $stock['material']);
        $unit = trim((string) ($stock['unit'] ?? ''));

        $user = Auth::user();
        if (($user['role'] ?? '') === 'foreman') {
            $assignment = (new BaseRepository('worker_projects'))->firstWhere(
                'project_id = :project AND worker_id = :worker AND status = :status',
                ['project' => $projectId, 'worker' => $user['worker_id'], 'status' => 'active']
            );
            if (!$assignment) {
                (new ErrorController())->forbidden();
                return;
            }
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $receivedStmt = $db->prepare(
                'SELECT COALESCE(SUM(quantity), 0)
                 FROM material_purchases
                 WHERE project_id = :project AND material = :material
                   AND COALESCE(unit, "") = :unit AND deleted_at IS NULL'
            );
            $receivedStmt->execute([
                'project' => $projectId,
                'material' => $material,
                'unit' => $unit,
            ]);
            $received = (float) $receivedStmt->fetchColumn();

            $usedStmt = $db->prepare(
                'SELECT COALESCE(SUM(quantity), 0)
                 FROM material_usages
                 WHERE project_id = :project AND material = :material
                   AND COALESCE(unit, "") = :unit AND deleted_at IS NULL'
            );
            $usedStmt->execute([
                'project' => $projectId,
                'material' => $material,
                'unit' => $unit,
            ]);
            $used = (float) $usedStmt->fetchColumn();

            $remaining = $received - $used;
            if ($quantity > $remaining + 0.000001) {
                $db->rollBack();
                flash('error', __('store.insufficient_stock'));
                redirect('/' . $user['role'] . '/store/use');
            }

            $insert = $db->prepare(
                'INSERT INTO material_usages
                    (project_id, material, unit, quantity, use_date, notes, used_by)
                 VALUES (:project, :material, :unit, :quantity, :use_date, :notes, :used_by)'
            );
            $insert->execute([
                'project' => $projectId,
                'material' => $material,
                'unit' => $unit !== '' ? $unit : null,
                'quantity' => $quantity,
                'use_date' => $useDate,
                'notes' => $notes !== '' ? $notes : null,
                'used_by' => $user['id'],
            ]);
            $id = (int) $db->lastInsertId();
            $db->commit();

            $this->audit->log('CREATE', 'material-usages', $id, [], [
                'project_id' => $projectId,
                'material' => $material,
                'unit' => $unit,
                'quantity' => $quantity,
                'use_date' => $useDate,
                'notes' => $notes,
            ]);
            flash('success', __('store.usage_saved'));
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }

        redirect('/' . $user['role'] . '/store');
    }

    private function availableStoreStocks(): array
    {
        $user = Auth::user();
        $clauses = ['p.deleted_at IS NULL'];
        $params = [];

        if (($user['role'] ?? '') === 'foreman') {
            $clauses[] = 'p.id IN (SELECT project_id FROM worker_projects WHERE worker_id = :stock_worker AND status = "active")';
            $params['stock_worker'] = $user['worker_id'];
        }

        $sql = 'SELECT s.project_id, p.name_en AS project_name, p.name_bn AS project_name_bn,
                       s.material, s.unit, s.received_quantity,
                       COALESCE(u.used_quantity, 0) AS used_quantity,
                       GREATEST(s.received_quantity - COALESCE(u.used_quantity, 0), 0) AS remaining_quantity
                FROM (
                    SELECT project_id, material, COALESCE(unit, "") AS unit, SUM(quantity) AS received_quantity
                    FROM material_purchases
                    WHERE deleted_at IS NULL
                    GROUP BY project_id, material, COALESCE(unit, "")
                ) s
                INNER JOIN projects p ON p.id = s.project_id
                LEFT JOIN (
                    SELECT project_id, material, COALESCE(unit, "") AS unit, SUM(quantity) AS used_quantity
                    FROM material_usages
                    WHERE deleted_at IS NULL
                    GROUP BY project_id, material, COALESCE(unit, "")
                ) u ON u.project_id = s.project_id AND u.material = s.material AND u.unit = s.unit
                WHERE ' . implode(' AND ', $clauses) . '
                  AND GREATEST(s.received_quantity - COALESCE(u.used_quantity, 0), 0) > 0
                ORDER BY p.name_en ASC, s.material ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(string $module): void
    {
        [$key, $config] = $this->authorize($module);
        $this->ensureWriteAllowed($key, 'CREATE');
        $row = [];
        $relations = $this->relations($config);
        $this->render('resources/form', compact('key', 'config', 'row', 'relations'));
    }

    public function store(string $module): void
    {
        [$key, $config] = $this->authorize($module);
        $this->ensureWriteAllowed($key, 'CREATE');
        $this->guardCsrf();
        $repo = new BaseRepository($config['table']);
        $data = $this->payload($key, $config);
        $this->ensurePayloadAccess($key, $data);
        $id = $this->transactionalSave($key, fn () => $repo->create($data), $data);
        $this->audit->log('CREATE', $key, $id, [], $data);
        flash('success', __('messages.saved'));
        redirect('/' . Auth::user()['role'] . '/' . $key);
    }

    public function edit(string $module, string $id): void
    {
        [$key, $config] = $this->authorize($module);
        $this->ensureWriteAllowed($key, 'UPDATE');
        $row = (new BaseRepository($config['table']))->find((int) $id);
        if (!$row) {
            redirect('/' . Auth::user()['role'] . '/' . $key);
        }
        $this->ensureOwnership($key, $row);
        $relations = $this->relations($config);
        $this->render('resources/form', compact('key', 'config', 'row', 'relations'));
    }

    public function update(string $module, string $id): void
    {
        [$key, $config] = $this->authorize($module);
        $this->ensureWriteAllowed($key, 'UPDATE');
        $this->guardCsrf();
        $repo = new BaseRepository($config['table']);
        $old = $repo->find((int) $id);
        if (!$old) {
            redirect('/' . Auth::user()['role'] . '/' . $key);
        }
        $this->ensureOwnership($key, $old);
        $data = $this->payload($key, $config, $old);
        $this->ensurePayloadAccess($key, $data);
        $this->transactionalSave($key, function () use ($repo, $id, $data): int {
            $repo->update((int) $id, $data);
            return (int) $id;
        }, $data);
        $this->audit->log('UPDATE', $key, (int) $id, $old, $data);
        flash('success', __('messages.saved'));
        redirect('/' . Auth::user()['role'] . '/' . $key);
    }

    public function delete(string $module, string $id): void
    {
        [$key, $config] = $this->authorize($module);
        $this->ensureWriteAllowed($key, 'DELETE');
        $this->guardCsrf();
        $repo = new BaseRepository($config['table']);
        $row = $repo->find((int) $id);
        if (!$row) {
            redirect('/' . Auth::user()['role'] . '/' . $key);
        }
        $this->ensureOwnership($key, $row);

        $voidStatus = $this->voidStatus($config);
        if (($config['financial'] ?? false) && array_key_exists('status', $row) && $voidStatus !== null) {
            $repo->update((int) $id, ['status' => $voidStatus]);
            $action = strtoupper($voidStatus);
        } else {
            $repo->softDelete((int) $id);
            $action = 'DELETE';
        }
        $this->audit->log($action, $key, (int) $id, $row);
        if ($key === 'equipment-assignments') {
            $this->calc->refreshEquipment((int) $row['equipment_id']);
        }
        flash('success', __('messages.deleted'));
        redirect('/' . Auth::user()['role'] . '/' . $key);
    }

    public function projectAttachment(string $id, string $mode): void
    {
        if (!in_array($mode, ['view', 'download'], true)) {
            (new ErrorController())->notFound();
            return;
        }

        [$key, $config] = $this->authorize('projects');
        $project = (new BaseRepository($config['table']))->find((int) $id);
        if (!$project || empty($project['project_attachment_path'])) {
            (new ErrorController())->notFound();
            return;
        }

        $this->ensureOwnership($key, $project);
        $relativePath = ltrim((string) $project['project_attachment_path'], '/');
        if (!str_starts_with($relativePath, 'storage/project_attachments/')) {
            (new ErrorController())->forbidden();
            return;
        }

        $path = base_path($relativePath);
        if (!is_file($path)) {
            (new ErrorController())->notFound();
            return;
        }

        $downloadName = $this->safeDownloadName((string) ($project['project_attachment_name'] ?? ''), $path);
        header('Content-Type: ' . (mime_content_type($path) ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($mode === 'download' ? 'attachment' : 'inline') . '; filename="' . $downloadName . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function authorize(string $module): array
    {
        $config = ModuleRegistry::get($module);
        if (!$config) {
            http_response_code(404);
            (new ErrorController())->notFound();
            exit;
        }
        Auth::requireRole($config['roles']);
        return [$module, $config];
    }

    private function guardCsrf(): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', __('validation.csrf'));
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    private function payload(string $key, array $config, ?array $existing = null): array
    {
        $data = [];
        foreach ($config['fields'] as $field => $meta) {
            if ($meta['type'] === 'file') {
                $directory = $this->uploadDirectory($field, $key);
                $service = new FileUploadService();
                $uploaded = $key === 'homepage-media'
                    ? $service->media($field, $directory, $existing[$field] ?? null)
                    : $service->image($field, $directory, $existing[$field] ?? null);
                if ($uploaded) {
                    $data[$field] = $uploaded;
                }
                continue;
            }
            if ($meta['type'] === 'attachment') {
                $service = new FileUploadService();
                $uploaded = $service->attachment($field, $this->uploadDirectory($field, $key), $existing[$field] ?? null);
                if ($uploaded) {
                    $data[$field] = $uploaded;
                    if ($key === 'projects') {
                        $data['project_attachment_name'] = $this->uploadedOriginalName($field);
                    }
                }
                continue;
            }
            if ($field === 'password') {
                continue;
            }
            $value = $_POST[$field] ?? null;
            if (($meta['type'] ?? '') === 'money' || ($meta['type'] ?? '') === 'number') {
                $value = $value === '' || $value === null ? 0 : $value;
            }
            if (in_array(($meta['type'] ?? ''), ['relation', 'date'], true) && $value === '') {
                $value = null;
            }
            if (($meta['type'] ?? '') === 'datetime-local' && is_string($value)) {
                $value = $value === '' ? null : str_replace('T', ' ', $value);
            }
            $data[$field] = is_string($value) ? trim($value) : $value;
        }

        $user = Auth::user();
        if ($key === 'workers' && empty($data['id_number'])) {
            $data['id_number'] = $this->generateWorkerId((string) ($data['joining_date'] ?? date('Y-m-d')));
        }
        if ($key === 'attendance') {
            $worker = (new BaseRepository('workers'))->find((int) $data['worker_id']);
            $salary = $this->calc->attendanceSalary((float) ($worker['daily_salary'] ?? 0), (string) $data['status'], (float) ($data['overtime_hours'] ?? 0));
            $data = array_merge($data, $salary, ['entered_by' => $user['id']]);
        }
        if (in_array($key, ['materials', 'food'], true)) {
            if ($key === 'materials') {
                $data['total_amount'] = round(((float) $data['quantity'] * (float) $data['unit_price']) + (float) ($data['carrying_cost'] ?? 0), 2);
            } else {
                $data['quantity'] = 0;
                $data['unit_price'] = 0;
                $data['total_amount'] = round((float) ($data['total_cost'] ?? 0) + (float) ($data['carrying_cost'] ?? 0), 2);
            }
        }
        if ($key === 'vehicles') {
            $data['total_amount'] = round((float) $data['rental_amount'] + (float) $data['fuel_amount'] + (float) $data['other_cost'], 2);
        }
        if ($key === 'payments') {
            $project = (new BaseRepository('projects'))->find((int) $data['project_id']);
            $data['contract_amount'] = (float) ($project['total_amount'] ?? 0);
            $data['receivable_amount'] = max(0, $data['contract_amount'] - (float) $data['received_amount']);
        }
        if ($key === 'leave' && $user['role'] !== 'admin') {
            $data['worker_id'] = $user['worker_id'];
            $data['status'] = 'pending';
        }
        if ($key === 'expenses' && $user['role'] === 'foreman') {
            $data['status'] = 'submitted';
        }
        if ($key === 'expenses' && $user['role'] === 'admin') {
            $data['status'] = 'approved';
        }
        if ($key === 'advances' && $user['role'] === 'foreman') {
            $data['status'] = 'approved';
        }
        if ($key === 'users' && !empty($_POST['password'])) {
            $data['password_hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
        }
        if (!$existing && !array_key_exists('created_by', $data) && ($config['creator'] ?? true)) {
            $data['created_by'] = $user['id'];
        }

        return $data;
    }

    private function transactionalSave(string $key, callable $callback, array $data): int
    {
        $financial = in_array($key, ['attendance', 'advances', 'withdrawals', 'bonuses', 'deductions', 'expenses', 'materials', 'food', 'vehicles', 'payments', 'equipment-assignments', 'admin-expenses'], true);
        $db = Database::connection();
        if ($financial) {
            $db->beginTransaction();
        }
        try {
            $id = (int) $callback();
            $this->afterSave($key, $id, $data);
            if ($financial) {
                $db->commit();
            }
            return $id;
        } catch (\Throwable $exception) {
            if ($financial && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function afterSave(string $key, int $id, array $data): void
    {
        if ($key === 'workers') {
            $this->syncWorkerUser($id, $data);
            $this->syncIdCard($id, $data);
        }
        if ($key === 'equipment') {
            $this->calc->refreshEquipment($id);
        }
        if ($key === 'attendance') {
            $salaryRepo = new BaseRepository('salary_transactions');
            $salaryData = [
                'worker_id' => $data['worker_id'],
                'project_id' => $data['project_id'],
                'attendance_id' => $id,
                'transaction_date' => $data['attendance_date'],
                'type' => 'salary',
                'amount' => $data['total_salary'],
                'overtime_amount' => $data['overtime_amount'],
                'description' => 'Attendance salary',
                'created_by' => Auth::user()['id'],
            ];
            $existing = $salaryRepo->firstWhere('attendance_id = :attendance', ['attendance' => $id]);
            if ($existing) {
                $salaryRepo->update((int) $existing['id'], $salaryData);
            } else {
                $salaryRepo->create($salaryData);
            }
            (new NotificationService())->create(null, 'notifications.attendance_title', 'notifications.attendance_body', 'success', '/admin/attendance');
        }
        if ($key === 'equipment-assignments') {
            $this->calc->refreshEquipment((int) $data['equipment_id']);
            (new BaseRepository('equipment_movements'))->create([
                'equipment_id' => $data['equipment_id'],
                'project_id' => $data['project_id'],
                'movement_type' => $data['status'] ?? 'assigned',
                'quantity' => $data['quantity'],
                'movement_date' => date('Y-m-d'),
                'notes' => $data['notes'] ?? '',
                'created_by' => Auth::user()['id'],
            ]);
        }
        if ($key === 'payments') {
            (new NotificationService())->create(null, 'notifications.payment_title', 'notifications.payment_body', 'success', '/admin/payments');
        }
    }

    private function syncWorkerUser(int $workerId, array $data): void
    {
        $repo = new BaseRepository('users');
        $existing = $repo->firstWhere('worker_id = :worker', ['worker' => $workerId]);
        $payload = [
            'name' => $data['full_name'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'role' => ($data['role'] ?? '') === 'foreman' ? 'foreman' : 'labor',
            'status' => ($data['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
            'worker_id' => $workerId,
        ];
        if (!empty($_POST['password'])) {
            $payload['password_hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
        }
        if ($existing) {
            $repo->update((int) $existing['id'], $payload);
            return;
        }
        $payload['email'] = null;
        $payload['password_hash'] = $payload['password_hash'] ?? password_hash('ChangeMe123!', PASSWORD_DEFAULT);
        $payload['language'] = 'bn';
        $payload['theme'] = 'light';
        $repo->create($payload);
    }

    private function syncIdCard(int $workerId, array $data): void
    {
        $repo = new BaseRepository('id_cards');
        $worker = (new BaseRepository('workers'))->find($workerId) ?: [];
        $existing = $repo->firstWhere('worker_id = :worker', ['worker' => $workerId]);
        $payload = [
            'worker_id' => $workerId,
            'id_number' => $data['id_number'] ?? $worker['id_number'] ?? $this->generateWorkerId((string) ($data['joining_date'] ?? date('Y-m-d'))),
            'designation' => $data['role'] ?? $worker['role'] ?? 'labor',
            'mobile' => $data['mobile'] ?? $worker['mobile'] ?? '',
            'photo_path' => $data['photo_path'] ?? $worker['photo_path'] ?? null,
            'status' => 'active',
            'created_by' => Auth::user()['id'] ?? null,
        ];
        if ($existing) {
            unset($payload['worker_id'], $payload['created_by']);
            $repo->update((int) $existing['id'], $payload);
            return;
        }
        $repo->create($payload);
    }

   private function generateWorkerId(string $joiningDate): string
{
    $timestamp = strtotime($joiningDate) ?: time();
    $date = date('Ymd', $timestamp);

    $repo = new BaseRepository('workers');

    /*
     * Find the highest serial from IDs generated in the new format.
     * Only IDs with exactly 4 digits after the date are considered.
     */
    $stmt = Database::connection()->prepare(
        "SELECT id_number
         FROM workers
         WHERE id_number REGEXP '^NEP[0-9]{8}[0-9]{4}$'
         ORDER BY CAST(RIGHT(id_number, 4) AS UNSIGNED) DESC
         LIMIT 1"
    );

    $stmt->execute();

    $latest = (string) ($stmt->fetchColumn() ?: '');

    $next = preg_match(
        '/^NEP\d{8}(\d{4})$/',
        $latest,
        $match
    )
        ? ((int) $match[1]) + 1
        : 1;

    if ($next > 9999) {
        throw new \RuntimeException(
            'Worker ID serial limit reached. Maximum is 9999.'
        );
    }

    do {
        $serial = str_pad(
            (string) $next,
            4,
            '0',
            STR_PAD_LEFT
        );

        $id = 'NEP' . $date . $serial;

        $next++;
    } while (
        $repo->firstWhere(
            'id_number = :id',
            ['id' => $id]
        )
    );

    return $id;
}

    private function uploadDirectory(string $field, string $module): string
    {
        return match (true) {
            $field === 'cheque_image' => 'cheques',
            $field === 'project_attachment_path' => 'project_attachments',
            str_contains($field, 'invoice') => 'invoices',
            $module === 'homepage-media' || str_starts_with($module, 'homepage-') => 'homepage',
            $module === 'id-cards' => 'idcards',
            default => 'workers',
        };
    }

    private function relations(array $config): array
    {
        $relations = [];
        foreach ($config['fields'] as $field => $meta) {
            if (($meta['type'] ?? '') !== 'relation') {
                continue;
            }
            $source = $meta['source'];
            [$where, $params] = $this->relationScope($source, $field);
            $relations[$field] = [
                'display' => $meta['display'],
                'rows' => (new BaseRepository($source))->all($where, $params, ($meta['display'] ?? 'id') . ' ASC'),
            ];
        }
        return $relations;
    }

    private function scope(string $key): array
    {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            return ['1=1', []];
        }
        if ($key === 'leave') {
            return ['worker_id = :worker', ['worker' => $user['worker_id']]];
        }
        if ($user['role'] === 'foreman' && $key === 'advances') {
            return [
                'worker_id IN (SELECT DISTINCT wp.worker_id FROM worker_projects wp WHERE wp.project_id IN (SELECT project_id FROM worker_projects WHERE worker_id = :foreman AND status = "active") AND wp.status = "active")',
                ['foreman' => $user['worker_id']],
            ];
        }
        if ($user['role'] === 'foreman' && in_array($key, ['projects', 'attendance', 'expenses', 'materials', 'food', 'vehicles'], true)) {
            if ($key === 'projects') {
                return ['id IN (SELECT project_id FROM worker_projects WHERE worker_id = :worker AND status = "active")', ['worker' => $user['worker_id']]];
            }
            return ['project_id IN (SELECT project_id FROM worker_projects WHERE worker_id = :worker AND status = "active")', ['worker' => $user['worker_id']]];
        }
        return ['worker_id = :worker', ['worker' => $user['worker_id']]];
    }

    private function ensureOwnership(string $key, array $row): void
    {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            return;
        }
        if (array_key_exists('worker_id', $row) && (int) $row['worker_id'] === (int) $user['worker_id']) {
            return;
        }
        if (array_key_exists('project_id', $row)) {
            $assignment = (new BaseRepository('worker_projects'))->firstWhere(
                'project_id = :project AND worker_id = :worker AND status = :status',
                ['project' => $row['project_id'], 'worker' => $user['worker_id'], 'status' => 'active']
            );
            if ($assignment) {
                return;
            }
        }
        http_response_code(403);
        (new ErrorController())->forbidden();
        exit;
    }

    private function ensureWriteAllowed(string $key, string $action): void
    {
        if (ResourcePolicy::allows($key, $action)) {
            return;
        }
        http_response_code(403);
        (new ErrorController())->forbidden();
        exit;
    }

    private function ensurePayloadAccess(string $key, array $data): void
    {
        $user = Auth::user();
        if (($user['role'] ?? '') === 'admin') {
            return;
        }
        if ($key === 'advances' && !empty($data['worker_id'])) {
            $targetWorker = (int) $data['worker_id'];
            $ownWorker = (int) ($user['worker_id'] ?? 0);
            if ($targetWorker === $ownWorker && empty($data['project_id'])) {
                return;
            }
            if ($targetWorker !== $ownWorker && !(new BaseRepository('worker_projects'))->firstWhere(
                'worker_id = :target AND status = :target_status AND project_id IN (SELECT project_id FROM worker_projects WHERE worker_id = :foreman AND status = :foreman_status)',
                ['target' => $targetWorker, 'foreman' => $ownWorker, 'target_status' => 'active', 'foreman_status' => 'active']
            )) {
                http_response_code(403);
                (new ErrorController())->forbidden();
                exit;
            }
        }
        if (!empty($data['project_id'])) {
            $assignment = (new BaseRepository('worker_projects'))->firstWhere(
                'project_id = :project AND worker_id = :worker AND status = :status',
                ['project' => $data['project_id'], 'worker' => $user['worker_id'], 'status' => 'active']
            );
            if (!$assignment) {
                http_response_code(403);
                (new ErrorController())->forbidden();
                exit;
            }
        }
        if ($key === 'attendance' && !empty($data['worker_id']) && !empty($data['project_id'])) {
            $workerAssignment = (new BaseRepository('worker_projects'))->firstWhere(
                'project_id = :project AND worker_id = :worker AND status = :status',
                ['project' => $data['project_id'], 'worker' => $data['worker_id'], 'status' => 'active']
            );
            if (!$workerAssignment) {
                http_response_code(403);
                (new ErrorController())->forbidden();
                exit;
            }
        }
    }

    private function relationScope(string $source, string $field): array
    {
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'foreman') {
            return ['1=1', []];
        }
        if ($source === 'projects') {
            return ['id IN (SELECT project_id FROM worker_projects WHERE worker_id = :worker AND status = "active")', ['worker' => $user['worker_id']]];
        }
        if ($source === 'workers' && $field === 'worker_id') {
            return [
                '(id = :foreman_self OR id IN (SELECT worker_id FROM worker_projects WHERE project_id IN (SELECT project_id FROM worker_projects WHERE worker_id = :foreman_projects AND status = "active") AND status = "active"))',
                ['foreman_self' => $user['worker_id'], 'foreman_projects' => $user['worker_id']],
            ];
        }
        return ['1=1', []];
    }

    private function filters(string $key, array $config): array
    {
        $clauses = [];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $searchParam = '%' . $q . '%';
            if ($key === 'workers') {
                $clauses[] = '(full_name LIKE :q_full_name OR full_name_bn LIKE :q_full_name_bn OR mobile LIKE :q_mobile OR id_number LIKE :q_id_number)';
                $params += ['q_full_name' => $searchParam, 'q_full_name_bn' => $searchParam, 'q_mobile' => $searchParam, 'q_id_number' => $searchParam];
            } elseif ($key === 'projects') {
                $clauses[] = '(name_en LIKE :q_name_en OR name_bn LIKE :q_name_bn OR client_mobile LIKE :q_client_mobile)';
                $params += ['q_name_en' => $searchParam, 'q_name_bn' => $searchParam, 'q_client_mobile' => $searchParam];
            } elseif ($key === 'worker-projects') {
                $clauses[] = 'project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_name_en OR name_bn LIKE :q_name_bn OR client_mobile LIKE :q_client_mobile)';
                $params += ['q_name_en' => $searchParam, 'q_name_bn' => $searchParam, 'q_client_mobile' => $searchParam];
            } elseif ($key === 'materials') {
                $clauses[] = '(material LIKE :q_material OR unit LIKE :q_unit OR supplier LIKE :q_supplier OR project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_project_name_en OR name_bn LIKE :q_project_name_bn OR client_mobile LIKE :q_project_client_mobile))';
                $params += [
                    'q_material' => $searchParam,
                    'q_unit' => $searchParam,
                    'q_supplier' => $searchParam,
                    'q_project_name_en' => $searchParam,
                    'q_project_name_bn' => $searchParam,
                    'q_project_client_mobile' => $searchParam,
                ];
            } elseif ($key === 'expenses') {
                $clauses[] = '(description_en LIKE :q_description_en OR description_bn LIKE :q_description_bn OR vendor LIKE :q_vendor OR project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_project_name_en OR name_bn LIKE :q_project_name_bn OR client_mobile LIKE :q_project_client_mobile) OR category_id IN (SELECT id FROM expense_categories WHERE name_en LIKE :q_category_name_en OR name_bn LIKE :q_category_name_bn))';
                $params += [
                    'q_description_en' => $searchParam,
                    'q_description_bn' => $searchParam,
                    'q_vendor' => $searchParam,
                    'q_project_name_en' => $searchParam,
                    'q_project_name_bn' => $searchParam,
                    'q_project_client_mobile' => $searchParam,
                    'q_category_name_en' => $searchParam,
                    'q_category_name_bn' => $searchParam,
                ];
            } elseif ($key === 'food') {
                $clauses[] = '(food_item LIKE :q_food_item OR description LIKE :q_food_description OR project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_project_name_en OR name_bn LIKE :q_project_name_bn OR client_mobile LIKE :q_project_client_mobile))';
                $params += [
                    'q_food_item' => $searchParam,
                    'q_food_description' => $searchParam,
                    'q_project_name_en' => $searchParam,
                    'q_project_name_bn' => $searchParam,
                    'q_project_client_mobile' => $searchParam,
                ];
            } elseif ($key === 'vehicles') {
                $clauses[] = '(vehicle_type LIKE :q_vehicle_type OR driver_name LIKE :q_driver_name OR notes LIKE :q_vehicle_notes OR project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_project_name_en OR name_bn LIKE :q_project_name_bn OR client_mobile LIKE :q_project_client_mobile))';
                $params += [
                    'q_vehicle_type' => $searchParam,
                    'q_driver_name' => $searchParam,
                    'q_vehicle_notes' => $searchParam,
                    'q_project_name_en' => $searchParam,
                    'q_project_name_bn' => $searchParam,
                    'q_project_client_mobile' => $searchParam,
                ];
            } elseif (isset($config['fields']['worker_id'])) {
                $clauses[] = 'worker_id IN (SELECT id FROM workers WHERE full_name LIKE :q_full_name OR full_name_bn LIKE :q_full_name_bn OR mobile LIKE :q_mobile OR id_number LIKE :q_id_number)';
                $params += ['q_full_name' => $searchParam, 'q_full_name_bn' => $searchParam, 'q_mobile' => $searchParam, 'q_id_number' => $searchParam];
            } elseif (isset($config['fields']['project_id'])) {
                $clauses[] = 'project_id IN (SELECT id FROM projects WHERE name_en LIKE :q_name_en OR name_bn LIKE :q_name_bn OR client_mobile LIKE :q_client_mobile)';
                $params += ['q_name_en' => $searchParam, 'q_name_bn' => $searchParam, 'q_client_mobile' => $searchParam];
            }
        }

        $dateField = $this->dateField($config);
        $from = trim((string) ($_GET['date_from'] ?? ''));
        $to = trim((string) ($_GET['date_to'] ?? ''));
        if (in_array($key, ['materials', 'expenses', 'food', 'vehicles', 'attendance', 'salary'], true) && $from === '' && $to === '') {
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

        return [implode(' AND ', $clauses), $params];
    }

    private function dateField(array $config): ?string
    {
        foreach (['attendance_date', 'expense_date', 'purchase_date', 'payment_date', 'transaction_date', 'date', 'start_date', 'created_at'] as $field) {
            if (isset($config['fields'][$field])) {
                return $field;
            }
        }
        return null;
    }

    private function projectCards(): array
    {
        $user = Auth::user();
        $clauses = ['wp.deleted_at IS NULL', 'p.deleted_at IS NULL'];
        $params = [];
        if (($user['role'] ?? '') === 'foreman') {
            $clauses[] = 'p.id IN (SELECT project_id FROM worker_projects WHERE worker_id = :current_worker AND status = "active")';
            $params['current_worker'] = $user['worker_id'];
        } elseif (($user['role'] ?? '') === 'labor') {
            $clauses[] = 'wp.worker_id = :current_worker';
            $params['current_worker'] = $user['worker_id'];
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $clauses[] = '(p.name_en LIKE :q_name_en OR p.name_bn LIKE :q_name_bn OR p.client_mobile LIKE :q_client_mobile)';
            $params['q_name_en'] = '%' . $q . '%';
            $params['q_name_bn'] = '%' . $q . '%';
            $params['q_client_mobile'] = '%' . $q . '%';
        }
        $sql = 'SELECT p.id, p.name_en, p.name_bn, p.client_name, p.client_mobile, p.location, p.work_type_en, p.work_type_bn,
                       p.total_amount, p.description_en, p.description_bn, p.project_attachment_path, p.project_attachment_name,
                       COUNT(DISTINCT wp.worker_id) AS total_labour,
                       COALESCE(SUM(st.amount + st.overtime_amount), 0) AS labour_amount,
                       GROUP_CONCAT(DISTINCT CONCAT(w.full_name, " (", COALESCE(w.id_number, ""), ")") ORDER BY w.full_name SEPARATOR "||") AS worker_list
                FROM worker_projects wp
                INNER JOIN projects p ON p.id = wp.project_id
                INNER JOIN workers w ON w.id = wp.worker_id
                LEFT JOIN salary_transactions st ON st.worker_id = wp.worker_id AND st.project_id = wp.project_id
                WHERE ' . implode(' AND ', $clauses) . '
                GROUP BY p.id
                ORDER BY p.start_date DESC, p.id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function attendanceCards(): array
    {
        $user = Auth::user();
        $from = trim((string) ($_GET['date_from'] ?? '')) ?: date('Y-m-d');
        $to = trim((string) ($_GET['date_to'] ?? '')) ?: $from;
        $clauses = ['a.deleted_at IS NULL', 'p.deleted_at IS NULL', 'a.attendance_date BETWEEN :date_from AND :date_to'];
        $params = ['date_from' => $from, 'date_to' => $to];
        if (($user['role'] ?? '') === 'foreman') {
            $clauses[] = 'a.project_id IN (SELECT project_id FROM worker_projects WHERE worker_id = :current_worker AND status = "active")';
            $params['current_worker'] = $user['worker_id'];
        } elseif (($user['role'] ?? '') === 'labor') {
            $clauses[] = 'a.worker_id = :current_worker';
            $params['current_worker'] = $user['worker_id'];
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $clauses[] = '(w.full_name LIKE :q_full_name OR w.full_name_bn LIKE :q_full_name_bn OR w.mobile LIKE :q_mobile OR w.id_number LIKE :q_id_number)';
            $params['q_full_name'] = '%' . $q . '%';
            $params['q_full_name_bn'] = '%' . $q . '%';
            $params['q_mobile'] = '%' . $q . '%';
            $params['q_id_number'] = '%' . $q . '%';
        }
        $sql = 'SELECT p.id, p.name_en, p.name_bn,
                       COUNT(*) AS total_attendance,
                       COALESCE(SUM(a.total_salary), 0) AS total_salary,
                       GROUP_CONCAT(CONCAT(a.id, "::", w.full_name, " (", COALESCE(w.id_number, ""), ") - ", a.status, " - ", a.attendance_date,
                           CASE
                               WHEN COALESCE((SELECT SUM(av.amount) FROM advances av WHERE av.worker_id = a.worker_id AND av.date = a.attendance_date AND av.deleted_at IS NULL AND av.status = "approved"), 0) > 0
                               THEN CONCAT(" - Advance: ", COALESCE((SELECT SUM(av2.amount) FROM advances av2 WHERE av2.worker_id = a.worker_id AND av2.date = a.attendance_date AND av2.deleted_at IS NULL AND av2.status = "approved"), 0))
                               ELSE ""
                           END) ORDER BY a.attendance_date DESC, w.full_name SEPARATOR "||") AS attendance_list
                FROM attendance a
                INNER JOIN projects p ON p.id = a.project_id
                INNER JOIN workers w ON w.id = a.worker_id
                WHERE ' . implode(' AND ', $clauses) . '
                GROUP BY p.id
                ORDER BY p.name_en ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function voidStatus(array $config): ?string
    {
        $options = $config['fields']['status']['options'] ?? [];
        if (in_array('void', $options, true)) {
            return 'void';
        }
        if (in_array('cancelled', $options, true)) {
            return 'cancelled';
        }
        return null;
    }

    private function uploadedOriginalName(string $field): ?string
    {
        $name = (string) ($_FILES[$field]['name'] ?? '');
        $name = basename($name);
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?: '';
        return $name !== '' ? substr($name, 0, 190) : null;
    }

    private function safeDownloadName(string $name, string $path): string
    {
        $name = trim($name) !== '' ? $name : basename($path);
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($name)) ?: basename($path);
        return str_replace('"', '', $name);
    }
}
