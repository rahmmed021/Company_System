<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\ModuleRegistry;

final class ResourcePolicy
{
    public static function allows(string $key, string $action): bool
    {
        $config = ModuleRegistry::get($key);
        if (!$config) {
            return false;
        }
        if ($config['read_only'] ?? false) {
            return false;
        }

        $role = Auth::user()['role'] ?? '';
        if ($role === 'admin') {
            return true;
        }

        if ($role === 'foreman') {
            $allowedCreate = ['attendance', 'advances', 'expenses', 'materials', 'food', 'vehicles', 'leave'];
            return $action === 'CREATE' && in_array($key, $allowedCreate, true);
        }

        if ($role === 'labor') {
            return $key === 'leave' && $action === 'CREATE';
        }

        return false;
    }
}
