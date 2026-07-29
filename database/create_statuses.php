<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();

echo "Creating appointment_statuses table...\n";

$sql = "CREATE TABLE IF NOT EXISTS appointment_statuses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  color VARCHAR(20) NOT NULL DEFAULT '#00AEEF',
  badge_class VARCHAR(50) NOT NULL DEFAULT 'primary',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  INDEX idx_status_active (is_active),
  INDEX idx_status_sort (sort_order)
) ENGINE=InnoDB;";

Database::query($sql);

$now = date('Y-m-d H:i:s');

$defaultStatuses = [
    ['Scheduled', 'scheduled', '#3B82F6', 'primary', 1],
    ['Confirmed', 'confirmed', '#0EA5E9', 'info', 2],
    ['Waiting', 'waiting', '#F59E0B', 'warning', 3],
    ['Checked In', 'checked_in', '#8B5CF6', 'warning', 4],
    ['With Doctor', 'with_doctor', '#6366F1', 'accent', 5],
    ['Completed', 'completed', '#22C55E', 'success', 6],
    ['Cancelled', 'cancelled', '#94A3B8', 'danger', 7],
    ['No Show', 'no_show', '#EF4444', 'secondary', 8],
];

foreach ($defaultStatuses as [$name, $slug, $color, $badge, $sort]) {
    $exists = Database::fetch('SELECT id FROM appointment_statuses WHERE slug = ?', [$slug]);
    if (!$exists) {
        Database::insert('appointment_statuses', [
            'name' => $name,
            'slug' => $slug,
            'color' => $color,
            'badge_class' => $badge,
            'sort_order' => $sort,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

echo "Appointment Statuses Table & Seed completed successfully.\n";
