<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Repositories\BaseRepository;
use App\Services\CalculationService;

final class ProfileController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $worker = $user['worker_id'] ? (new BaseRepository('workers'))->find((int) $user['worker_id']) : null;
        $balance = $worker ? (new CalculationService())->workerBalance((int) $worker['id']) : [];
        $this->render('profile/index', compact('user', 'worker', 'balance'));
    }

    public function password(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            flash('error', __('validation.csrf'));
            redirect('/profile');
        }
        if (strlen((string) ($_POST['password'] ?? '')) < 8) {
            flash('error', __('validation.password_length'));
            redirect('/profile');
        }
        (new BaseRepository('users'))->update((int) Auth::user()['id'], [
            'password_hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
        ]);
        flash('success', __('messages.saved'));
        redirect('/profile');
    }
}
