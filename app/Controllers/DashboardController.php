<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\BaseRepository;
use App\Services\CalculationService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $role = Auth::user()['role'];
        if ($role !== 'admin') {
            redirect('/' . $role . '/dashboard');
        }
        redirect('/admin/dashboard');
    }

    public function admin(): void
    {
        Auth::requireRole(['admin']);
        $stats = (new CalculationService())->dashboard();
        $recent = [
            'attendance' => (new BaseRepository('attendance'))->all('1=1', [], 'id DESC LIMIT 5'),
            'expenses' => (new BaseRepository('expenses'))->all('1=1', [], 'id DESC LIMIT 5'),
            'payments' => (new BaseRepository('received_payments'))->all('1=1', [], 'id DESC LIMIT 5'),
        ];
        $this->render('dashboard/admin', compact('stats', 'recent'));
    }

    public function workerDashboard(): void
    {
        Auth::requireRole(['foreman', 'labor']);
        $user = Auth::user();
        $workerId = (int) ($user['worker_id'] ?? 0);
        $balance = $workerId ? (new CalculationService())->workerBalance($workerId) : [];
        $assignments = $workerId ? (new BaseRepository('worker_projects'))->all('worker_id = :worker', ['worker' => $workerId]) : [];
        $projects = [];
        foreach ((new BaseRepository('projects'))->all() as $project) {
            $projects[(int) $project['id']] = $project;
        }
        $attendance = $workerId ? (new BaseRepository('attendance'))->all('worker_id = :worker', ['worker' => $workerId], 'attendance_date DESC LIMIT 10') : [];
        $attendanceAdvances = [];
        if ($workerId) {
            $advanceRows = (new BaseRepository('advances'))->all(
                'worker_id = :worker AND status = :status AND deleted_at IS NULL',
                ['worker' => $workerId, 'status' => 'approved'],
                'date DESC, id DESC'
            );
            foreach ($advanceRows as $advanceRow) {
                $date = (string) ($advanceRow['date'] ?? '');
                if ($date === '') {
                    continue;
                }
                $attendanceAdvances[$date] = ($attendanceAdvances[$date] ?? 0) + (float) ($advanceRow['amount'] ?? 0);
            }
        }
        $this->render('dashboard/worker', compact('balance', 'assignments', 'projects', 'attendance', 'attendanceAdvances'));
    }
}
