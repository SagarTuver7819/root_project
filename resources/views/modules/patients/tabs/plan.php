<?php
$patientId = (int) ($id ?? 0);
$canEdit = can('patients.edit');
$doctors = $doctors ?? [];
$savedItems = $savedItems ?? [];

$minRows = 5;
$rows = $savedItems;
while (count($rows) < $minRows) {
    $rows[] = ['id' => '', 'description' => '', 'doctor_id' => '', 'teeth' => ''];
}

$selectedTeeth = [];
foreach ($savedItems as $item) {
    $raw = trim((string) ($item['teeth'] ?? ''));
    if ($raw === '') {
        continue;
    }
    foreach (array_map('trim', explode(',', $raw)) as $code) {
        if ($code !== '') {
            $selectedTeeth[$code] = true;
        }
    }
}

$renderPalmerTeeth = static function (array $codes, bool $canEdit, array $selectedTeeth): void {
    foreach ($codes as $code) {
        $label = preg_replace('/^[A-Z]{2}/', '', $code) ?? $code;
        $on = !empty($selectedTeeth[$code]);
        ?>
        <button type="button" class="palmer-tooth<?= $on ? ' is-selected' : '' ?>" data-tooth="<?= e($code) ?>" <?= $canEdit ? '' : 'disabled' ?>><?= e($label) ?></button>
        <?php
    }
};
?>

<form method="post" action="<?= app_url('patients/' . $patientId . '/suggested-plan') ?>" class="ajax-form suggested-plan-form" data-redirect="<?= e(app_url('patients/' . $patientId . '?tab=plan')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="tooth_notes" id="suggestedPlanToothNotes" value="<?= e(json_encode($toothNotes ?: new stdClass(), JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT)) ?>">

    <div class="palmer-wrap">
        <div class="palmer-wrap-title">Dental Chart</div>
        <div class="palmer-arch">
            <div class="palmer-arch-label permanent"><i class="bi bi-emoji-smile"></i> Permanent Teeth</div>
            <div class="palmer-quad-labels"><span>Upper Right</span><span class="text-end">Upper Left</span></div>
            <div class="palmer-grid">
                <?php $renderPalmerTeeth(['UR8','UR7','UR6','UR5','UR4','UR3','UR2','UR1','UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8'], $canEdit, $selectedTeeth); ?>
                <?php $renderPalmerTeeth(['LR8','LR7','LR6','LR5','LR4','LR3','LR2','LR1','LL1','LL2','LL3','LL4','LL5','LL6','LL7','LL8'], $canEdit, $selectedTeeth); ?>
            </div>
            <div class="palmer-quad-labels lower"><span>Lower Right</span><span class="text-end">Lower Left</span></div>
        </div>
        <div class="palmer-arch">
            <div class="palmer-arch-label deciduous"><i class="bi bi-emoji-smile"></i> Deciduous Teeth</div>
            <div class="palmer-quad-labels"><span>Upper Right</span><span class="text-end">Upper Left</span></div>
            <div class="palmer-grid deciduous">
                <?php $renderPalmerTeeth(['URE','URD','URC','URB','URA','ULA','ULB','ULC','ULD','ULE'], $canEdit, $selectedTeeth); ?>
                <?php $renderPalmerTeeth(['LRE','LRD','LRC','LRB','LRA','LLA','LLB','LLC','LLD','LLE'], $canEdit, $selectedTeeth); ?>
            </div>
            <div class="palmer-quad-labels lower"><span>Lower Right</span><span class="text-end">Lower Left</span></div>
        </div>
        <p class="palmer-hint mb-0">Tooth par click karo — note popup khulse. Treatment line select kari ne save karo.</p>
    </div>

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
            $teeth = trim((string) ($row['teeth'] ?? ''));
            $toothList = $teeth === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $teeth))));
            ?>
            <div class="suggested-plan-row<?= $index === 0 ? ' is-active' : '' ?>" data-index="<?= (int) $index ?>">
                <div class="suggested-plan-num"><?= (int) $n ?></div>
                <div class="suggested-plan-fields">
                    <input type="hidden" name="items[<?= (int) $index ?>][id]" value="<?= e($rowId) ?>">
                    <input type="hidden" class="suggested-plan-teeth-input" name="items[<?= (int) $index ?>][teeth]" value="<?= e($teeth) ?>">
                    <input
                        class="form-control suggested-plan-desc"
                        type="text"
                        name="items[<?= (int) $index ?>][description]"
                        value="<?= e($desc) ?>"
                        placeholder="Treatment <?= (int) $n ?>"
                        <?= $n === 1 ? 'required' : '' ?>
                        <?= $canEdit ? '' : 'readonly' ?>
                    >
                    <div class="suggested-plan-teeth">
                        <?php foreach ($toothList as $tooth): ?>
                            <span class="tooth-chip"><?= e($tooth) ?></span>
                        <?php endforeach; ?>
                    </div>
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
            <input type="hidden" class="suggested-plan-teeth-input" name="items[__INDEX__][teeth]" value="">
            <input class="form-control suggested-plan-desc" type="text" name="items[__INDEX__][description]" placeholder="Treatment __NUM__">
            <div class="suggested-plan-teeth"></div>
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

