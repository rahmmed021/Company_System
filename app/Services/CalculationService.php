<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\BaseRepository;

final class CalculationService
{
    public function attendanceSalary(float $dailySalary, string $status, float $overtimeHours): array
    {
        $base = match ($status) {
            'present', 'holiday' => $dailySalary,
            'half_day' => $dailySalary / 2,
            default => 0.0,
        };
        $overtimeAmount = round($overtimeHours * ($dailySalary / 8), 2);
        return [
            'daily_salary' => round($dailySalary, 2),
            'overtime_amount' => $overtimeAmount,
            'total_salary' => round($base + $overtimeAmount, 2),
        ];
    }

    public function workerBalance(int $workerId): array
    {
        $salary = new BaseRepository('salary_transactions');
        $advances = new BaseRepository('advances');
        $withdrawals = new BaseRepository('withdrawals');
        $bonuses = new BaseRepository('bonuses');
        $deductions = new BaseRepository('deductions');

        $earned = $salary->sum('amount', 'worker_id = :worker AND type = :type', ['worker' => $workerId, 'type' => 'salary']);
        $overtime = $salary->sum('overtime_amount', 'worker_id = :worker', ['worker' => $workerId]);
        $advance = $advances->sum('amount', 'worker_id = :worker AND status = :status', ['worker' => $workerId, 'status' => 'approved']);
        $withdrawn = $withdrawals->sum('amount', 'worker_id = :worker AND status = :status', ['worker' => $workerId, 'status' => 'paid']);
        $bonus = $bonuses->sum('amount', 'worker_id = :worker AND status = :status', ['worker' => $workerId, 'status' => 'approved']);
        $deduction = $deductions->sum('amount', 'worker_id = :worker AND status = :status', ['worker' => $workerId, 'status' => 'active']);

        return [
            'earned' => $earned,
            'overtime' => $overtime,
            'bonus' => $bonus,
            'advance' => $advance,
            'withdrawn' => $withdrawn,
            'deduction' => $deduction,
            'balance' => $earned + $bonus - $advance - $withdrawn - $deduction,
        ];
    }

    public function projectFinancials(int $projectId): array
    {
        $project = (new BaseRepository('projects'))->find($projectId) ?: ['total_amount' => 0];
        $expenses = new BaseRepository('expenses');
        $materials = new BaseRepository('material_purchases');
        $food = new BaseRepository('food_expenses');
        $vehicles = new BaseRepository('vehicle_expenses');
        $payments = new BaseRepository('received_payments');
        $salary = new BaseRepository('salary_transactions');

        $contract = (float) $project['total_amount'];
        $daily = $expenses->sum('amount', 'project_id = :project AND (status IS NULL OR status <> :void)', ['project' => $projectId, 'void' => 'void']);
        $material = $materials->sum('total_amount', 'project_id = :project', ['project' => $projectId]);
        $foodTotal = $food->sum('total_amount', 'project_id = :project', ['project' => $projectId]);
        $vehicle = $vehicles->sum('total_amount', 'project_id = :project', ['project' => $projectId]);
        $salaryTotal = $salary->sum('amount', 'project_id = :project', ['project' => $projectId]);
        $received = $payments->sum('received_amount', 'project_id = :project AND status = :status', ['project' => $projectId, 'status' => 'received']);
        $cost = $daily + $material + $foodTotal + $vehicle + $salaryTotal;

        return [
            'contract' => $contract,
            'received' => $received,
            'receivable' => $contract - $received,
            'daily_expenses' => $daily,
            'material_expenses' => $material,
            'food_expenses' => $foodTotal,
            'vehicle_expenses' => $vehicle,
            'salary' => $salaryTotal,
            'cost' => $cost,
            'balance' => $contract - $cost,
        ];
    }

    public function equipmentAvailability(int $equipmentId): array
    {
        $equipment = (new BaseRepository('equipment'))->find($equipmentId) ?: ['quantity' => 0];
        $assigned = (new BaseRepository('equipment_assignments'))->sum('quantity', 'equipment_id = :equipment AND status = :status', [
            'equipment' => $equipmentId,
            'status' => 'assigned',
        ]);
        $available = max(0, (int) $equipment['quantity'] - (int) $assigned);
        return [
            'assigned' => (int) $assigned,
            'available' => $available,
        ];
    }

