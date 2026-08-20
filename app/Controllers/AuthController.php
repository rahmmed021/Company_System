<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Session;
use App\Repositories\BaseRepository;

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['_token'] ?? null)) {
                flash('error', __('validation.csrf'));
                redirect('/login');
            }
            $login = trim((string) ($_POST['login'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (Auth::attempt($login, $password)) {
                $role = Auth::user()['role'];
                redirect('/' . $role . '/dashboard');
            }
            flash('error', __('auth.invalid'));
        }
        $this->render('auth/login', [], 'layouts/auth');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', __('auth.logged_out'));
        redirect('/login');
    }

    public function language(string $locale): void
    {
        Lang::setLocale($locale);
        if (Auth::check()) {
            (new BaseRepository('users'))->update((int) Auth::user()['id'], ['language' => $locale]);
            $user = Auth::user();
            $user['language'] = $locale;
            Session::put('user', $user);
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/login');
    }

    public function theme(string $theme): void
    {
        $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
        Session::put('theme', $theme);
        if (Auth::check()) {
            (new BaseRepository('users'))->update((int) Auth::user()['id'], ['theme' => $theme]);
        }
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
