<?php

/**
 * Shared app config — same file for local + live.
 * Change values only in `.env` (never commit `.env`).
 */
$detectedUrl = function_exists('detect_app_url') ? detect_app_url() : 'http://localhost/roots_project/public';
$appUrl = env('APP_URL');
if ($appUrl === null || $appUrl === '') {
    $appUrl = $detectedUrl;
}
$appUrl = rtrim((string) $appUrl, '/');

// Derive public path prefix for upload URLs (works for /public subdirectory or subdomain root)
$uploadUrl = env('UPLOAD_URL');
if ($uploadUrl === null || $uploadUrl === '') {
    $path = parse_url($appUrl, PHP_URL_PATH) ?: '';
    $uploadUrl = rtrim($path, '/') . '/assets/uploads';
}

return [
    'name' => env('APP_NAME', 'Roots Dentistry'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => $appUrl,
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),
    'locale' => env('APP_LOCALE', 'en'),
    'key' => env('APP_KEY', 'roots-dental-hms-secret-key-change-me'),
    'session_name' => env('SESSION_NAME', 'roots_hms_session'),
    'csrf_token_name' => env('CSRF_TOKEN_NAME', '_token'),
    'upload_path' => env('UPLOAD_PATH') ?: (__DIR__ . '/../public/assets/uploads'),
    'upload_url' => $uploadUrl,
];
