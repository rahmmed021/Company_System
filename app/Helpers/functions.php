<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\ResourcePolicy;
use App\Core\Session;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return rtrim((string) BASE_PATH, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

if (!function_exists('load_env')) {
    function load_env(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return Lang::get($key, $replace);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $base = rtrim((string) env('APP_URL', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base ? $base . $path : $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('is_role')) {
    function is_role(string $role): bool
    {
        $user = current_user();
        return $user && $user['role'] === $role;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            Session::flash($key, $value);
            return null;
        }
        return Session::pullFlash($key);
    }
}

if (!function_exists('money')) {
    function money(mixed $amount): string
    {
        $symbol = env('CURRENCY', 'BDT') === 'BDT' ? 'BDT' : (string) env('CURRENCY', 'BDT');
        return $symbol . ' ' . number_format((float) $amount, 2);
    }
}

if (!function_exists('route_role_prefix')) {
    function route_role_prefix(): string
    {
        $role = current_user()['role'] ?? 'admin';
        return '/' . $role;
    }
}

if (!function_exists('option_label')) {
    function option_label(mixed $value): string
    {
        $key = 'options.' . str_replace('-', '_', (string) $value);
        $translated = __($key);
        return $translated !== '' ? $translated : ucwords(str_replace('_', ' ', (string) $value));
    }
}

if (!function_exists('display_value')) {
    function display_value(string $field, mixed $value, array $relations = []): string
    {
        if (isset($relations[$field])) {
            foreach ($relations[$field]['rows'] as $row) {
                if ((string) $row['id'] === (string) $value) {
                    return e($row[$relations[$field]['display']] ?? $value);
                }
            }
        }
        if (str_ends_with($field, '_path') || str_ends_with($field, '_image')) {
            if (!$value) {
                return '';
            }
            $href = e(url((string) $value));
            return '<a href="' . $href . '" target="_blank">' . e(__('actions.view')) . '</a> <a href="' . $href . '" download>' . e(__('actions.download')) . '</a>';
        }
        if (str_contains($field, 'amount') || str_contains($field, 'salary') || str_contains($field, 'price') || str_contains($field, 'cost')) {
            return money($value);
        }
        if ($field === 'status' || $field === 'role' || $field === 'is_read' || $field === 'success' || str_contains($field, 'method') || str_contains($field, 'type')) {
            return e(option_label($value));
        }
        return e($value);
    }
}

if (!function_exists('can_resource_action')) {
    function can_resource_action(string $key, string $action): bool
    {
        return ResourcePolicy::allows($key, $action);
    }
}

if (!function_exists('public_file')) {
    function public_file(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return asset('images/nousin-logo.svg');
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return url(ltrim($path, '/'));
    }
}

if (!function_exists('localized')) {
    function localized(array $row, string $base): string
    {
        $locale = \App\Core\Lang::locale();
        return (string) ($row[$base . '_' . $locale] ?? $row[$base . '_en'] ?? $row[$base] ?? '');
    }
}

if (!function_exists('line_or_raw')) {
    function line_or_raw(?string $value): string
    {
        $value = (string) $value;
        if (str_starts_with($value, 'raw:')) {
            return substr($value, 4);
        }
        $translated = __($value);
        return $translated !== '' ? $translated : $value;
    }
}
