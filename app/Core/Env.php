<?php

namespace App\Core;

class Env
{
    private static bool $loaded = false;

    /** @var array<string, string> */
    private static array $values = [];

    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $path ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (!str_contains($line, '=')) {
                        continue;
                    }
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($name === '') {
                        continue;
                    }
                    // Strip matching quotes
                    if (
                        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))
                    ) {
                        $value = substr($value, 1, -1);
                    }
                    self::$values[$name] = $value;
                    // Do not overwrite real server env
                    if (getenv($name) === false) {
                        putenv($name . '=' . $value);
                        $_ENV[$name] = $value;
                    }
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $env = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($env === false || $env === null || $env === '') {
            return $default;
        }

        return $env;
    }
}
