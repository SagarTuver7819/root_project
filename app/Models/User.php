<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static bool $softDelete = true;

    public static function findByLogin(string $login): ?array
    {
        return Database::fetch(
            'SELECT * FROM users WHERE (email = ? OR username = ?) AND deleted_at IS NULL LIMIT 1',
            [$login, $login]
        );
    }

    public static function findByRememberToken(string $hashedToken): ?array
    {
        return Database::fetch(
            'SELECT * FROM users WHERE remember_token = ? AND deleted_at IS NULL LIMIT 1',
            [$hashedToken]
        );
    }

    public static function updateRememberToken(int $id, ?string $token): void
    {
        Database::update('users', [
            'remember_token' => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => $id]);
    }

    public static function touchLastLogin(int $id): void
    {
        Database::update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => $id]);
    }

    public static function roles(int $userId): array
    {
        return Database::fetchAll(
            'SELECT r.* FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = ?
             ORDER BY r.id ASC',
            [$userId]
        );
    }

    public static function permissions(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT DISTINCT p.slug FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ?',
            [$userId]
        );
        return array_column($rows, 'slug');
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [$email]
        );
    }
}
