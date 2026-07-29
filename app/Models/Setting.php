<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function allKeyed(): array
    {
        $rows = Database::fetchAll('SELECT `key`, `value` FROM settings');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = Database::fetch('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1', [$key]);
        return $row['value'] ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $existing = Database::fetch('SELECT id FROM settings WHERE `key` = ?', [$key]);
        if ($existing) {
            Database::update('settings', [
                'value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = :_id', ['_id' => $existing['id']]);
        } else {
            Database::insert('settings', [
                'key' => $key,
                'value' => $value,
                'group_name' => $group,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function setMany(array $pairs, string $group = 'general'): void
    {
        foreach ($pairs as $key => $value) {
            self::set($key, $value, $group);
        }
    }
}
