<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Print') ?> | <?= e(branding('hospital_name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <style>
        body { background: #fff; color: #1e293b; }
        .print-header { border-bottom: 2px solid #00AEEF; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        @media print { .no-print { display: none !important; } body { padding: 0 !important; } }
    </style>
</head>
<body class="p-4">
    <div class="print-header d-flex justify-content-between align-items-start no-print">
        <div>
            <?php if (branding('logo_main') || logo_url('logo_main')): ?>
                <img src="<?= e(logo_url('logo_main')) ?>" alt="Logo" style="max-height:65px">
            <?php else: ?>
                <h1 class="h4 m-0 fw-bold text-primary"><?= e(branding('hospital_name')) ?></h1>
            <?php endif; ?>
            <div class="mt-2 small text-muted">
                <strong><?= e(branding('hospital_name')) ?></strong><br>
                <?= e(branding('hospital_address')) ?><br>
                <?php if (branding('hospital_phone')): ?>Phone: <?= e(branding('hospital_phone')) ?><?php endif; ?>
                <?php if (branding('hospital_phone') && branding('hospital_email')): ?> · <?php endif; ?>
                <?php if (branding('hospital_email')): ?>Email: <?= e(branding('hospital_email')) ?><?php endif; ?>
            </div>
        </div>
        <div>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Report</button>
        </div>
    </div>
    <?= $content ?? '' ?>
</body>
</html>
