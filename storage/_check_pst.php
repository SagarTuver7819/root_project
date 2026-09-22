<?php

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';
App\Core\App::bootstrap();

use App\Core\Database;

echo "=== COLUMNS ===\n";
foreach (Database::fetchAll('SHOW COLUMNS FROM patient_suggested_treatments') as $c) {
    echo $c['Field'] . ' | ' . $c['Type'] . ' | default=' . var_export($c['Default'], true) . "\n";
}

echo "\n=== ROWS (patient 17 & 177) ===\n";
$rows = Database::fetchAll(
    'SELECT id, patient_id, description, status, doctor_id, appointment_id, next_appointment_date, next_appointment_time, completed_at
     FROM patient_suggested_treatments
     WHERE patient_id IN (17, 177)
     ORDER BY patient_id, id'
);
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== PENDING FILTER CHECK ===\n";
$pending = Database::fetchAll(
    "SELECT id, patient_id, description, status FROM patient_suggested_treatments
     WHERE patient_id IN (17, 177)
       AND (status IS NULL OR status = '' OR status = 'pending')
     ORDER BY id"
);
echo 'pending count: ' . count($pending) . "\n";
foreach ($pending as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
