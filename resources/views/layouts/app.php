<?php
$user = auth_user();
$role = \App\Core\Auth::primaryRole();
$primary = branding('primary_color') ?: '#00AEEF';
$secondary = branding('secondary_color') ?: '#58595B';
$sidebarBg = branding('sidebar_color') ?: '#111111';
$sidebarText = branding('sidebar_text_color') ?: '#FFFFFF';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($title ?? $pageTitle ?? 'HMS') . ' | ' . branding('hospital_name')) ?></title>
    <link rel="icon" href="<?= e(logo_url('favicon')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/css/app.css'))) ?>" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= e($primary) ?>;
            --primary-dark: #0090C5;
            --primary-light: #E6F7FD;
            --secondary-color: <?= e($secondary) ?>;
            --accent-color: #BCBEC0;
            --background-color: #F5F7F9;
            --surface-color: #FFFFFF;
            --text-primary: #1A1A1A;
            --text-secondary: #6C757D;
            --border-color: #E9ECEF;
            --sidebar-bg: <?= e($sidebarBg) ?>;
            --sidebar-text: <?= e($sidebarText) ?>;
            --sidebar-width: 270px;
            --sidebar-collapsed-width: 78px;
        }
    </style>
</head>
<body>
<div class="app-wrapper" id="appWrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="app-main">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="app-content">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
    window.APP_URL = <?= json_encode(rtrim(app_url(), '/')) ?>;
    window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
</script>
<script src="<?= e(asset('js/datatable-init.js')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/js/datatable-init.js'))) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) @filemtime(App\Core\App::basePath('public/assets/js/app.js'))) ?>"></script>
<?php flash_toastr(); ?>
</body>
</html>
