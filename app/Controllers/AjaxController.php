<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\ModuleRegistry;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Repositories\BaseRepository;
use App\Services\CalculationService;

final class AjaxController extends Controller
{
    public function search(): void
    {
        Auth::requireAuth();
        $q = '%' . trim((string) ($_GET['q'] ?? '')) . '%';
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT 'worker' type, full_name label, id FROM workers WHERE full_name LIKE :q_worker
             UNION ALL SELECT 'project', name_en, id FROM projects WHERE name_en LIKE :q_project
             UNION ALL SELECT 'equipment', name_en, id FROM equipment WHERE name_en LIKE :q_equipment
             LIMIT 20"
        );
        $stmt->execute(['q_worker' => $q, 'q_project' => $q, 'q_equipment' => $q]);
        $this->json(['success' => true, 'results' => $stmt->fetchAll()]);
    }

    public function dashboard(): void
    {
        Auth::requireRole(['admin']);
        $this->json(['success' => true, 'data' => (new CalculationService())->dashboard()]);
    }

    public function projectFinancials(string $id): void
    {
        Auth::requireRole(['admin', 'foreman']);
        $this->json(['success' => true, 'data' => (new CalculationService())->projectFinancials((int) $id)]);
    }

    public function options(string $module): void
    {
        Auth::requireAuth();
        $config = ModuleRegistry::get($module);
        if (!$config) {
            $this->json(['success' => false, 'message' => __('errors.not_found')], 404);
            return;
        }
        $rows = (new BaseRepository($config['table']))->all();
        $this->json(['success' => true, 'results' => $rows]);
    }
}
