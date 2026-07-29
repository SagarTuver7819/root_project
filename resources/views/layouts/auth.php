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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/css/app.css'))) ?>" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= e(branding('primary_color') ?: '#00AEEF') ?>;
            --primary-dark: #0090C5;
            --secondary-color: <?= e(branding('secondary_color') ?: '#58595B') ?>;
        }
    </style>
</head>
<body class="auth-body">
    <div class="auth-stage">
        <section class="auth-brand-pane" aria-label="Roots Dentistry">
            <div class="auth-orbit auth-orbit-a"></div>
            <div class="auth-orbit auth-orbit-b"></div>
            <div class="auth-orbit auth-orbit-c"></div>
            <div class="auth-wave" aria-hidden="true"></div>

            <div class="auth-brand-content">
                <img src="<?= e(logo_url('logo_login')) ?>" alt="<?= e(branding('hospital_name')) ?>" class="auth-hero-logo">
                <h1 class="auth-hero-name"><?= e(branding('hospital_name') ?: 'Roots Dentistry') ?></h1>
                <p class="auth-hero-tag"><?= e(branding('hospital_tagline') ?: 'Oral Surgeon · Implants · Surgery · Smile Design') ?></p>
                <div class="auth-hero-line"></div>
                <p class="auth-hero-note">Secure hospital desk access for appointments, clinical care, and billing.</p>
            </div>
        </section>

        <section class="auth-form-pane">
            <div class="auth-form-frame">
                <?= $content ?? '' ?>
            </div>
            <p class="auth-copy">© <?= date('Y') ?> <?= e(branding('hospital_name') ?: 'Roots Dentistry') ?></p>
        </section>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/js/app.js'))) ?>"></script>
    <?php flash_toastr(); ?>
</body>
</html>
