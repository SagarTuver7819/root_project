<?php
/**
 * CLI: php database/migrate_live.php
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;

App::bootstrap();
require __DIR__ . '/live_update.inc.php';

foreach (roots_live_update() as $line) {
    echo $line . PHP_EOL;
}
