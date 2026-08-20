<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = base_path('app/Views/' . $view . '.php');
        $layoutFile = base_path('app/Views/' . $layout . '.php');
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require $layoutFile;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
