<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\BaseRepository;

final class IdCardController extends Controller
{
    public function mine(): void
    {
        Auth::requireRole(['foreman', 'labor']);
        $workerId = (int) (Auth::user()['worker_id'] ?? 0);
        $this->showCard($workerId, false);
    }

    public function adminShow(string $workerId): void
    {
        Auth::requireRole(['admin']);
        $this->showCard((int) $workerId, true);
    }

    public function downloadMine(): void
    {
        Auth::requireRole(['foreman', 'labor']);
        $this->download((int) (Auth::user()['worker_id'] ?? 0));
    }

    public function adminDownload(string $workerId): void
    {
        Auth::requireRole(['admin']);
        $this->download((int) $workerId);
    }

    private function showCard(int $workerId, bool $admin): void
    {
        [$worker, $card] = $this->data($workerId);
        $companyAddress = $this->companyAddress();
        $this->render('idcards/show', compact('worker', 'card', 'admin', 'companyAddress'));
    }

    private function download(int $workerId): void
    {
        [$worker, $card] = $this->data($workerId);
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', $card['id_number']) . '.html"');
        $admin = false;
        $companyAddress = $this->companyAddress();
        $this->render('idcards/show', compact('worker', 'card', 'admin', 'companyAddress'), 'layouts/print');
    }

    private function companyAddress(): string
    {
        $row = (new BaseRepository('settings'))->firstWhere('setting_key = :key', ['key' => 'company_address']);
        return trim((string) ($row['setting_value'] ?? ''));
    }

    private function data(int $workerId): array
    {
        $worker = (new BaseRepository('workers'))->find($workerId);
        if (!$worker) {
            (new ErrorController())->notFound();
            exit;
        }
        $card = (new BaseRepository('id_cards'))->firstWhere('worker_id = :worker AND status = :status AND deleted_at IS NULL', ['worker' => $workerId, 'status' => 'active']);
        $anyCard = (new BaseRepository('id_cards'))->firstWhere('worker_id = :worker', ['worker' => $workerId]);
        if (!$card && $anyCard) {
            (new ErrorController())->notFound();
            exit;
        }
        if (!$card) {
            $card = [
                'id_number' => $worker['id_number'] ?? '',
                'designation' => $worker['role'] ?? 'labor',
                'mobile' => $worker['mobile'] ?? '',
                'photo_path' => $worker['photo_path'] ?? null,
            ];
        }
        return [$worker, $card];
    }
}
