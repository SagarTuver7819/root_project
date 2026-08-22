<?php
$formAction = app_url('patients/' . ($patient['id'] ?? '') . '/edit');
$actions = '<a href="' . $formAction . '" class="btn btn-primary">Edit Patient</a><a href="' . app_url('calendar?patient_id=' . ($patient['id'] ?? '')) . '" class="btn btn-light">Book</a>';
require __DIR__ . '/../../components/page-header.php';

$tabs = [
    'clinical' => 'Clinical Chart',
    'plan' => 'Treatment Plan',
    'history' => 'History',
    'appointments' => 'Appointments',
    'estimate' => 'Treatment Estimate',
    'treatments' => 'Treatments',
    'prescriptions' => 'Prescriptions',
    'payments' => 'Payments',
    'documents' => 'Documents',
];
$hasTreatmentPlan = !empty($hasTreatmentPlan);
$openTabs = ['clinical', 'plan'];
$defaultTab = $_GET['tab'] ?? 'clinical';
if (!isset($tabs[$defaultTab])) {
    $defaultTab = 'clinical';
}
if (!$hasTreatmentPlan && !in_array($defaultTab, $openTabs, true)) {
    $defaultTab = 'plan';
}
?>
<div class="card content-card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><strong>OPD Number</strong><div><?= e($patient['patient_code'] ?? '') ?></div></div>
            <div class="col-md-3"><strong>Name</strong><div><?= e($patient['name'] ?? '') ?></div></div>
            <div class="col-md-3"><strong>Mobile</strong><div><?= e($patient['mobile'] ?? '') ?></div></div>
            <div class="col-md-3"><strong>Status</strong><div><?= status_badge(!empty($patient['is_active']) ? 'active' : 'inactive') ?></div></div>
        </div>
        <div class="mt-3">
            <a href="<?= app_url('patients/history?q=' . urlencode((string) ($patient['mobile'] ?? $patient['patient_code'] ?? ''))) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clock-history me-1"></i>Open Full History by Mobile
            </a>
        </div>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <ul class="nav nav-tabs patient-profile-tabs" id="patientTabs" role="tablist">
            <?php foreach ($tabs as $key => $label): ?>
            <?php $isLocked = !$hasTreatmentPlan && !in_array($key, $openTabs, true); ?>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?= $key === $defaultTab ? 'active' : '' ?><?= $isLocked ? ' is-locked' : '' ?>"
                    type="button"
                    data-tab="<?= e($key) ?>"
                    data-locked="<?= $isLocked ? '1' : '0' ?>"
                    role="tab"
                >
                    <?= e($label) ?>
                    <?php if ($isLocked): ?><i class="bi bi-lock-fill ms-1 small"></i><?php endif; ?>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$hasTreatmentPlan): ?>
            <div class="alert alert-warning py-2 px-3 mt-3 mb-0">
                Suggested treatment plan (line 1) save thay pachi j biji tabs open thase.
            </div>
        <?php endif; ?>
        <div id="tabContent" class="pt-3">Loading...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const box = document.getElementById('tabContent');
  const base = '<?= app_url('patients/' . ($patient['id'] ?? '') . '/tab/') ?>';
  const defaultTab = <?= json_encode($defaultTab) ?>;

  function runScripts(container) {
    container.querySelectorAll('script').forEach(function (oldScript) {
      const script = document.createElement('script');
      if (oldScript.src) {
        script.src = oldScript.src;
      } else {
        script.textContent = oldScript.textContent;
      }
      Array.from(oldScript.attributes).forEach(function (attr) {
        if (attr.name !== 'src') {
          script.setAttribute(attr.name, attr.value);
        }
      });
      oldScript.parentNode.replaceChild(script, oldScript);
    });
  }

  function load(tab) {
    box.innerHTML = '<div class="text-muted py-3">Loading...</div>';
    fetch(base + tab, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        box.innerHTML = (r.data && r.data.html) || r.html || '<div class="text-muted py-3">No records found.</div>';
        runScripts(box);
        if (window.RootsApp && typeof window.RootsApp.initSelect2 === 'function') {
          window.RootsApp.initSelect2(box);
        }
      })
      .catch(function () {
        box.innerHTML = '<div class="text-danger py-3">Unable to load tab data.</div>';
      });
  }

  document.querySelectorAll('#patientTabs .nav-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (this.dataset.locked === '1') {
        toastr.warning('Pehla suggested treatment plan save karo. Line 1 compulsory che.');
        return;
      }
      document.querySelectorAll('#patientTabs .nav-link').forEach(function (x) { x.classList.remove('active'); });
      this.classList.add('active');
      load(this.dataset.tab);
    });
  });

  load(defaultTab);
});
</script>
