<?php
declare(strict_types=1);

use App\Controllers\AjaxController;
use App\Controllers\AuthController;
use App\Controllers\BackupController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\IdCardController;
use App\Controllers\NoticeController;
use App\Controllers\NotificationController;
use App\Controllers\ProfileController;
use App\Controllers\ReportController;
use App\Controllers\ResourceController;
use App\Controllers\WorkerProfileController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/language/{locale}', [AuthController::class, 'language']);
$router->get('/theme/{theme}', [AuthController::class, 'theme']);

$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile/password', [ProfileController::class, 'password']);
$router->get('/id-card', [IdCardController::class, 'mine'], ['foreman', 'labor']);
$router->get('/id-card/download', [IdCardController::class, 'downloadMine'], ['foreman', 'labor']);

$router->get('/admin/dashboard', [DashboardController::class, 'admin'], ['admin']);
$router->get('/foreman/dashboard', [DashboardController::class, 'workerDashboard'], ['foreman']);
$router->get('/labor/dashboard', [DashboardController::class, 'workerDashboard'], ['labor']);

$router->get('/admin/reports', [ReportController::class, 'index'], ['admin']);
$router->get('/admin/reports/{module}', [ReportController::class, 'show'], ['admin']);
$router->get('/admin/reports/{module}/export/{type}', [ReportController::class, 'export'], ['admin']);
$router->get('/admin/id-card/{workerId}', [IdCardController::class, 'adminShow'], ['admin']);
$router->get('/admin/id-card/download/{workerId}', [IdCardController::class, 'adminDownload'], ['admin']);
$router->get('/admin/workers/profile/{id}', [WorkerProfileController::class, 'show'], ['admin']);
$router->post('/admin/workers/profile/{id}/password', [WorkerProfileController::class, 'password'], ['admin']);
$router->get('/notifications/open/{id}', [NotificationController::class, 'open']);
$router->get('/admin/notices/create', [NoticeController::class, 'create'], ['admin']);
$router->post('/admin/notices/send', [NoticeController::class, 'send'], ['admin']);

$router->get('/admin/backups', [BackupController::class, 'index'], ['admin']);
$router->post('/admin/backups/create', [BackupController::class, 'create'], ['admin']);
$router->get('/admin/backups/download/{id}', [BackupController::class, 'download'], ['admin']);

$router->get('/admin/store', [ResourceController::class, 'storeIndex'], ['admin']);
$router->get('/foreman/store', [ResourceController::class, 'storeIndex'], ['foreman']);
$router->get('/admin/store/use', [ResourceController::class, 'storeUseForm'], ['admin']);
$router->get('/foreman/store/use', [ResourceController::class, 'storeUseForm'], ['foreman']);
$router->post('/admin/store/use', [ResourceController::class, 'storeUse'], ['admin']);
$router->post('/foreman/store/use', [ResourceController::class, 'storeUse'], ['foreman']);

$router->get('/ajax/search', [AjaxController::class, 'search']);
$router->get('/ajax/dashboard', [AjaxController::class, 'dashboard'], ['admin']);
$router->get('/ajax/project/{id}/financials', [AjaxController::class, 'projectFinancials'], ['admin', 'foreman']);
$router->get('/ajax/options/{module}', [AjaxController::class, 'options']);

$router->get('/admin/projects/attachment/{id}/{mode}', [ResourceController::class, 'projectAttachment'], ['admin']);
$router->get('/foreman/projects/attachment/{id}/{mode}', [ResourceController::class, 'projectAttachment'], ['foreman']);

foreach (['admin', 'foreman', 'labor'] as $role) {
    $router->get('/' . $role . '/{module}', [ResourceController::class, 'index'], [$role]);
    $router->get('/' . $role . '/{module}/create', [ResourceController::class, 'create'], [$role]);
    $router->post('/' . $role . '/{module}/store', [ResourceController::class, 'store'], [$role]);
    $router->get('/' . $role . '/{module}/edit/{id}', [ResourceController::class, 'edit'], [$role]);
    $router->post('/' . $role . '/{module}/update/{id}', [ResourceController::class, 'update'], [$role]);
    $router->post('/' . $role . '/{module}/delete/{id}', [ResourceController::class, 'delete'], [$role]);
}
