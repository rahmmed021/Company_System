<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/app/Helpers/functions.php';

load_env(BASE_PATH . '/.env');

date_default_timezone_set(
    (string) env('APP_TIMEZONE', 'Asia/Dhaka')
);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = BASE_PATH . '/app/'
        . str_replace(
            '\\',
            '/',
            substr($class, strlen($prefix))
        )
        . '.php';

    if (is_file($path)) {
        require $path;
    }
});

App\Core\Session::start();
App\Core\Lang::load();
App\Core\Csrf::token();

set_exception_handler(function (Throwable $exception): void {
    App\Core\Logger::error(
        $exception->getMessage()
        . "\n"
        . $exception->getTraceAsString()
    );

    http_response_code(500);

    echo env('APP_DEBUG', false)
        ? e($exception->getMessage())
        : __('errors.general');
});

$router = new App\Core\Router();

require BASE_PATH . '/routes/web.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI']
);