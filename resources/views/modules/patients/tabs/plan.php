<?php
$patientId = (int) ($id ?? 0);
$canEdit = can('patients.edit');
$doctors = $doctors ?? [];
$savedItems = $savedItems ?? [];

$minRows = 5;
$rows = $savedItems;
while (count($rows) < $minRows) {
    $rows[] = ['id' => '', 'description' => '', 'doctor_id' => ''];
}
?>

<form method="post" action="<?= app_url('patients/' . $patientId . '/suggested-plan') ?>" class="ajax-form suggested-plan-form" data-redirect="<?= e(app_url('patients/' . $patientId . '?tab=plan')) ?>">
    <?= csrf_field() ?>

    <div class="suggested-plan-head">
        <h3 class="h5 mb-1">Suggested Treatment Plan</h3>
        <p class="text-muted small mb-0">Pehli line compulsory che. Default 5 line, pachhi sequence ma add kari shakay. Doctor select kari ne calendar ma appointment book kari shakay.</p>
    </div>

    <div id="suggestedPlanRows" class="suggested-plan-list">
        <?php foreach ($rows as $index => $row): ?>
            <?php
            $n = $index + 1;
            $rowId = (string) ($row['id'] ?? '');
            $desc = (string) ($row['description'] ?? '');
            $docId = (string) ($row['doctor_id'] ?? '');
            ?>
            <div class="suggested-plan-row" data-index="<?= (int) $index ?>">
                <div class="suggested-plan-num"><?= (int) $n ?></div>
                <div class="suggested-plan-fields">
                    <input type="hidden" name="items[<?= (int) $index ?>][id]" value="<?= e($rowId) ?>">
                    <input
                        class="form-control suggested-plan-desc"
                        type="text"
                        name="items[<?= (int) $index ?>][description]"
                        value="<?= e($desc) ?>"
                        placeholder="Treatment <?= (int) $n ?>"
                        <?= $n === 1 ? 'required' : '' ?>
                        <?= $canEdit ? '' : 'readonly' ?>
                    >
                    <div class="suggested-plan-actions">
                        <select class="form-select no-select2 suggested-plan-doctor" name="items[<?= (int) $index ?>][doctor_id]" <?= $canEdit ? '' : 'disabled' ?>>
                            <option value="">Add treating doctor</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?= e((string) $d['id']) ?>" <?= $docId === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($canEdit && can('appointments.add')): ?>
                            <button type="button" class="btn btn-outline-primary suggested-plan-book">
                                <i class="bi bi-calendar-plus me-1"></i>Add appointment in calendar
                            </button>
                        <?php endif; ?>
                        <?php if ($canEdit && $n > $minRows): ?>
                            <button type="button" class="btn btn-outline-danger suggested-plan-remove" title="Remove">&times;</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($canEdit): ?>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" id="addSuggestedPlanRow">
                <i class="bi bi-plus-lg me-1"></i>Add next treatment
            </button>
            <button type="submit" class="btn btn-primary">Save Treatment Plan</button>
        </div>
    <?php endif; ?>
</form>

<?php if ($canEdit): ?>
<template id="suggestedPlanRowTpl">
    <div class="suggested-plan-row" data-index="__INDEX__">
        <div class="suggested-plan-num">__NUM__</div>
        <div class="suggested-plan-fields">
            <input type="hidden" name="items[__INDEX__][id]" value="">
            <input class="form-control suggested-plan-desc" type="text" name="items[__INDEX__][description]" placeholder="Treatment __NUM__">
            <div class="suggested-plan-actions">
                <select class="form-select no-select2 suggested-plan-doctor" name="items[__INDEX__][doctor_id]">
                    <option value="">Add treating doctor</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= e((string) $d['id']) ?>"><?= e(doctor_label($d['name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (can('appointments.add')): ?>
                    <button type="button" class="btn btn-outline-primary suggested-plan-book">
                        <i class="bi bi-calendar-plus me-1"></i>Add appointment in calendar
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-danger suggested-plan-remove" title="Remove">&times;</button>
            </div>
        </div>
    </div>
</template>
<script>
(function () {
  const list = document.getElementById('suggestedPlanRows');
  const form = document.querySelector('.suggested-plan-form');
  const tpl = document.getElementById('suggestedPlanRowTpl');
  if (!list || !form) return;

    const calendarBase = <?= json_encode(app_url('calendar')) ?>;
    const saveUrl = form.getAttribute('action');
    const patientId = <?= json_encode((string) $patientId) ?>;
  const patientText = <?= json_encode(trim(($patient['patient_code'] ?? '') . ' - ' . ($patient['name'] ?? '') . ' (' . ($patient['mobile'] ?? '') . ')')) ?>;
  const minRows = 5;

  function reindex() {
    Array.from(list.querySelectorAll('.suggested-plan-row')).forEach(function (row, i) {
      const n = i + 1;
      row.dataset.index = String(i);
      row.querySelector('.suggested-plan-num').textContent = String(n);
      row.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
      });
      const desc = row.querySelector('.suggested-plan-desc');
      if (desc) {
        desc.placeholder = 'Treatment ' + n;
        desc.required = n === 1;
      }
      const remove = row.querySelector('.suggested-plan-remove');
      if (remove) {
        remove.classList.toggle('d-none', n <= minRows);
      }
    });
  }

  document.getElementById('addSuggestedPlanRow')?.addEventListener('click', function () {
    if (!tpl) return;
    const html = tpl.innerHTML.replace(/__INDEX__/g, String(list.children.length)).replace(/__NUM__/g, String(list.children.length + 1));
    list.insertAdjacentHTML('beforeend', html);
    reindex();
  });

  list.addEventListener('click', function (e) {
    const remove = e.target.closest('.suggested-plan-remove');
    if (remove) {
      const row = remove.closest('.suggested-plan-row');
      if (list.querySelectorAll('.suggested-plan-row').length > minRows) {
        row.remove();
        reindex();
      }
      return;
    }

    const book = e.target.closest('.suggested-plan-book');
    if (!book) return;
    const row = book.closest('.suggested-plan-row');
    const desc = (row.querySelector('.suggested-plan-desc')?.value || '').trim();
    const doctorId = row.querySelector('.suggested-plan-doctor')?.value || '';
    if (!desc) {
      toastr.warning('First enter treatment for this line.');
      row.querySelector('.suggested-plan-desc')?.focus();
      return;
    }
    if (!doctorId) {
      toastr.warning('Please select treating doctor for this line.');
      row.querySelector('.suggested-plan-doctor')?.focus();
      return;
    }

    const params = new URLSearchParams({
      patient_id: patientId,
      doctor_id: doctorId,
      reason: desc,
      patient_text: patientText,
      open_book: '1',
      return: 'patients/' + patientId + '?tab=plan'
    });
    const calendarUrl = calendarBase + '?' + params.toString();
    const fd = new FormData(form);
    fetch(saveUrl, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
      },
      body: fd
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (res && (res.success === false)) {
        toastr.error(res.message || 'Please save treatment plan first.');
        return;
      }
      window.location.href = calendarUrl;
    }).catch(function () {
      toastr.error('Unable to save treatment plan.');
    });
  });
})();
</script>
<?php endif; ?>
