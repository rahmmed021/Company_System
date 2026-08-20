<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\BaseRepository;

final class NotificationController
{
    public function open(string $id): void
    {
        Auth::requireAuth();
        $notification = (new BaseRepository('notifications'))->find((int) $id);
        if (!$notification) {
            (new ErrorController())->notFound();
            return;
        }

        $user = Auth::user();
        $belongsToUser = (int) ($notification['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $isAdminGlobal = ($user['role'] ?? '') === 'admin' && empty($notification['user_id']);
        if (!$belongsToUser && !$isAdminGlobal) {
            (new ErrorController())->forbidden();
            return;
        }

        (new BaseRepository('notifications'))->update((int) $notification['id'], ['is_read' => 1]);
        $destination = trim((string) ($notification['destination_url'] ?? ''));
        redirect($destination !== '' ? $destination : '/' . ($user['role'] ?? 'admin') . '/notifications');
    }
}
