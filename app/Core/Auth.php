<?php

namespace App\Core;

use App\Models\User;
use App\Models\Setting;

class Auth
{
    private static ?array $user = null;
    private static ?array $permissions = null;

    public static function attempt(string $login, string $password, bool $remember = false): bool
    {
        $user = User::findByLogin($login);
        if (!$user || !(int) $user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        self::login($user, $remember);
        return true;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$permissions = null;

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::updateRememberToken((int) $user['id'], hash('sha256', $token));
            setcookie('remember_token', $token, [
                'expires' => time() + (60 * 60 * 24 * 30),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        User::touchLastLogin((int) $user['id']);
    }

    public static function logout(): void
    {
        if ($id = self::id()) {
            User::updateRememberToken($id, null);
        }
        setcookie('remember_token', '', time() - 3600, '/');
        Session::forget('user_id');
        self::$user = null;
        self::$permissions = null;
        Session::regenerate();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = Session::get('user_id');
        if (!$id && !empty($_COOKIE['remember_token'])) {
            $user = User::findByRememberToken(hash('sha256', $_COOKIE['remember_token']));
            if ($user && (int) $user['is_active']) {
                Session::set('user_id', (int) $user['id']);
                self::$user = $user;
                return self::$user;
            }
        }

        if ($id) {
            self::$user = User::find((int) $id);
            if (self::$user && !(int) self::$user['is_active']) {
                self::logout();
                return null;
            }
        }

        return self::$user;
    }

    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }

        $user = self::user();
        if (!$user) {
            return self::$permissions = [];
        }

        self::$permissions = User::permissions((int) $user['id']);
        return self::$permissions;
    }

    public static function can(string $permission): bool
    {
        $perms = self::permissions();
        if (in_array('*', $perms, true) || in_array($permission, $perms, true)) {
            return true;
        }

        // Super admin role slug
        $roles = User::roles((int) (self::id() ?? 0));
        return in_array('super_admin', array_column($roles, 'slug'), true);
    }

    public static function hasRole(string $slug): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        $roles = User::roles((int) $user['id']);
        return in_array($slug, array_column($roles, 'slug'), true);
    }

    public static function primaryRole(): ?array
    {
        $user = self::user();
        if (!$user) {
            return null;
        }
        $roles = User::roles((int) $user['id']);
        return $roles[0] ?? null;
    }
}
