<?php

namespace App\Core;

class App
{
    private static ?array $config = null;
    private static array $loaded = [];

    public static function bootstrap(): void
    {
        Env::load();

        $app = self::config('app');
        date_default_timezone_set($app['timezone'] ?? 'Asia/Kolkata');

        if (!empty($app['debug'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name($app['session_name'] ?? 'roots_hms_session');
            session_start();
        }

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function config(string $file): array
    {
        if (!isset(self::$loaded[$file])) {
            $path = dirname(__DIR__, 2) . '/config/' . $file . '.php';
            self::$loaded[$file] = file_exists($path) ? require $path : [];
        }

        return self::$loaded[$file];
    }

    public static function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) : $base;
    }

    public static function url(string $path = ''): string
    {
        $base = rtrim(self::config('app')['url'] ?? '', '/');
        $path = ltrim($path, '/');
        return $path ? $base . '/' . $path : $base;
    }

    public static function asset(string $path): string
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}
