<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\User;
use App\Services\AuditService;

class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->view('modules/auth/login', [
            'title' => 'Login',
        ], 'layouts/auth');
    }

    public function login(Request $request): void
    {
        $data = $this->validate($request, [
            'login' => 'required',
            'password' => 'required',
        ]);

        $remember = (bool) $request->input('remember');

        if (!Auth::attempt($data['login'], $data['password'], $remember)) {
            if ($request->isAjax()) {
                $this->jsonError('Invalid credentials or inactive account.');
            }
            Session::flash('error', 'Invalid credentials or inactive account.');
            $this->redirect('login');
        }

        AuditService::log('auth', 'login', Auth::id());

        if ($request->isAjax()) {
            $this->jsonSuccess('Login successful.', ['redirect' => App::url('dashboard')]);
        }

        Session::flash('success', 'Welcome back!');
        $this->redirect('dashboard');
    }

    public function logout(Request $request): void
    {
        AuditService::log('auth', 'logout', Auth::id());
        Auth::logout();
        Session::flash('success', 'Logged out successfully.');
        $this->redirect('login');
    }

    public function showForgot(Request $request): void
    {
        $this->view('modules/auth/forgot', ['title' => 'Forgot Password'], 'layouts/auth');
    }

    public function forgot(Request $request): void
    {
        $data = $this->validate($request, ['email' => 'required|email']);
        $user = User::findByEmail($data['email']);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::insert('password_resets', [
                'email' => $data['email'],
                'token' => hash('sha256', $token),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            // In production, email the link. For local, store flash with reset URL.
            Session::flash('info', 'Password reset link generated. Use: ' . App::url('reset-password?token=' . $token . '&email=' . urlencode($data['email'])));
        } else {
            Session::flash('success', 'If the email exists, a reset link has been sent.');
        }

        $this->redirect('forgot-password');
    }

    public function showReset(Request $request): void
    {
        $this->view('modules/auth/reset', [
            'title' => 'Reset Password',
            'token' => $request->query('token'),
            'email' => $request->query('email'),
        ], 'layouts/auth');
    }

    public function reset(Request $request): void
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6',
            'password_confirmation' => 'required',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            Session::flash('error', 'Passwords do not match.');
            $this->redirect('reset-password?token=' . urlencode($data['token']) . '&email=' . urlencode($data['email']));
        }

        $row = Database::fetch(
            'SELECT * FROM password_resets WHERE email = ? AND token = ? ORDER BY id DESC LIMIT 1',
            [$data['email'], hash('sha256', $data['token'])]
        );

        if (!$row || strtotime($row['created_at']) < time() - 3600) {
            Session::flash('error', 'Invalid or expired reset token.');
            $this->redirect('forgot-password');
        }

        $user = User::findByEmail($data['email']);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirect('forgot-password');
        }

        User::updateById((int) $user['id'], [
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        Database::query('DELETE FROM password_resets WHERE email = ?', [$data['email']]);

        Session::flash('success', 'Password reset successfully. Please login.');
        $this->redirect('login');
    }

    public function showChangePassword(Request $request): void
    {
        $this->view('modules/auth/change-password', ['title' => 'Change Password', 'pageTitle' => 'Change Password']);
    }

    public function changePassword(Request $request): void
    {
        $data = $this->validate($request, [
            'current_password' => 'required',
            'password' => 'required|min:6',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::user();
        if (!password_verify($data['current_password'], $user['password'])) {
            if ($request->isAjax()) {
                $this->jsonError('Current password is incorrect.');
            }
            Session::flash('error', 'Current password is incorrect.');
            $this->redirect('change-password');
        }

        if ($data['password'] !== $data['password_confirmation']) {
            if ($request->isAjax()) {
                $this->jsonError('Passwords do not match.');
            }
            Session::flash('error', 'Passwords do not match.');
            $this->redirect('change-password');
        }

        User::updateById((int) $user['id'], [
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        AuditService::log('auth', 'change_password', (int) $user['id']);

        if ($request->isAjax()) {
            $this->jsonSuccess('Password changed successfully.');
        }
        Session::flash('success', 'Password changed successfully.');
        $this->redirect('change-password');
    }

    public function profile(Request $request): void
    {
        $this->view('modules/auth/profile', [
            'title' => 'My Profile',
            'pageTitle' => 'My Profile',
            'user' => Auth::user(),
            'role' => Auth::primaryRole(),
        ]);
    }
}
