<?php
declare(strict_types=1);

namespace App\Core;

use App\Repositories\BaseRepository;

final class Auth
{
    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $login, string $password): bool
    {
        $repo = new BaseRepository('users');

        $user = $repo->firstWhere(
            '(email = :email_login OR mobile = :mobile_login)
             AND status = :status
             AND deleted_at IS NULL',
            [
                'email_login' => $login,
                'mobile_login' => $login,
                'status' => 'active',
            ]
        );

        if (
            !$user ||
            !self::verifyPassword(
                $password,
                (string) $user['password_hash'],
                (int) $user['id']
            )
        ) {
            self::recordLogin($login, false, null);
            return false;
        }

        session_regenerate_id(true);

        unset($user['password_hash']);

        Session::put('user', $user);
        Session::put('last_activity', time());

        Lang::setLocale(
            !empty($user['language'])
                ? (string) $user['language']
                : Lang::locale()
        );

        self::recordLogin(
            $login,
            true,
            (int) $user['id']
        );

        (new \App\Services\AuditService())->log(
            'LOGIN',
            'auth',
            (int) $user['id']
        );

        return true;
    }

    public static function logout(): void
    {
        if (self::check()) {
            (new \App\Services\AuditService())->log(
                'LOGOUT',
                'auth',
                (int) self::user()['id']
            );
        }

        Session::forget('user');

        session_regenerate_id(true);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            redirect('/login');
        }

        $timeout = (int) env('SESSION_TIMEOUT', 7200);

        $lastActivity = (int) Session::get(
            'last_activity',
            time()
        );

        if (time() - $lastActivity > $timeout) {
            self::logout();

            flash(
                'error',
                __('auth.session_expired')
            );

            redirect('/login');
        }

        Session::put(
            'last_activity',
            time()
        );
    }

    public static function requireRole(array $roles): void
    {
        self::requireAuth();

        $user = self::user();
        $role = $user['role'] ?? '';

        if (!in_array($role, $roles, true)) {
            http_response_code(403);

            (new \App\Controllers\ErrorController())->forbidden();

            exit;
        }
    }

    private static function recordLogin(
        string $login,
        bool $success,
        ?int $userId
    ): void {
        (new BaseRepository('login_history'))->create([
            'user_id' => $userId,
            'login_identifier' => $login,
            'success' => $success ? 1 : 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr(
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                0,
                255
            ),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function verifyPassword(
        string $password,
        string $hash,
        int $userId
    ): bool {
        if (password_verify($password, $hash)) {
            return true;
        }

        if (
            str_starts_with($hash, 'seed$') &&
            hash_equals(
                substr($hash, 5),
                hash('sha256', $password)
            )
        ) {
            (new BaseRepository('users'))->update(
                $userId,
                [
                    'password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                ]
            );

            return true;
        }

        return false;
    }
}
