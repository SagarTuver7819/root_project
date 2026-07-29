<?php

/**
 * Copy to database.php on the server and fill live credentials.
 * cp config/database.example.php config/database.php
 */
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'your_database_name',
    'username' => 'your_db_user',
    'password' => 'your_db_password',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
