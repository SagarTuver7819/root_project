<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($title ?? 'Login') . ' | ' . branding('hospital_name')) ?></title>
    <link rel="icon" href="<?= e(logo_url('favicon')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= e(branding('primary_color') ?: '#00AEEF') ?>;
            --primary-dark: #0090C5;
            --secondary-color: <?= e(branding('secondary_color') ?: '#58595B') ?>;
        }
    </style>
</head>
<body class="auth-body">
    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-brand text-center mb-4">
                <img src="<?= e(logo_url('logo_login')) ?>" alt="<?= e(branding('hospital_name')) ?>" class="auth-logo">
            </div>
            <?= $content ?? '' ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <?php flash_toastr(); ?>
</body>
</html>