    public function equipmentRemainingById(): array
    {
        $sql = 'SELECT e.id, e.quantity,
                       COALESCE(SUM(CASE WHEN ea.status = "assigned" AND ea.deleted_at IS NULL THEN ea.quantity ELSE 0 END), 0) AS assigned_quantity
                FROM equipment e
                LEFT JOIN equipment_assignments ea ON ea.equipment_id = e.id
                WHERE e.deleted_at IS NULL
                GROUP BY e.id, e.quantity';
        $rows = Database::connection()->query($sql)->fetchAll();
        $remaining = [];
        foreach ($rows as $row) {
            $remaining[(int) $row['id']] = max(0, (int) $row['quantity'] - (int) $row['assigned_quantity']);
        }
        return $remaining;
    }

    public function workerTypeCounts(): array
    {
        $stmt = Database::connection()->query(
            "SELECT role, COUNT(*) AS total
             FROM workers
             WHERE status = 'active' AND deleted_at IS NULL
             GROUP BY role
             ORDER BY role ASC"
        );
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(string) $row['role']] = (int) $row['total'];
        }
        return $counts;
    }

    public function refreshEquipment(int $equipmentId): void
    {
        $values = $this->equipmentAvailability($equipmentId);
        (new BaseRepository('equipment'))->update($equipmentId, [
            'assigned_quantity' => $values['assigned'],
            'available_quantity' => $values['available'],
        ]);
    }

    public function dashboard(): array
    {
        $db = Database::connection();
        $queries = [
            'labor' => "SELECT COUNT(*) FROM workers WHERE role = 'labor' AND deleted_at IS NULL",
            'foreman' => "SELECT COUNT(*) FROM workers WHERE role = 'foreman' AND deleted_at IS NULL",
            'active_workers' => "SELECT COUNT(*) FROM workers WHERE status = 'active' AND deleted_at IS NULL",
            'projects' => "SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL",
            'active_projects' => "SELECT COUNT(*) FROM projects WHERE status = 'running' AND deleted_at IS NULL",
            'completed_projects' => "SELECT COUNT(*) FROM projects WHERE status = 'completed' AND deleted_at IS NULL",
            'equipment' => "SELECT COALESCE(SUM(quantity),0) FROM equipment WHERE deleted_at IS NULL",
            'assigned_equipment' => "SELECT COALESCE(SUM(quantity),0) FROM equipment_assignments WHERE status = 'assigned' AND deleted_at IS NULL",
        ];
        $data = [];
        foreach ($queries as $key => $sql) {
            $data[$key] = (float) $db->query($sql)->fetchColumn();
        }
        $data['project_value'] = (new BaseRepository('projects'))->sum('total_amount', 'deleted_at IS NULL');
        $data['worker_type_counts'] = $this->workerTypeCounts();
        $data['equipment_purchase_value'] = (new BaseRepository('equipment'))->sum('purchase_price', 'deleted_at IS NULL');
        $data['available_equipment'] = max(0, $data['equipment'] - $data['assigned_equipment']);
        $data['received'] = (new BaseRepository('received_payments'))->sum('received_amount', "status = 'received'");
        $dailyExpenses = (new BaseRepository('expenses'))->sum('amount', "status IS NULL OR status <> 'void'");
        $materialExpenses = (new BaseRepository('material_purchases'))->sum('total_amount');
        $foodExpenses = (new BaseRepository('food_expenses'))->sum('total_amount');
        $vehicleExpenses = (new BaseRepository('vehicle_expenses'))->sum('total_amount');
        $data['salary'] = (new BaseRepository('salary_transactions'))->sum('amount');
        $data['advance'] = (new BaseRepository('advances'))->sum('amount', "status = 'approved'");
        $data['withdrawal'] = (new BaseRepository('withdrawals'))->sum('amount', "status = 'paid'");
        $data['admin_personal'] = (new BaseRepository('admin_personal_expenses'))->sum('amount', "status = 'active'");
        $data['total_earning'] = $data['project_value'];
        $data['total_expense'] = $dailyExpenses + $materialExpenses + $foodExpenses + $vehicleExpenses + $data['salary'];
        $data['expenses'] = $data['total_expense'];
        $data['receivable'] = $data['project_value'] - $data['received'];
        $data['estimated_balance'] = $data['total_earning'] - $data['total_expense'];
        return $data;
    }
}
