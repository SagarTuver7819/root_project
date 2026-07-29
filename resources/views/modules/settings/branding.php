<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
$brand = branding();
$logoFields = [
    'logo_main' => 'Main Logo',
    'logo_login' => 'Login Logo',
    'logo_sidebar' => 'Sidebar Logo (expanded menu)',
    'logo_collapsed' => 'Collapsed Logo (mini menu)',
    'favicon' => 'Favicon',
];
?>
<form method="post" action="<?= app_url('settings/branding') ?>" enctype="multipart/form-data" id="brandingForm">
    <?= csrf_field() ?>
    <div class="card content-card">
        <div class="card-body">
            <div class="alert alert-info py-2 mb-4">
                Sidebar માં દેખાવા માટે <strong>Sidebar Logo</strong> જ change કરો, પછી <strong>Save Branding</strong> દબાવો.
                File choose કર્યા પછી Save વગર change apply નહીં થાય.
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Hospital Name</label>
                    <input class="form-control" name="hospital_name" value="<?= e(old('hospital_name', $brand['hospital_name'] ?? '')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Primary Color</label>
                    <input class="form-control form-control-color w-100" type="color" name="primary_color" value="<?= e(old('primary_color', $brand['primary_color'] ?? '#00AEEF')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Secondary Color</label>
                    <input class="form-control form-control-color w-100" type="color" name="secondary_color" value="<?= e(old('secondary_color', $brand['secondary_color'] ?? '#58595B')) ?>">
                </div>

                <div class="col-12">
                    <hr class="my-1">
                    <h6 class="mb-3">Sidebar Theme</h6>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sidebar Background</label>
                    <input class="form-control form-control-color w-100" type="color" id="sidebarColor" name="sidebar_color" value="<?= e(old('sidebar_color', $brand['sidebar_color'] ?? '#111111')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sidebar Text</label>
                    <input class="form-control form-control-color w-100" type="color" id="sidebarTextColor" name="sidebar_text_color" value="<?= e(old('sidebar_text_color', $brand['sidebar_text_color'] ?? '#FFFFFF')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label d-block">Quick Themes</label>
                    <div class="d-flex flex-wrap gap-2" id="sidebarThemePresets">
                        <?php
                        $presets = [
                            ['Dark', '#111111', '#FFFFFF'],
                            ['Navy', '#0B1F33', '#FFFFFF'],
                            ['Teal', '#0A3A40', '#FFFFFF'],
                            ['Slate', '#2C3338', '#FFFFFF'],
                            ['Brand', '#0090C5', '#FFFFFF'],
                            ['Light', '#F5F7F9', '#1A1A1A'],
                        ];
                        foreach ($presets as [$label, $bg, $text]):
                        ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary theme-preset"
                            data-bg="<?= e($bg) ?>" data-text="<?= e($text) ?>"
                            style="border-left: 6px solid <?= e($bg) ?>;">
                            <?= e($label) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text">Save Branding પછી sidebar color apply થશે.</div>
                </div>
                <div class="col-12">
                    <div id="sidebarThemePreview" class="rounded p-3 d-flex align-items-center gap-3"
                         style="background:<?= e($brand['sidebar_color'] ?? '#111111') ?>;color:<?= e($brand['sidebar_text_color'] ?? '#FFFFFF') ?>;min-height:64px;">
                        <span class="fw-semibold">Sidebar preview</span>
                        <span class="opacity-75 small">Menu text sample</span>
                    </div>
                </div>

                <?php foreach ($logoFields as $key => $label): ?>
                <div class="col-md-4">
                    <label class="form-label"><?= e($label) ?></label>
                    <div class="border rounded p-2 mb-2 d-flex align-items-center justify-content-center logo-preview-wrap" style="min-height:84px;background:<?= e($brand['sidebar_color'] ?? '#111111') ?>;">
                        <img class="logo-preview" data-logo-key="<?= e($key) ?>" src="<?= e(logo_url($key)) ?>" alt="<?= e($label) ?>" style="max-height:70px;max-width:100%;object-fit:contain;">
                    </div>
                    <input class="form-control logo-input" type="file" name="<?= e($key) ?>" accept=".png,.jpg,.jpeg,.webp,.svg,.gif,image/*" data-preview=".logo-preview[data-logo-key='<?= e($key) ?>']">
                    <div class="form-text small">
                        Current: <code><?= e(basename((string) ($brand[$key] ?? ''))) ?></code>
                    </div>
                    <?php if ($key === 'logo_sidebar'): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="sync_collapsed_logo" value="1" id="syncCollapsed" checked>
                            <label class="form-check-label" for="syncCollapsed">Same logo for collapsed sidebar</label>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="hospital_address" rows="2"><?= e(old('hospital_address', $brand['hospital_address'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input class="form-control" name="hospital_phone" value="<?= e(old('hospital_phone', $brand['hospital_phone'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="hospital_email" value="<?= e(old('hospital_email', $brand['hospital_email'] ?? '')) ?>">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="saveBrandingBtn">Save Branding</button>
                <a class="btn btn-light" href="<?= app_url('dashboard') ?>">Cancel</a>
            </div>
        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const bgInput = document.getElementById('sidebarColor');
  const textInput = document.getElementById('sidebarTextColor');
  const preview = document.getElementById('sidebarThemePreview');

  function applySidebarPreview() {
    if (!bgInput || !textInput) return;
    const bg = bgInput.value;
    const text = textInput.value;
    if (preview) {
      preview.style.background = bg;
      preview.style.color = text;
    }
    document.querySelectorAll('.logo-preview-wrap').forEach(function (el) {
      el.style.background = bg;
    });
  }

  bgInput && bgInput.addEventListener('input', applySidebarPreview);
  textInput && textInput.addEventListener('input', applySidebarPreview);

  document.querySelectorAll('.theme-preset').forEach(function (btn) {
    btn.addEventListener('click', function () {
      bgInput.value = this.dataset.bg;
      textInput.value = this.dataset.text;
      applySidebarPreview();
    });
  });

  document.querySelectorAll('.logo-input').forEach(function (input) {
    input.addEventListener('change', function () {
      const file = this.files && this.files[0];
      const previewImg = document.querySelector(this.dataset.preview);
      if (!file || !previewImg) return;
      previewImg.src = URL.createObjectURL(file);
      if (this.name === 'logo_sidebar' && window.toastr) {
        toastr.info('Sidebar logo selected: ' + file.name + '. Now click Save Branding.');
      }
    });
  });

  document.getElementById('brandingForm').addEventListener('submit', function () {
    const btn = document.getElementById('saveBrandingBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
  });
});
</script>
