<?php
require __DIR__ . '/../app/Core/Env.php';
require __DIR__ . '/../app/Core/App.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Helpers/helpers.php';

\App\Core\Env::load(__DIR__ . '/../.env');
\App\Core\App::bootstrap();

use App\Core\Database;

$patientId = 15;
$payload = [
    'chief_complaint' => 'pain with upper anterior (persist test)',
    'drug_list' => json_encode([
        'conditions' => ['diabetes', 'blood pressure'],
        'other' => 'thyroid',
        'daily_medicine' => 'metformin',
    ], JSON_UNESCAPED_UNICODE),
    'habit' => json_encode([
        'items' => ['masala'],
        'other' => 'tea',
    ], JSON_UNESCAPED_UNICODE),
    'test_advised' => 'OPG',
    'tooth_notes' => json_encode(['11' => 'note test'], JSON_UNESCAPED_UNICODE),
    'lab_work' => json_encode(['product' => '', 'shade' => '', 'brand' => '', 'lab_name' => ''], JSON_UNESCAPED_UNICODE),
    'implant_work' => json_encode(new stdClass(), JSON_UNESCAPED_UNICODE),
    'updated_by' => 1,
    'updated_at' => date('Y-m-d H:i:s'),
];

$existing = Database::fetch('SELECT id FROM patient_clinical_charts WHERE patient_id = ? LIMIT 1', [$patientId]);
if ($existing) {
    Database::update('patient_clinical_charts', $payload, 'id = :_id', ['_id' => (int) $existing['id']]);
    echo "UPDATED id={$existing['id']}\n";
} else {
    $payload['patient_id'] = $patientId;
    $payload['created_by'] = 1;
    $payload['created_at'] = date('Y-m-d H:i:s');
    $id = Database::insert('patient_clinical_charts', $payload);
    echo "INSERTED id={$id}\n";
}

$row = Database::fetch('SELECT chief_complaint, drug_list, habit, test_advised, tooth_notes FROM patient_clinical_charts WHERE patient_id = ?', [$patientId]);
echo "READ BACK:\n";
print_r($row);
echo "upload_url sample: " . upload_url('patients/15/demo.jpg') . "\n";
echo "upload_path: " . upload_path('patients/15') . "\n";
