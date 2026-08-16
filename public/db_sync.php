<?php
/**
 * Live / local DB sync — open in browser.
 *
 * Local:
 *   http://localhost/roots_project/public/db_sync.php?key=RootsDbSync2026
 *
 * Live:
 *   https://roots.oceanhub.co.in/public/db_sync.php?key=RootsDbSync2026
 *   (or without /public if domain already points to public folder)
 *
 * Optional: .env ma MIGRATE_KEY set hoy to e pan key chalse.
 * Sync successful pachi aa file delete kari do.
 */
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Env;

try {
    App::bootstrap();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Bootstrap failed</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

$given = trim((string) ($_GET['key'] ?? ''));
$envKey = trim((string) Env::get('MIGRATE_KEY', ''));
$defaultKey = 'RootsDbSync2026';
$allowed = array_values(array_filter([$envKey, $defaultKey]));

$appUrl = rtrim((string) (App::config('app')['url'] ?? ''), '/');
$syncUrl = $appUrl . '/db_sync.php?key=' . rawurlencode($defaultKey);

if ($given === '') {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>DB Sync</title></head>';
    echo '<body style="font-family:Segoe UI,sans-serif;max-width:760px;margin:40px auto;padding:0 16px">';
    echo '<h1>DB Sync key required</h1>';
    echo '<p>Aa URL open karo:</p>';
    echo '<pre>' . htmlspecialchars($syncUrl) . '</pre>';
    echo '</body></html>';
    exit;
}

$okKey = false;
foreach ($allowed as $key) {
    if (hash_equals($key, $given)) {
        $okKey = true;
        break;
    }
}

if (!$okKey) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Unauthorized</title></head>';
    echo '<body style="font-family:Segoe UI,sans-serif;max-width:760px;margin:40px auto">';
    echo '<h1>Unauthorized</h1><p>Invalid <code>?key=</code>.</p></body></html>';
    exit;
}

require dirname(__DIR__) . '/database/live_update.inc.php';

$ok = true;
$lines = [];
try {
    $lines = roots_live_update();
} catch (Throwable $e) {
    $ok = false;
    $lines[] = 'ERROR: ' . $e->getMessage();
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DB Sync</title>
    <style>
        body { font-family: Segoe UI, sans-serif; background:#f5f7f9; color:#1a1a1a; margin:0; padding:24px; }
        .box { max-width:860px; margin:0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; }
        h1 { margin:0 0 8px; font-size:1.4rem; }
        .ok { color:#15803d; font-weight:700; }
        .err { color:#b91c1c; font-weight:700; }
        pre { background:#0f172a; color:#e2e8f0; padding:16px; border-radius:10px; overflow:auto; line-height:1.45; }
        .warn { background:#fff7ed; border:1px solid #fdba74; color:#9a3412; padding:12px 14px; border-radius:8px; margin-top:16px; }
        a.btn { display:inline-block; margin-top:14px; background:#00AEEF; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600; }
    </style>
</head>
<body>
<div class="box">
    <h1>Roots Dentistry — DB Sync</h1>
    <p class="<?= $ok ? 'ok' : 'err' ?>"><?= $ok ? 'SUCCESS — database synced.' : 'FAILED — log check karo.' ?></p>
    <pre><?php foreach ($lines as $line): ?><?= htmlspecialchars((string) $line) . "\n" ?><?php endforeach; ?></pre>
    <div class="warn">
        <strong>Security:</strong> Sync successful pachi <code>public/db_sync.php</code> delete kari do.
    </div>
    <?php if ($appUrl !== ''): ?>
        <a class="btn" href="<?= htmlspecialchars($appUrl . '/login') ?>">Open Login</a>
    <?php endif; ?>
</div>
</body>
</html>
