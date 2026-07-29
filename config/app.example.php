<?php

/**
 * Copy to app.php on the server and set live URL.
 * Example subdomain: https://hms.yourdomain.com
 * Document root must point to /public
 */
return [
    'name' => 'Roots Dentistry',
    'env' => 'production',
    'debug' => false,
    'url' => 'https://hms.yourdomain.com',
    'timezone' => 'Asia/Kolkata',
    'locale' => 'en',
    'key' => 'change-this-to-a-long-random-secret-key',
    'session_name' => 'roots_hms_session',
    'csrf_token_name' => '_token',
    'upload_path' => __DIR__ . '/../public/assets/uploads',
    'upload_url' => '/assets/uploads',
];
