<?php
$actions = '<a href="' . app_url('profile') . '" class="btn btn-light">Profile</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card"><div class="card-body"><form method="post" action="<?= app_url('change-password') ?>" class="ajax-form"><?= csrf_field() ?><div class="row g-3"><div class="col-md-4"><label class="form-label">Current Password</label><input class="form-control" type="password" name="current_password" required></div><div class="col-md-4"><label class="form-label">New Password</label><input class="form-control" type="password" name="password" required></div><div class="col-md-4"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" required></div></div><div class="mt-4 d-flex gap-2"><button type="submit" class="btn btn-primary">Update Password</button><a class="btn btn-light" href="<?= app_url('profile') ?>">Cancel</a></div></form></div></div>
