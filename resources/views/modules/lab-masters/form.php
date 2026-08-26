<?php
$actions = '<a class="btn btn-light" href="' . app_url('lab-masters') . '"><i class="bi bi-arrow-left me-1"></i>Back</a>';
require __DIR__ . '/../../components/page-header.php';

$lab = $lab ?? [];
$isEdit = !empty($lab['id']);
?>
<form method="post" action="<?= $isEdit ? app_url('lab-masters/' . $lab['id']) : app_url('lab-masters') ?>" class="ajax-form" data-redirect="<?= e(app_url('lab-masters')) ?>">
    <?= csrf_field() ?>
    <div class="card content-card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Lab Name <span class="required-star">*</span></label>
                    <input
                        class="form-control"
                        type="text"
                        name="name"
                        value="<?= e(old('name', $lab['name'] ?? '')) ?>"
                        required
                        maxlength="150"
                        placeholder="Enter lab name"
                    >
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Save' ?></button>
                <a class="btn btn-light" href="<?= app_url('lab-masters') ?>">Cancel</a>
            </div>
        </div>
    </div>
</form>
