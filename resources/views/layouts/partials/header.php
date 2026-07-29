<?php $user = auth_user(); $role = \App\Core\Auth::primaryRole(); ?>
<header class="app-header">
    <div class="header-left">
        <button type="button" class="btn btn-icon" id="sidebarToggle" title="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="header-title-wrap">
            <h1 class="page-title"><?= e($pageTitle ?? $title ?? '') ?></h1>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= app_url('dashboard') ?>">Home</a></li>
                    <li class="breadcrumb-item active"><?= e($pageTitle ?? $title ?? '') ?></li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="header-right">
        <div class="header-search d-none d-md-block">
            <i class="bi bi-search"></i>
            <input type="text" id="quickPatientSearch" class="form-control form-control-sm" placeholder="Mobile / name / ID → Patient History">
        </div>
        <div class="dropdown">
            <button class="btn btn-profile dropdown-toggle" data-bs-toggle="dropdown">
                <span class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></span>
                <span class="profile-meta d-none d-sm-inline">
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <small><?= e($role['name'] ?? 'User') ?></small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="<?= app_url('profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="<?= app_url('change-password') ?>"><i class="bi bi-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= app_url('logout') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
