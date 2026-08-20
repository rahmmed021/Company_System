<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function error(string $message): void
    {
        $dir = base_path('storage/logs');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        error_log('[' . date('c') . '] ' . $message . PHP_EOL, 3, $dir . '/app.log');
    }
}
