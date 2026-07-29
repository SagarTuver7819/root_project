<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();

$col = Database::fetch("SHOW COLUMNS FROM bills LIKE 'booking_amount'");
if (!$col) {
    Database::query('ALTER TABLE bills ADD COLUMN booking_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER gross_amount');
    echo "Added bills.booking_amount\n";
} else {
    echo "bills.booking_amount already exists\n";
}

$now = date('Y-m-d H:i:s');
foreach (['booking_amount' => '300', 'booking_validity_months' => '3'] as $key => $value) {
    $exists = Database::fetch('SELECT id FROM settings WHERE `key` = ?', [$key]);
    if ($exists) {
        Database::update('settings', ['value' => $value, 'updated_at' => $now], 'id = :_id', ['_id' => $exists['id']]);
    } else {
        Database::insert('settings', [
            'key' => $key,
            'value' => $value,
            'group_name' => 'billing',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    echo "Setting {$key} = {$value}\n";
}

echo "Booking fee migration complete.\n";
