<?php
/**
 * Create patient_clinical_charts table if missing.
 * Usage: php database/migrate_clinical_charts.php
 */
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();

$sql = file_get_contents(__DIR__ . '/migrations/patient_clinical_charts.sql');
if ($sql === false) {
    fwrite(STDERR, "Migration file not found.\n");
    exit(1);
}

try {
    Database::connection()->exec($sql);
    echo "OK: patient_clinical_charts ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
