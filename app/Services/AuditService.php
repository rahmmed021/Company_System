<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\BaseRepository;

final class AuditService
{
    public function log(string $action, string $module, ?int $recordId = null, array $old = [], array $new = []): void
    {
        try {
            (new BaseRepository('audit_logs'))->create([
                'user_id' => Auth::user()['id'] ?? null,
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'old_data' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'new_data' => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Audit logging must not break the user's primary action.
        }
    }
}
