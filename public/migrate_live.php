<?php
/**
 * Browser URL runner for live DB update.
 *
 * Live example:
 *   https://your-domain.com/migrate_live.php?key=YOUR_MIGRATE_KEY
 *
 * Local example:
 *   http://localhost/roots_project/public/migrate_live.php?key=YOUR_MIGRATE_KEY
 *
 * Set MIGRATE_KEY in `.env` before opening this URL.
 * After success: delete this file OR clear MIGRATE_KEY for safety.
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

$expected = trim((string) Env::get('MIGRATE_KEY', ''));
$given = trim((string) ($_GET['key'] ?? ''));

if ($expected === '') {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Migrate blocked</title></head><body style="font-family:sans-serif;max-width:720px;margin:40px auto;padding:0 16px">';
    echo '<h1>MIGRATE_KEY missing</h1>';
    echo '<p>Live <code>.env</code> ma aa line add karo:</p>';
    echo '<pre>MIGRATE_KEY=RootsLiveUpdate2026</pre>';
    echo '<p>Pachi aa URL open karo:</p>';
    echo '<pre>' . htmlspecialchars(rtrim((string) App::config('app')['url'], '/') . '/migrate_live.php?key=RootsLiveUpdate2026') . '</pre>';
    echo '</body></html>';
    exit;
}

if ($given === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Unauthorized</title></head><body style="font-family:sans-serif;max-width:720px;margin:40px auto">';
    echo '<h1>Unauthorized</h1><p>Invalid or missing <code>?key=</code>.</p></body></html>';
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

$appUrl = rtrim((string) (App::config('app')['url'] ?? ''), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live DB Update</title>
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
    <h1>Roots HMS — Live DB Update</h1>
    <p class="<?= $ok ? 'ok' : 'err' ?>"><?= $ok ? 'SUCCESS — database updated.' : 'FAILED — check log below.' ?></p>
    <pre><?php foreach ($lines as $line): ?><?= htmlspecialchars((string) $line) . "\n" ?><?php endforeach; ?></pre>
    <div class="warn">
        <strong>Security:</strong> Update successful pachi <code>public/migrate_live.php</code> delete kari do,
        athva <code>.env</code> mathi <code>MIGRATE_KEY</code> remove/change kari do.
    </div>
    <?php if ($appUrl !== ''): ?>
        <a class="btn" href="<?= htmlspecialchars($appUrl . '/login') ?>">Open Login</a>
    <?php endif; ?>
</div>
</body>
</html>
