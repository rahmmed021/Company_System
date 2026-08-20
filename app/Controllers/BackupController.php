<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Repositories\BaseRepository;

final class BackupController extends Controller
{
    public function index(): void
    {
        Auth::requireRole(['admin']);
        $backups = (new BaseRepository('backups'))->all();
        $this->render('backups/index', compact('backups'));
    }

    public function create(): void
    {
        Auth::requireRole(['admin']);
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', __('validation.csrf'));
            redirect('/admin/backups');
        }
        $file = 'backup_' . date('Ymd_His') . '.sql';
        $path = base_path('storage/backups/' . $file);
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s > %s',
            escapeshellarg((string) env('DB_HOST', '127.0.0.1')),
            escapeshellarg((string) env('DB_PORT', '3306')),
            escapeshellarg((string) env('DB_USERNAME', 'root')),
            env('DB_PASSWORD', '') !== '' ? '-p' . escapeshellarg((string) env('DB_PASSWORD', '')) : '',
            escapeshellarg((string) env('DB_DATABASE', 'contracting_erp')),
            escapeshellarg($path)
        );
        system($command, $code);
        if ($code !== 0 || !is_file($path)) {
            flash('error', __('messages.backup_failed'));
            redirect('/admin/backups');
        }
        (new BaseRepository('backups'))->create([
            'file_name' => $file,
            'file_path' => 'storage/backups/' . $file,
            'created_by' => Auth::user()['id'],
        ]);
        flash('success', __('messages.backup_created'));
        redirect('/admin/backups');
    }

    public function download(string $id): void
    {
        Auth::requireRole(['admin']);
        $backup = (new BaseRepository('backups'))->find((int) $id);
        if (!$backup || !is_file(base_path((string) $backup['file_path']))) {
            (new ErrorController())->notFound();
            return;
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename((string) $backup['file_name']) . '"');
        readfile(base_path((string) $backup['file_path']));
    }
}