<div class="modal fade" id="toothNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tooth <span id="toothNoteIdLabel"></span> — Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Clinical / treatment note</label>
                <textarea class="form-control" id="toothNoteInput" rows="4" placeholder="Write note for this tooth..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="toothNoteClear">Clear</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="toothNoteSave">Save Note</button>
            </div>
        </div>
    </div>
</div>

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
  let activeTooth = '';
  const notesField = document.getElementById('suggestedPlanToothNotes');
  const input = document.getElementById('toothNoteInput');
  const modalEl = document.getElementById('toothNoteModal');
  const modal = (modalEl && window.bootstrap) ? new bootstrap.Modal(modalEl) : null;

  function parseTeethNotes() {
    try {
      const data = JSON.parse(notesField && notesField.value ? notesField.value : '{}');
      return (data && typeof data === 'object' && !Array.isArray(data)) ? data : {};
    } catch (err) {
      return {};
    }
  }

  function activeRow() {
    return list.querySelector('.suggested-plan-row.is-active') || list.querySelector('.suggested-plan-row');
  }

  function parseTeeth(value) {
    return String(value || '').split(',').map(function (v) { return v.trim(); }).filter(Boolean);
  }

  function renderChips(row) {
    const input = row.querySelector('.suggested-plan-teeth-input');
    const box = row.querySelector('.suggested-plan-teeth');
    if (!input || !box) return;
    const teeth = parseTeeth(input.value);
    const notes = parseTeethNotes();
    box.innerHTML = teeth.map(function (t) {
      const note = notes[t] ? String(notes[t]).replace(/</g, '&lt;').replace(/"/g, '&quot;') : '';
      return '<span class="tooth-chip"' + (note ? ' title="' + note + '"' : '') + '>' + t.replace(/</g, '&lt;') + '</span>';
    }).join('');
  }

  function paintChart() {
    const selected = {};
    list.querySelectorAll('.suggested-plan-teeth-input').forEach(function (input) {
      parseTeeth(input.value).forEach(function (t) { selected[t] = true; });
    });
    form.querySelectorAll('.palmer-tooth').forEach(function (btn) {
      btn.classList.toggle('is-selected', !!selected[btn.dataset.tooth]);
    });
  }

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

  list.addEventListener('click', function (e) {
    const row = e.target.closest('.suggested-plan-row');
    if (row) {
      list.querySelectorAll('.suggested-plan-row').forEach(function (r) { r.classList.remove('is-active'); });
      row.classList.add('is-active');
    }
  });

  form.querySelectorAll('.palmer-tooth').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const row = activeRow();
      if (!row) return;
      list.querySelectorAll('.suggested-plan-row').forEach(function (r) { r.classList.remove('is-active'); });
      row.classList.add('is-active');
      activeTooth = this.dataset.tooth;
      const q = { UR: 'Upper Right', UL: 'Upper Left', LR: 'Lower Right', LL: 'Lower Left' };
      const prefix = (activeTooth || '').slice(0, 2);
      const num = (activeTooth || '').slice(2);
      document.getElementById('toothNoteIdLabel').textContent = (q[prefix] ? q[prefix] + ' ' : '') + num;
      const notes = parseTeethNotes();
      const desc = (row.querySelector('.suggested-plan-desc')?.value || '').trim();
      input.value = notes[activeTooth] || (parseTeeth(row.querySelector('.suggested-plan-teeth-input').value).indexOf(activeTooth) >= 0 ? desc : '');
      modal && modal.show();
      setTimeout(function () { input.focus(); }, 200);
    });
  });

  document.getElementById('toothNoteSave')?.addEventListener('click', function () {
    if (!activeTooth) return;
    const row = activeRow();
    if (!row) return;
    const val = input.value.trim();
    const notes = parseTeethNotes();
    const hiddenTeeth = row.querySelector('.suggested-plan-teeth-input');
    const desc = row.querySelector('.suggested-plan-desc');
    const teeth = parseTeeth(hiddenTeeth.value);

    if (val) {
      notes[activeTooth] = val;
      if (teeth.indexOf(activeTooth) < 0) teeth.push(activeTooth);
      if (desc && !desc.value.trim()) desc.value = val;
    } else {
      delete notes[activeTooth];
      const idx = teeth.indexOf(activeTooth);
      if (idx >= 0) teeth.splice(idx, 1);
    }

    hiddenTeeth.value = teeth.join(',');
    notesField.value = JSON.stringify(notes);
    renderChips(row);
    paintChart();
    modal && modal.hide();
    if (window.toastr) {
      toastr.success(val ? 'Tooth note saved. Click Save Treatment Plan to keep it.' : 'Tooth note cleared.');
    }
  });

  document.getElementById('toothNoteClear')?.addEventListener('click', function () {
    input.value = '';
  });

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
        paintChart();
      }
      return;
    }

    const book = e.target.closest('.suggested-plan-book');
    if (!book) return;
    const row = book.closest('.suggested-plan-row');
    const desc = (row.querySelector('.suggested-plan-desc')?.value || '').trim();
    const doctorId = row.querySelector('.suggested-plan-doctor')?.value || '';
    const teeth = (row.querySelector('.suggested-plan-teeth-input')?.value || '').trim();
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

    const reason = teeth ? (desc + ' · ' + teeth) : desc;
    const params = new URLSearchParams({
      patient_id: patientId,
      doctor_id: doctorId,
      reason: reason,
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

  list.querySelectorAll('.suggested-plan-row').forEach(renderChips);
  paintChart();
})();
</script>
<?php endif; ?>
