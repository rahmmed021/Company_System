<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Repositories\BaseRepository;
use App\Services\CalculationService;

final class WorkerProfileController extends Controller
{
    public function show(string $id): void
    {
        Auth::requireRole(['admin']);
        $worker = (new BaseRepository('workers'))->find((int) $id);
        if (!$worker) {
            (new ErrorController())->notFound();
            return;
        }
        $balance = (new CalculationService())->workerBalance((int) $worker['id']);
        $attendance = (new BaseRepository('attendance'))->all('worker_id = :worker', ['worker' => $worker['id']], 'attendance_date DESC LIMIT 20');
        $leaves = (new BaseRepository('leave_applications'))->all('worker_id = :worker', ['worker' => $worker['id']], 'id DESC LIMIT 20');
        $assignments = (new BaseRepository('worker_projects'))->all('worker_id = :worker', ['worker' => $worker['id']], 'id DESC');
        $projects = [];
        foreach ((new BaseRepository('projects'))->all() as $project) {
            $projects[(int) $project['id']] = $project;
        }
        $this->render('workers/profile', compact('worker', 'balance', 'attendance', 'leaves', 'assignments', 'projects'));
    }

    public function password(string $id): void
    {
        Auth::requireRole(['admin']);
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', __('validation.csrf'));
            redirect('/admin/workers/profile/' . (int) $id);
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        if (strlen($password) < 8 || $password !== $confirmation) {
            flash('error', __('validation.password_confirmed'));
            redirect('/admin/workers/profile/' . (int) $id);
        }

        $worker = (new BaseRepository('workers'))->find((int) $id);
        if (!$worker) {
            (new ErrorController())->notFound();
            return;
        }

        $userRepo = new BaseRepository('users');
        $user = $userRepo->firstWhere('worker_id = :worker AND deleted_at IS NULL', ['worker' => $worker['id']]);
        if (!$user) {
            flash('error', __('worker_profile.user_missing'));
            redirect('/admin/workers/profile/' . (int) $id);
        }

        $userRepo->update((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', __('worker_profile.password_updated'));
        redirect('/admin/workers/profile/' . (int) $id);
    }
}
