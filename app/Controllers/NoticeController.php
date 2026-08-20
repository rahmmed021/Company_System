<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Repositories\BaseRepository;
use App\Services\NotificationService;

final class NoticeController extends Controller
{
    public function create(): void
    {
        Auth::requireRole(['admin']);
        $workers = (new BaseRepository('workers'))->all('status = :status', ['status' => 'active'], 'full_name ASC');
        $workerTypes = array_values(array_unique(array_map(static fn (array $worker): string => (string) $worker['role'], $workers)));
        sort($workerTypes);
        $this->render('notices/create', compact('workers', 'workerTypes'));
    }

    public function send(): void
    {
        Auth::requireRole(['admin']);
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', __('validation.csrf'));
            redirect('/admin/notices/create');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $recipientType = (string) ($_POST['recipient_type'] ?? '');
        $workerId = (int) ($_POST['worker_id'] ?? 0);
        $workerType = trim((string) ($_POST['worker_type'] ?? ''));

        if ($title === '' || $message === '' || !in_array($recipientType, ['single', 'group', 'all'], true)) {
            flash('error', __('validation.required'));
            redirect('/admin/notices/create');
        }

        $workers = match ($recipientType) {
            'single' => (new BaseRepository('workers'))->all('id = :worker AND status = :status', ['worker' => $workerId, 'status' => 'active']),
            'group' => (new BaseRepository('workers'))->all('role = :role AND status = :status', ['role' => $workerType, 'status' => 'active']),
            default => (new BaseRepository('workers'))->all('status = :status', ['status' => 'active']),
        };

        $sent = 0;
        $users = new BaseRepository('users');
        $notifications = new NotificationService();
        foreach ($workers as $worker) {
            $user = $users->firstWhere('worker_id = :worker AND status = :status AND deleted_at IS NULL', [
                'worker' => $worker['id'],
                'status' => 'active',
            ]);
            if (!$user) {
                continue;
            }
            $notifications->create((int) $user['id'], 'raw:' . $title, 'raw:' . $message, 'info', '/profile');
            $sent++;
        }

        flash('success', __('notices.sent') . ' ' . $sent);
        redirect('/admin/notices/create');
    }
}
