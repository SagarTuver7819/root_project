<?php
/**
 * Database seeder – run once after schema.sql
 * Usage: php database/seed.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();

$pdo = Database::connection();
$now = date('Y-m-d H:i:s');

echo "Seeding Roots Dental HMS...\n";

// Roles
$roles = [
    ['Super Admin', 'super_admin', 'Full system access'],
    ['Admin', 'admin', 'Hospital administrator'],
    ['Receptionist', 'receptionist', 'Front desk & appointments'],
    ['Doctor', 'doctor', 'Clinical operations'],
    ['Accounts', 'accounts', 'Billing & payments'],
    ['Inventory', 'inventory', 'Inventory & purchase staff'],
];

$roleIds = [];
foreach ($roles as [$name, $slug, $desc]) {
    $existing = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$slug]);
    if ($existing) {
        $roleIds[$slug] = (int) $existing['id'];
        continue;
    }
    $roleIds[$slug] = Database::insert('roles', [
        'name' => $name,
        'slug' => $slug,
        'description' => $desc,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// Permissions from config
$permMap = require dirname(__DIR__) . '/config/permissions.php';
$permissionIds = [];
foreach ($permMap as $module => $actions) {
    foreach ($actions as $action) {
        $slug = $module . '.' . $action;
        $existing = Database::fetch('SELECT id FROM permissions WHERE slug = ?', [$slug]);
        if ($existing) {
            $permissionIds[$slug] = (int) $existing['id'];
            continue;
        }
        $permissionIds[$slug] = Database::insert('permissions', [
            'module' => $module,
            'action' => $action,
            'slug' => $slug,
            'name' => ucwords(str_replace('_', ' ', $module)) . ' - ' . ucwords(str_replace('_', ' ', $action)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// Assign all permissions to super_admin & admin
foreach (['super_admin', 'admin'] as $slug) {
    foreach ($permissionIds as $pid) {
        Database::query(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
            [$roleIds[$slug], $pid]
        );
    }
}

// Receptionist permissions — front desk + billing collection + patient history
Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleIds['receptionist']]);
$receptionPerms = [
    'dashboard.view',
    'calendar.view', 'calendar.add', 'calendar.edit',
    'appointments.view', 'appointments.add', 'appointments.edit', 'appointments.status_change', 'appointments.print',
    'queue.view', 'queue.status_change',
    'patients.view', 'patients.add', 'patients.edit', 'patients.print',
    'follow_ups.view', 'follow_ups.add', 'follow_ups.edit', 'follow_ups.status_change',
    'visits.view', 'visits.add',
    'prescriptions.view', 'prescriptions.print',
    'doctors.view', 'reference_doctors.view', 'treatment_masters.view',
    'billing.view', 'billing.add', 'billing.edit', 'billing.print',
    'payments.view', 'payments.add', 'payments.print',
    'outstanding.view', 'outstanding.export',
];
foreach ($receptionPerms as $slug) {
    if (isset($permissionIds[$slug])) {
        Database::query('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleIds['receptionist'], $permissionIds[$slug]]);
    }
}

// Doctor permissions — own clinical work + limited billing/invoice view
Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleIds['doctor']]);
$doctorPerms = [
    'dashboard.view',
    'calendar.view', 'calendar.add', 'calendar.edit',
    'appointments.view', 'appointments.add', 'appointments.edit', 'appointments.status_change', 'appointments.print',
    'queue.view', 'queue.status_change',
    'patients.view', 'patients.print',
    'visits.view', 'visits.add', 'visits.edit',
    'treatments.view', 'treatments.add', 'treatments.edit', 'treatments.status_change',
    'treatment_sessions.view', 'treatment_sessions.add', 'treatment_sessions.edit',
    'prescriptions.view', 'prescriptions.add', 'prescriptions.edit', 'prescriptions.print',
    'follow_ups.view', 'follow_ups.add', 'follow_ups.edit', 'follow_ups.status_change',
    'medicine_masters.view', 'treatment_masters.view',
    'doctors.view',
    'billing.view', 'billing.print',
    'payments.view',
];
foreach ($doctorPerms as $slug) {
    if (isset($permissionIds[$slug])) {
        Database::query('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleIds['doctor'], $permissionIds[$slug]]);
    }
}

// Accounts — billing/payments focused (+ masters needed on bill forms)
Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleIds['accounts']]);
$accountsPerms = [
    'dashboard.view',
    'patients.view', 'patients.print',
    'doctors.view', 'treatment_masters.view',
    'billing.view', 'billing.add', 'billing.edit', 'billing.print', 'billing.approve',
    'payments.view', 'payments.add', 'payments.print', 'payments.edit',
    'outstanding.view', 'outstanding.export',
    'reports.view', 'reports.export', 'reports.print',
];
foreach ($accountsPerms as $slug) {
    if (isset($permissionIds[$slug])) {
        Database::query('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleIds['accounts'], $permissionIds[$slug]]);
    }
}

// Inventory staff
Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$roleIds['inventory']]);
$invPerms = [
    'dashboard.view',
    'inventory.view', 'inventory.add', 'inventory.edit',
    'suppliers.view', 'suppliers.add', 'suppliers.edit',
    'purchases.view', 'purchases.add', 'purchases.edit',
    'reports.view', 'reports.export',
];
foreach ($invPerms as $slug) {
    if (isset($permissionIds[$slug])) {
        Database::query('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleIds['inventory'], $permissionIds[$slug]]);
    }
}

// Super Admin user
$admin = Database::fetch('SELECT id FROM users WHERE username = ?', ['admin']);
if (!$admin) {
    $adminId = Database::insert('users', [
        'name' => 'Super Admin',
        'username' => 'admin',
        'email' => 'admin@rootsdental.local',
        'password' => password_hash('Admin@123', PASSWORD_BCRYPT),
        'phone' => '9999999999',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$adminId, $roleIds['super_admin']]);
    echo "Admin user created: admin / Admin@123\n";
} else {
    $adminId = (int) $admin['id'];
    echo "Admin user already exists\n";
}

// Receptionist demo
$rec = Database::fetch('SELECT id FROM users WHERE username = ?', ['reception']);
if (!$rec) {
    $recId = Database::insert('users', [
        'name' => 'Front Desk',
        'username' => 'reception',
        'email' => 'reception@rootsdental.local',
        'password' => password_hash('Reception@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$recId, $roleIds['receptionist']]);
} else {
    $recId = (int) $rec['id'];
    Database::update('users', [
        'password' => password_hash('Reception@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'updated_at' => $now,
    ], 'id = :_id', ['_id' => $recId]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$recId, $roleIds['receptionist']]);
}

// Hospital Admin (Admin role — not super admin)
$hospAdmin = Database::fetch('SELECT id FROM users WHERE username = ?', ['hospitaladmin']);
if (!$hospAdmin) {
    $hospAdminId = Database::insert('users', [
        'name' => 'Hospital Admin',
        'username' => 'hospitaladmin',
        'email' => 'admin.ops@rootsdental.local',
        'password' => password_hash('Admin@123', PASSWORD_BCRYPT),
        'phone' => '9999999998',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$hospAdminId, $roleIds['admin']]);
} else {
    $hospAdminId = (int) $hospAdmin['id'];
    Database::update('users', [
        'password' => password_hash('Admin@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'updated_at' => $now,
    ], 'id = :_id', ['_id' => $hospAdminId]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$hospAdminId, $roleIds['admin']]);
}

// Accounts demo user
$acc = Database::fetch('SELECT id FROM users WHERE username = ?', ['accounts']);
if (!$acc) {
    $accId = Database::insert('users', [
        'name' => 'Accounts Desk',
        'username' => 'accounts',
        'email' => 'accounts@rootsdental.local',
        'password' => password_hash('Accounts@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$accId, $roleIds['accounts']]);
} else {
    $accId = (int) $acc['id'];
    Database::update('users', [
        'password' => password_hash('Accounts@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'updated_at' => $now,
    ], 'id = :_id', ['_id' => $accId]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$accId, $roleIds['accounts']]);
}

// Inventory demo user
$invUser = Database::fetch('SELECT id FROM users WHERE username = ?', ['inventory']);
if (!$invUser) {
    $invUserId = Database::insert('users', [
        'name' => 'Inventory Staff',
        'username' => 'inventory',
        'email' => 'inventory@rootsdental.local',
        'password' => password_hash('Inventory@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$invUserId, $roleIds['inventory']]);
} else {
    $invUserId = (int) $invUser['id'];
    Database::update('users', [
        'password' => password_hash('Inventory@123', PASSWORD_BCRYPT),
        'is_active' => 1,
        'updated_at' => $now,
    ], 'id = :_id', ['_id' => $invUserId]);
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$invUserId, $roleIds['inventory']]);
}

// Doctor login users (linked to doctors.user_id)
$doctorUsers = [
    ['dr.sharma', 'Dr. Sharma', 'dr.sharma@rootsdental.local', '9876500001', 'DOC001'],
    ['dr.patel', 'Dr. Patel', 'dr.patel@rootsdental.local', '9876500002', 'DOC002'],
    ['dr.mehta', 'Dr. Mehta', 'dr.mehta@rootsdental.local', '9876500003', 'DOC003'],
];
foreach ($doctorUsers as [$username, $name, $email, $phone, $doctorCode]) {
    $user = Database::fetch('SELECT id FROM users WHERE username = ?', [$username]);
    if (!$user) {
        $userId = Database::insert('users', [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => password_hash('Doctor@123', PASSWORD_BCRYPT),
            'phone' => $phone,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    } else {
        $userId = (int) $user['id'];
        Database::update('users', [
            'name' => $name,
            'email' => $email,
            'password' => password_hash('Doctor@123', PASSWORD_BCRYPT),
            'phone' => $phone,
            'is_active' => 1,
            'updated_at' => $now,
        ], 'id = :_id', ['_id' => $userId]);
    }
    Database::query('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)', [$userId, $roleIds['doctor']]);
    $doc = Database::fetch('SELECT id FROM doctors WHERE doctor_code = ? AND deleted_at IS NULL', [$doctorCode]);
    if ($doc) {
        Database::update('doctors', ['user_id' => $userId, 'updated_at' => $now], 'id = :_id', ['_id' => (int) $doc['id']]);
    }
}

// Branding settings — Roots Dentistry (Rajkot)
$settings = [
    'hospital_name' => 'Roots Dentistry',
    'hospital_tagline' => 'Oral Surgeon | Implants | Surgery | Smile Design',
    'logo_main' => 'branding/logo-main.jpg',
    'logo_login' => 'branding/logo-login.jpg',
    'logo_sidebar' => 'branding/logo-sidebar.jpg',
    'logo_collapsed' => 'branding/logo-collapsed.png',
    'favicon' => 'branding/logo-collapsed.png',
    'primary_color' => '#00AEEF',
    'secondary_color' => '#58595B',
    'sidebar_color' => '#111111',
    'sidebar_text_color' => '#FFFFFF',
    'hospital_address' => '208 Jasal Complex, Nanavati Circle, 150 Feet Ring Rd, Rajkot, Gujarat 360007',
    'hospital_phone' => '083477 60330',
    'hospital_email' => 'info@rootsdentistry.in',
    'booking_amount' => '300',
    'booking_validity_months' => '3',
];
foreach ($settings as $key => $value) {
    $exists = Database::fetch('SELECT id FROM settings WHERE `key` = ?', [$key]);
    $group = in_array($key, ['booking_amount', 'booking_validity_months'], true) ? 'billing' : 'branding';
    if ($exists) {
        Database::update('settings', ['value' => $value, 'updated_at' => $now], 'id = :_id', ['_id' => $exists['id']]);
    } else {
        Database::insert('settings', [
            'key' => $key,
            'value' => $value,
            'group_name' => $group,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// Sample doctors
$doctors = [
    ['DOC001', 'Dr. Sharma', '9876500001', 'Oral Surgery', 'MDS'],
    ['DOC002', 'Dr. Patel', '9876500002', 'Implantology', 'MDS'],
    ['DOC003', 'Dr. Mehta', '9876500003', 'Orthodontics', 'MDS'],
];
foreach ($doctors as [$code, $name, $mobile, $spec, $qual]) {
    if (!Database::fetch('SELECT id FROM doctors WHERE doctor_code = ?', [$code])) {
        $docId = Database::insert('doctors', [
            'doctor_code' => $code,
            'name' => $name,
            'mobile' => $mobile,
            'qualification' => $qual,
            'specialization' => $spec,
            'consultation_fee' => 500,
            'slot_duration' => 30,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        // Mon-Sat 10:00-18:00, break 13:00-14:00
        for ($d = 1; $d <= 6; $d++) {
            Database::insert('doctor_schedules', [
                'doctor_id' => $docId,
                'day_of_week' => $d,
                'start_time' => '10:00:00',
                'end_time' => '18:00:00',
                'break_start' => '13:00:00',
                'break_end' => '14:00:00',
                'slot_duration' => 30,
                'is_off' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        Database::insert('doctor_schedules', [
            'doctor_id' => $docId,
            'day_of_week' => 0,
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'slot_duration' => 30,
            'is_off' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// Treatment masters
$treatments = [
    ['Root Canal', 'Endodontics', 5000, 3],
    ['Implant', 'Implantology', 35000, 4],
    ['Extraction', 'Surgery', 1500, 1],
    ['Filling', 'Restorative', 1200, 1],
    ['Crown', 'Prosthodontics', 8000, 2],
    ['Bridge', 'Prosthodontics', 15000, 3],
    ['Scaling', 'Periodontics', 1500, 1],
    ['Denture', 'Prosthodontics', 12000, 3],
    ['Orthodontics', 'Orthodontics', 45000, 12],
];
foreach ($treatments as [$name, $cat, $price, $sessions]) {
    if (!Database::fetch('SELECT id FROM treatment_masters WHERE name = ?', [$name])) {
        Database::insert('treatment_masters', [
            'name' => $name,
            'category' => $cat,
            'default_price' => $price,
            'estimated_sessions' => $sessions,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// Medicines
$meds = [
    ['Amoxicillin 500mg', 'Amoxicillin', 'Capsule', '1 Cap', 'TDS', '5 Days', 'After food'],
    ['Ibuprofen 400mg', 'Ibuprofen', 'Tablet', '1 Tab', 'TDS', '3 Days', 'After food'],
    ['Chlorhexidine Mouthwash', 'Chlorhexidine', 'Mouthwash', '10ml', 'BD', '7 Days', 'Do not swallow'],
    ['Metronidazole 400mg', 'Metronidazole', 'Tablet', '1 Tab', 'TDS', '5 Days', 'After food'],
];
foreach ($meds as [$name, $generic, $type, $dose, $freq, $dur, $inst]) {
    if (!Database::fetch('SELECT id FROM medicine_masters WHERE name = ?', [$name])) {
        Database::insert('medicine_masters', [
            'name' => $name,
            'generic_name' => $generic,
            'medicine_type' => $type,
            'default_dosage' => $dose,
            'default_frequency' => $freq,
            'default_duration' => $dur,
            'default_instructions' => $inst,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// Inventory category + sample item
$cat = Database::fetch('SELECT id FROM inventory_categories WHERE name = ?', ['Dental Consumables']);
if (!$cat) {
    $catId = Database::insert('inventory_categories', [
        'name' => 'Dental Consumables',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
} else {
    $catId = (int) $cat['id'];
}

if (!Database::fetch('SELECT id FROM inventory_items WHERE item_code = ?', ['ITM001'])) {
    Database::insert('inventory_items', [
        'item_code' => 'ITM001',
        'name' => 'Composite Filling Material',
        'category_id' => $catId,
        'brand' => '3M',
        'unit' => 'syringe',
        'current_stock' => 25,
        'minimum_stock' => 5,
        'purchase_rate' => 450,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// Seed Sample Patients
$samplePatients = [
    ['PAT001', 'Rajesh Kumar', '9876511101', 'male', '1985-05-12', 41, 'rajesh.kumar@example.com', 'A+'],
    ['PAT002', 'Priya Sharma', '9876511102', 'female', '1992-08-20', 33, 'priya.sharma@example.com', 'B+'],
    ['PAT003', 'Amit Verma', '9876511103', 'male', '1978-11-04', 47, 'amit.verma@example.com', 'O+'],
    ['PAT004', 'Sneha Gupta', '9876511104', 'female', '1996-03-15', 30, 'sneha.gupta@example.com', 'AB+'],
    ['PAT005', 'Vikram Singh', '9876511105', 'male', '1989-09-28', 36, 'vikram.singh@example.com', 'A-'],
    ['PAT006', 'Ananya Roy', '9876511106', 'female', '2001-01-10', 25, 'ananya.roy@example.com', 'O-'],
    ['PAT007', 'Ramesh Patel', '9876511107', 'male', '1965-07-14', 61, 'ramesh.patel@example.com', 'B-'],
];

$patientIds = [];
foreach ($samplePatients as [$code, $name, $mobile, $gender, $dob, $age, $email, $blood]) {
    $existing = Database::fetch('SELECT id FROM patients WHERE patient_code = ?', [$code]);
    if ($existing) {
        $patientIds[$code] = (int) $existing['id'];
    } else {
        $patientIds[$code] = Database::insert('patients', [
            'patient_code' => $code,
            'name' => $name,
            'mobile' => $mobile,
            'gender' => $gender,
            'dob' => $dob,
            'age' => $age,
            'email' => $email,
            'blood_group' => $blood,
            'registration_date' => date('Y-m-d'),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

// Fetch doctor IDs
$docSharma = Database::fetch('SELECT id FROM doctors WHERE doctor_code = ?', ['DOC001'])['id'] ?? 1;
$docPatel = Database::fetch('SELECT id FROM doctors WHERE doctor_code = ?', ['DOC002'])['id'] ?? 2;
$docMehta = Database::fetch('SELECT id FROM doctors WHERE doctor_code = ?', ['DOC003'])['id'] ?? 3;

// Fetch treatment master IDs
$rct = Database::fetch('SELECT id FROM treatment_masters WHERE name = ?', ['Root Canal'])['id'] ?? null;
$implant = Database::fetch('SELECT id FROM treatment_masters WHERE name = ?', ['Implant'])['id'] ?? null;
$scaling = Database::fetch('SELECT id FROM treatment_masters WHERE name = ?', ['Scaling'])['id'] ?? null;
$filling = Database::fetch('SELECT id FROM treatment_masters WHERE name = ?', ['Filling'])['id'] ?? null;
$extraction = Database::fetch('SELECT id FROM treatment_masters WHERE name = ?', ['Extraction'])['id'] ?? null;

$today = date('Y-m-d'); // Today: 2026-07-29

// Seed Today's Appointments with various aspects & statuses
$todayAppointments = [
    // [Code, PatientCode, DoctorId, StartTime, EndTime, Reason, TreatmentId, EntryType, Status]
    ['APT-TODAY-01', 'PAT001', $docSharma, '10:00:00', '10:30:00', 'Severe Toothache in lower molar', $rct, 'appointment', 'completed'],
    ['APT-TODAY-02', 'PAT002', $docSharma, '10:30:00', '11:00:00', 'Follow-up for Dental Crown', $filling, 'appointment', 'with_doctor'],
    ['APT-TODAY-03', 'PAT003', $docSharma, '11:00:00', '11:30:00', 'Routine Dental Cleaning', $scaling, 'walk_in', 'waiting'],
    ['APT-TODAY-04', 'PAT004', $docPatel, '10:00:00', '10:30:00', 'Dental Implant Consultation', $implant, 'appointment', 'completed'],
    ['APT-TODAY-05', 'PAT005', $docPatel, '11:30:00', '12:00:00', 'Wisdom Tooth Extraction Consultation', $extraction, 'appointment', 'checked_in'],
    ['APT-TODAY-06', 'PAT006', $docMehta, '11:00:00', '11:30:00', 'Orthodontic Braces Checkup', null, 'appointment', 'scheduled'],
    ['APT-TODAY-07', 'PAT007', $docMehta, '12:00:00', '12:30:00', 'Denturn Fitting Check', null, 'walk_in', 'cancelled'],
];

foreach ($todayAppointments as [$code, $pCode, $dId, $start, $end, $reason, $tId, $entryType, $status]) {
    $pId = $patientIds[$pCode] ?? null;
    if (!$pId) continue;

    $existing = Database::fetch('SELECT id FROM appointments WHERE appointment_code = ?', [$code]);
    if (!$existing) {
        Database::insert('appointments', [
            'appointment_code' => $code,
            'patient_id' => $pId,
            'doctor_id' => $dId,
            'appointment_date' => $today,
            'start_time' => $start,
            'end_time' => $end,
            'visit_reason' => $reason,
            'treatment_master_id' => $tId,
            'entry_type' => $entryType,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

echo "Seeding completed successfully with Today's Appointments & Sample Patients.\n";

