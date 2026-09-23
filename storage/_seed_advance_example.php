<?php
/**
 * Local demo: 2-visit treatment with Collect Advance (₹2000 of ₹4500).
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();

$now = date('Y-m-d H:i:s');

// Prefer an existing active doctor
$doctor = Database::fetch(
    'SELECT id, name FROM doctors WHERE deleted_at IS NULL AND is_active = 1 ORDER BY id ASC LIMIT 1'
);
if (!$doctor) {
    fwrite(STDERR, "No doctor found.\n");
    exit(1);
}
$doctorId = (int) $doctor['id'];

// Prefer existing patient "demo" style, else latest patient, else create
$patient = Database::fetch(
    "SELECT id, patient_code, name, mobile FROM patients
     WHERE deleted_at IS NULL AND (name LIKE '%Advance Demo%' OR patient_code = 'PATADV01')
     LIMIT 1"
);
if (!$patient) {
    $patient = Database::fetch(
        'SELECT id, patient_code, name, mobile FROM patients WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1'
    );
}

if (!$patient) {
    $patientId = Database::insert('patients', [
        'patient_code' => 'PATADV01',
        'name' => 'Advance Demo Patient',
        'mobile' => '9000000001',
        'gender' => 'Female',
        'age' => 30,
        'registration_date' => date('Y-m-d'),
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $patient = Database::fetch('SELECT id, patient_code, name, mobile FROM patients WHERE id = ?', [$patientId]);
}

$patientId = (int) $patient['id'];

// Ensure schema columns exist (via PatientController helper logic inline)
$cols = [
    'status' => "VARCHAR(30) NOT NULL DEFAULT 'pending'",
    'amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
    'paid_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
    'payment_status' => "VARCHAR(30) NOT NULL DEFAULT 'pending'",
    'teeth' => 'VARCHAR(255) NULL',
    'remarks' => 'TEXT NULL',
    'patient_instruction' => 'TEXT NULL',
    'consent_book_number' => 'VARCHAR(100) NULL',
    'completed_at' => 'DATETIME NULL',
    'completed_by' => 'INT UNSIGNED NULL',
    'payment_bill_id' => 'INT UNSIGNED NULL',
    'next_appointment_date' => 'DATE NULL',
    'next_appointment_time' => 'TIME NULL',
];
$table = Database::fetch("SHOW TABLES LIKE 'patient_suggested_treatments'");
if (!$table) {
    Database::connection()->exec(
        "CREATE TABLE IF NOT EXISTS patient_suggested_treatments (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          patient_id INT UNSIGNED NOT NULL,
          sort_order INT UNSIGNED NOT NULL DEFAULT 1,
          description VARCHAR(255) NOT NULL,
          doctor_id INT UNSIGNED NULL,
          appointment_id INT UNSIGNED NULL,
          teeth VARCHAR(255) NULL,
          created_by INT UNSIGNED NULL,
          updated_by INT UNSIGNED NULL,
          created_at DATETIME NULL,
          updated_at DATETIME NULL,
          INDEX idx_pst_patient (patient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}
foreach ($cols as $col => $def) {
    $exists = Database::fetch("SHOW COLUMNS FROM patient_suggested_treatments LIKE '{$col}'");
    if (!$exists) {
        Database::query("ALTER TABLE patient_suggested_treatments ADD COLUMN `{$col}` {$def}");
    }
}

// Remove previous demo lines for this example
Database::query(
    "DELETE FROM patient_suggested_treatments
     WHERE patient_id = ? AND description LIKE 'fcc (2-visit demo)%'",
    [$patientId]
);

// Line 1: full amount 4500, already collected advance 2000 → shows Paid/Due + Collect remaining
$id1 = Database::insert('patient_suggested_treatments', [
    'patient_id' => $patientId,
    'sort_order' => 1,
    'description' => 'fcc (2-visit demo)',
    'doctor_id' => $doctorId,
    'teeth' => 'UR6',
    'amount' => 4500.00,
    'paid_amount' => 2000.00,
    'payment_status' => 'partial',
    'status' => 'pending',
    'remarks' => 'Local example: Visit 1 advance ₹2000 collected. Visit 2 ma Complete + remaining ₹2500.',
    'created_at' => $now,
    'updated_at' => $now,
]);

// Line 2: fresh line — amount set, no payment yet → Collect Advance button ready
$id2 = Database::insert('patient_suggested_treatments', [
    'patient_id' => $patientId,
    'sort_order' => 2,
    'description' => 'fcc (2-visit demo) — try Collect Advance',
    'doctor_id' => $doctorId,
    'teeth' => 'UL6',
    'amount' => 4500.00,
    'paid_amount' => 0,
    'payment_status' => 'pending',
    'status' => 'pending',
    'remarks' => 'Local example: pehla Collect Advance dabavi ₹2000 try karo.',
    'created_at' => $now,
    'updated_at' => $now,
]);

$base = rtrim((string) (App::config('app')['url'] ?? 'http://localhost/roots_project/public'), '/');
$url = $base . '/patients/' . $patientId . '?tab=plan';

echo "OK — Advance example seeded\n";
echo "Patient: {$patient['patient_code']} — {$patient['name']} (#{$patientId})\n";
echo "Doctor: " . ($doctor['name'] ?? '') . " (#{$doctorId})\n";
echo "Row #{$id1}: fcc (2-visit demo) — Amount 4500 / Paid 2000 / Due 2500\n";
echo "Row #{$id2}: try Collect Advance — Amount 4500 / Paid 0\n";
echo "Open: {$url}\n";
