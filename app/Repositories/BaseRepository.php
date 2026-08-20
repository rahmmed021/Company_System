<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class BaseRepository
{
    public function __construct(private readonly string $table)
    {
    }

    public function all(string $where = '1=1', array $params = [], string $order = 'id DESC'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$where}";
        if ($this->hasColumn('deleted_at')) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " ORDER BY {$order}";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function firstWhere(string $where, array $params = []): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM {$this->table} WHERE {$where} LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        if ($this->hasColumn('created_at') && !array_key_exists('created_at', $data)) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if ($this->hasColumn('updated_at') && !array_key_exists('updated_at', $data)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            ':' . implode(', :', $columns)
        );
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if (!$data) {
            return;
        }
        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        $sets = implode(', ', array_map(fn (string $column): string => "{$column} = :{$column}", array_keys($data)));
        $data['id'] = $id;
        $stmt = Database::connection()->prepare("UPDATE {$this->table} SET {$sets} WHERE id = :id");
        $stmt->execute($data);
    }

    public function softDelete(int $id): void
    {
        if ($this->hasColumn('deleted_at')) {
            $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
            return;
        }
        $stmt = Database::connection()->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function count(string $where = '1=1', array $params = []): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function sum(string $column, string $where = '1=1', array $params = []): float
    {
        $stmt = Database::connection()->prepare("SELECT COALESCE(SUM({$column}),0) FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];
        $key = $this->table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $this->table, 'column' => $column]);
        return $cache[$key] = (bool) $stmt->fetchColumn();
    }
}
