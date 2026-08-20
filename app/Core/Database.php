<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'),
            env('DB_PORT', '3306'),
            env('DB_DATABASE', 'contracting_erp')
        );

        try {
            self::$pdo = new PDO($dsn, (string) env('DB_USERNAME', 'root'), (string) env('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$pdo;
        } catch (PDOException $exception) {
            Logger::error($exception->getMessage());
            http_response_code(500);
            exit(env('APP_DEBUG', false) ? e($exception->getMessage()) : __('errors.database'));
        }
    }
}
