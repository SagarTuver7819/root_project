<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($title ?? 'Login') . ' | ' . branding('hospital_name')) ?></title>
    <link rel="icon" href="<?= e(logo_url('favicon')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/css/app.css'))) ?>" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= e(branding('primary_color') ?: '#00AEEF') ?>;
            --primary-dark: #0090C5;
            --secondary-color: <?= e(branding('secondary_color') ?: '#58595B') ?>;

            --login-logo-opacity: <?= e((string) branding('login_logo_opacity', 0.22)) ?>;
            --login-logo-scale: <?= e((string) branding('login_logo_scale', 1.00)) ?>;
            --login-bg-overlay-opacity: <?= e((string) branding('login_bg_overlay_opacity', 0.12)) ?>;
        }
    </style>
</head>
<body class="auth-body">
    <div class="auth-ambient" aria-hidden="true">
        <span class="auth-bg-overlay"></span>
        <span class="auth-blob auth-blob-1"></span>
        <span class="auth-blob auth-blob-2"></span>
        <span class="auth-blob auth-blob-3"></span>
        <span class="auth-blob auth-blob-4"></span>
        <i class="bi bi-braces auth-bg-icon auth-bg-dental" aria-hidden="true"></i>
        <i class="bi bi-clipboard2-pulse auth-bg-icon auth-bg-records" aria-hidden="true"></i>
        <i class="bi bi-droplet auth-bg-icon auth-bg-med" aria-hidden="true"></i>
        <i class="bi bi-capsule auth-bg-icon auth-bg-medicine" aria-hidden="true"></i>
        <span class="auth-mesh"></span>
    </div>

    <main class="auth-center">
        <div class="auth-form-frame auth-rise" style="--d:.18s">
            <?= $content ?? '' ?>
        </div>

        <a class="auth-dev-credit auth-rise" style="--d:.32s"
           href="https://oceaninfotech.co.in/" target="_blank" rel="noopener noreferrer"
           aria-label="Ocean Infotech website">
            <span class="auth-dev-label">Design &amp; Developed By</span>
            <strong class="auth-dev-name">Ocean Infotech</strong>
        </a>

        <p class="auth-copy auth-rise" style="--d:.42s">© <?= date('Y') ?> <?= e(branding('hospital_name') ?: 'Roots Dentistry') ?></p>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/js/app.js'))) ?>"></script>
    <?php flash_toastr(); ?>
</body>
</html>
