<?php
$status = $status ?? [];
$isEdit = !empty($status['id']);
require __DIR__ . '/../../components/page-header.php';
?>
<form method="post" action="<?= $isEdit ? app_url('appointment-statuses/' . $status['id']) : app_url('appointment-statuses') ?>" class="ajax-form">
    <?= csrf_field() ?>
    <div class="card content-card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status Name <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" name="name" value="<?= e(old('name', $status['name'] ?? '')) ?>" required placeholder="e.g. In Consultation">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug / Key</label>
                    <input class="form-control" type="text" name="slug" value="<?= e(old('slug', $status['slug'] ?? '')) ?>" placeholder="Auto-generated (e.g. in_consultation)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Color Code</label>
                    <div class="d-flex gap-2">
                        <input class="form-control form-control-color" type="color" name="color" value="<?= e(old('color', $status['color'] ?? '#00AEEF')) ?>">
                        <input class="form-control" type="text" name="color_text" value="<?= e(old('color', $status['color'] ?? '#00AEEF')) ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Badge Class Style</label>
                    <select class="form-select" name="badge_class">
                        <?php
                        $badges = ['primary', 'info', 'warning', 'success', 'danger', 'secondary', 'accent'];
                        $selectedBadge = old('badge_class', $status['badge_class'] ?? 'primary');
                        foreach ($badges as $b):
                        ?>
                            <option value="<?= e($b) ?>" <?= $selectedBadge === $b ? 'selected' : '' ?>><?= e(ucfirst($b)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sort Order</label>
                    <input class="form-control" type="number" name="sort_order" value="<?= e(old('sort_order', $status['sort_order'] ?? 0)) ?>">
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= (int) old('is_active', $status['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Status</button>
                <a class="btn btn-light" href="<?= app_url('appointment-statuses') ?>">Cancel</a>
            </div>
        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.querySelector('input[name="color"]');
    const colorText = document.querySelector('input[name="color_text"]');
    if (colorInput && colorText) {
        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
    }
});
</script>
