<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card"><div class="card-body"><table class="table table-hover"><thead><tr><th>Role</th><th>Permissions</th><th>Actions</th></tr></thead><tbody><?php foreach(($roles??[]) as $role): ?><tr><td><?= e($role['name']??'') ?></td><td><?= e($role['permission_count']??0) ?></td><td><a class="btn btn-sm btn-light" href="<?= app_url('roles/'.$role['id'].'/edit') ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div></div>
