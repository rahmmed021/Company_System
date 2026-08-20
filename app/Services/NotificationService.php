<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\BaseRepository;

final class NotificationService
{
    public function create(?int $userId, string $titleKey, string $bodyKey, string $type = 'info', ?string $destinationUrl = null): void
    {
        $data = [
            'user_id' => $userId,
            'title_key' => $titleKey,
            'body_key' => $bodyKey,
            'type' => $type,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($destinationUrl !== null && $this->hasDestinationColumn()) {
            $data['destination_url'] = $destinationUrl;
        }
        (new BaseRepository('notifications'))->create($data);
    }

    private function hasDestinationColumn(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => 'notifications', 'column' => 'destination_url']);
        return $exists = (bool) $stmt->fetchColumn();
    }
}
