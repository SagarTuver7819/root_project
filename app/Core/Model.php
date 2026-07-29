<?php

namespace App\Core;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static bool $softDelete = false;

    public static function table(): string
    {
        return static::$table;
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?';
        $params = [$id];
        if (static::$softDelete) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return Database::fetch($sql, $params);
    }

    public static function create(array $data): int
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update(static::$table, $data, static::$primaryKey . ' = :_id', ['_id' => $id]);
    }

    public static function softDelete(int $id): int
    {
        return self::updateById($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '`';
        if (static::$softDelete) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        $sql .= ' ORDER BY ' . $orderBy;
        return Database::fetchAll($sql);
    }
}
