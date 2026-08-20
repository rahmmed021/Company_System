<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class ErrorController extends Controller
{
    public function notFound(): void
    {
        $this->render('errors/code', ['code' => 404, 'message' => __('errors.not_found')]);
    }

    public function forbidden(): void
    {
        $this->render('errors/code', ['code' => 403, 'message' => __('errors.forbidden')]);
    }
}
