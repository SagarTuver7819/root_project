<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<?php $treatment = $treatment ?? []; $isEdit = !empty($treatment['id']); ?>
<form method="post" action="<?= $isEdit ? app_url('treatment-masters/' . $treatment['id']) : app_url('treatment-masters') ?>" class="ajax-form">
<?= csrf_field() ?>
<div class="card content-card"><div class="card-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Name</label><input class="form-control" type="text" name="name" value="<?= e(old('name', $treatment['name'] ?? '')) ?>"></div><div class="col-md-4"><label class="form-label">Category</label><input class="form-control" type="text" name="category" value="<?= e(old('category', $treatment['category'] ?? '')) ?>"></div><div class="col-md-4"><label class="form-label">Default Price</label><input class="form-control" type="number" step="0.01" name="default_price" value="<?= e(old('default_price', $treatment['default_price'] ?? '')) ?>"></div><div class="col-md-4"><label class="form-label">Estimated Sessions</label><input class="form-control" type="number" step="0.01" name="estimated_sessions" value="<?= e(old('estimated_sessions', $treatment['estimated_sessions'] ?? '')) ?>"></div><div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e(old('description', $treatment['description'] ?? '')) ?></textarea></div>
<div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= (int) old('is_active', $treatment['is_active'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label">Active</label></div></div>
</div><button class="btn btn-primary mt-4">Save</button><a class="btn btn-light mt-4" href="<?= app_url('treatment-masters') ?>">Cancel</a></div></div></form>
