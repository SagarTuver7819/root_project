<?php
$actions = '<a href="' . app_url('change-password') . '" class="btn btn-primary">Change Password</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card"><div class="card-body"><h3 class="h5"><?= e($user['name'] ?? '') ?></h3><div class="text-muted"><?= e($user['email'] ?? '') ?></div><hr><div class="row g-3"><div class="col-md-4"><strong>Username</strong><div><?= e($user['username'] ?? '-') ?></div></div><div class="col-md-4"><strong>Phone</strong><div><?= e($user['phone'] ?? '-') ?></div></div><div class="col-md-4"><strong>Role</strong><div><?= e(is_array($role ?? null) ? ($role['name'] ?? '-') : ($role ?? '-')) ?></div></div></div></div></div>
