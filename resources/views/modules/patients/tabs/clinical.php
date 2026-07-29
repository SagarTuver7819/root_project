<?php
$chart = $chart ?? [];
$toothNotes = $toothNotes ?? [];
$doctors = $doctors ?? [];
$xrays = $xrays ?? [];
$pictures = $pictures ?? [];
$patientId = (int) ($id ?? 0);
$canEdit = can('patients.edit');

$permanentUpper = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];
$permanentLower = [32, 31, 30, 29, 28, 27, 26, 25, 24, 23, 22, 21, 20, 19, 18, 17];
$primaryUpper = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
$primaryLower = ['T', 'S', 'R', 'Q', 'P', 'O', 'N', 'M', 'L', 'K'];

$renderTooth = static function ($toothId) use ($toothNotes): void {
    $key = (string) $toothId;
    $note = trim((string) ($toothNotes[$key] ?? ''));
    $hasNote = $note !== '';
    $title = $hasNote ? $key . ': ' . $note : 'Tooth ' . $key . ' — click to write note';
    ?>
    <button type="button"
        class="tooth-btn<?= $hasNote ? ' has-note' : '' ?>"
        data-tooth="<?= e($key) ?>"
        title="<?= e($title) ?>">
        <span class="tooth-shape" aria-hidden="true"></span>
        <span class="tooth-id"><?= e($key) ?></span>
    </button>
    <?php
};
?>

<form method="post" action="<?= app_url('patients/' . $patientId . '/clinical-chart') ?>" class="ajax-form clinical-chart-form" data-reload="1">
    <?= csrf_field() ?>
    <input type="hidden" name="tooth_notes" id="toothNotesJson" value="<?= e(json_encode($toothNotes, JSON_UNESCAPED_UNICODE)) ?>">

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold">1. Chief Complaint</label>
            <textarea class="form-control" name="chief_complaint" rows="2" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['chief_complaint'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="fw-semibold mb-2">2. Medical History</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Drug list</label>
                    <textarea class="form-control" name="drug_list" rows="3" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['drug_list'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Habit</label>
                    <textarea class="form-control" name="habit" rows="3" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['habit'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="fw-semibold mb-2">3. X-Ray</div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2 mb-2">
                <input type="file" class="form-control form-control-sm clinical-doc-file" data-type="xray" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <button type="button" class="btn btn-sm btn-outline-primary clinical-doc-upload" data-type="xray">Upload</button>
            </div>
            <?php endif; ?>
            <div class="clinical-doc-list small">
                <?php if (empty($xrays)): ?>
                    <div class="text-muted">No X-Ray uploaded.</div>
                <?php else: ?>
                    <?php foreach ($xrays as $doc): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <a href="<?= e(asset('uploads/' . ltrim($doc['file_path'], '/'))) ?>" target="_blank"><?= e($doc['description'] ?: basename($doc['file_path'])) ?></a>
                            <span class="text-muted"><?= e(format_date($doc['created_at'] ?? null, 'd-m-Y')) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="fw-semibold mb-2">4. Clinical Pictures</div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2 mb-2">
                <input type="file" class="form-control form-control-sm clinical-doc-file" data-type="clinical_picture" accept=".jpg,.jpeg,.png,.webp">
                <button type="button" class="btn btn-sm btn-outline-primary clinical-doc-upload" data-type="clinical_picture">Upload</button>
            </div>
            <?php endif; ?>
            <div class="clinical-doc-list small">
                <?php if (empty($pictures)): ?>
                    <div class="text-muted">No clinical pictures uploaded.</div>
                <?php else: ?>
                    <?php foreach ($pictures as $doc): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <a href="<?= e(asset('uploads/' . ltrim($doc['file_path'], '/'))) ?>" target="_blank"><?= e($doc['description'] ?: basename($doc['file_path'])) ?></a>
                            <span class="text-muted"><?= e(format_date($doc['created_at'] ?? null, 'd-m-Y')) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold">5. Test advised</label>
            <textarea class="form-control mb-2" name="test_advised" rows="2" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['test_advised'] ?? '') ?></textarea>

            <div class="dental-chart-wrap">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Dental Chart <span class="text-muted fw-normal">(click a tooth to write note)</span></div>
                    <div class="small text-muted">Patient view — Right | Left</div>
                </div>

                <div class="dental-arch-label">Permanent Teeth</div>
                <div class="dental-side-labels"><span>Right</span><span>Left</span></div>
                <div class="dental-arch">
                    <?php foreach ($permanentUpper as $t): $renderTooth($t); endforeach; ?>
                </div>
                <div class="dental-midline"></div>
                <div class="dental-arch">
                    <?php foreach ($permanentLower as $t): $renderTooth($t); endforeach; ?>
                </div>

                <div class="dental-arch-label mt-3">Primary / Deciduous Teeth</div>
                <div class="dental-side-labels"><span>Right</span><span>Left</span></div>
                <div class="dental-arch dental-arch-primary">
                    <?php foreach ($primaryUpper as $t): $renderTooth($t); endforeach; ?>
                </div>
                <div class="dental-midline"></div>
                <div class="dental-arch dental-arch-primary">
                    <?php foreach ($primaryLower as $t): $renderTooth($t); endforeach; ?>
                </div>

                <div class="mt-3">
                    <div class="fw-semibold mb-1">Tooth notes summary</div>
                    <div id="toothNotesSummary" class="tooth-notes-summary small">
                        <?php if (empty($toothNotes)): ?>
                            <span class="text-muted">No tooth notes yet. Click a tooth to add.</span>
                        <?php else: ?>
                            <?php foreach ($toothNotes as $tid => $note): ?>
                                <div><strong>#<?= e((string) $tid) ?>:</strong> <?= e((string) $note) ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">6. Allotted doctor</label>
            <select class="form-select" name="allotted_doctor_id" <?= $canEdit ? '' : 'disabled' ?>>
                <option value="">Select doctor</option>
                <?php $allotted = (string) ($chart['allotted_doctor_id'] ?? ''); foreach ($doctors as $d): ?>
                    <option value="<?= e($d['id']) ?>" <?= $allotted === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">7. Test done</label>
            <textarea class="form-control" name="test_done" rows="2" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['test_done'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="fw-semibold mb-2">8. Next Appt</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input class="form-control" type="date" name="next_appt_date" value="<?= e($chart['next_appt_date'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time</label>
                    <input class="form-control" type="time" name="next_appt_time" value="<?= e(isset($chart['next_appt_time']) ? substr((string) $chart['next_appt_time'], 0, 5) : '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Test to be done</label>
                    <input class="form-control" name="next_appt_test" value="<?= e($chart['next_appt_test'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">9. Next Appt Doctor</label>
            <select class="form-select" name="next_appt_doctor_id" <?= $canEdit ? '' : 'disabled' ?>>
                <option value="">Select doctor</option>
                <?php $nextDoc = (string) ($chart['next_appt_doctor_id'] ?? ''); foreach ($doctors as $d): ?>
                    <option value="<?= e($d['id']) ?>" <?= $nextDoc === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($canEdit): ?>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Save Clinical Chart</button>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($canEdit): ?>
<div class="modal fade" id="toothNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tooth <span id="toothNoteIdLabel"></span> — Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="toothNoteInput" rows="4" placeholder="Write clinical note for this tooth..."></textarea>
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
  const root = document.querySelector('.clinical-chart-form');
  if (!root) return;

  const hidden = document.getElementById('toothNotesJson');
  const summary = document.getElementById('toothNotesSummary');
  let notes = {};
  try { notes = JSON.parse(hidden.value || '{}') || {}; } catch (e) { notes = {}; }

  let activeTooth = null;
  const modalEl = document.getElementById('toothNoteModal');
  const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  const input = document.getElementById('toothNoteInput');

  function syncSummary() {
    const keys = Object.keys(notes).filter(function (k) { return String(notes[k] || '').trim() !== ''; });
    if (!keys.length) {
      summary.innerHTML = '<span class="text-muted">No tooth notes yet. Click a tooth to add.</span>';
      return;
    }
    summary.innerHTML = keys.map(function (k) {
      return '<div><strong>#' + k + ':</strong> ' + String(notes[k]).replace(/</g, '&lt;') + '</div>';
    }).join('');
  }

  function syncHidden() {
    hidden.value = JSON.stringify(notes);
  }

  function markTooth(btn) {
    const id = btn.dataset.tooth;
    const has = String(notes[id] || '').trim() !== '';
    btn.classList.toggle('has-note', has);
    btn.title = has ? (id + ': ' + notes[id]) : ('Tooth ' + id + ' — click to write note');
  }

  root.querySelectorAll('.tooth-btn').forEach(function (btn) {
    markTooth(btn);
    btn.addEventListener('click', function () {
      activeTooth = this.dataset.tooth;
      document.getElementById('toothNoteIdLabel').textContent = activeTooth;
      input.value = notes[activeTooth] || '';
      modal && modal.show();
      setTimeout(function () { input.focus(); }, 200);
    });
  });

  document.getElementById('toothNoteSave')?.addEventListener('click', function () {
    if (!activeTooth) return;
    const val = input.value.trim();
    if (val) notes[activeTooth] = val;
    else delete notes[activeTooth];
    syncHidden();
    syncSummary();
    const btn = root.querySelector('.tooth-btn[data-tooth="' + activeTooth + '"]');
    if (btn) markTooth(btn);
    modal && modal.hide();
  });

  document.getElementById('toothNoteClear')?.addEventListener('click', function () {
    input.value = '';
  });

  document.querySelectorAll('.clinical-doc-upload').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const type = this.dataset.type;
      const fileInput = document.querySelector('.clinical-doc-file[data-type="' + type + '"]');
      if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        toastr.warning('Please choose a file first.');
        return;
      }
      const fd = new FormData();
      fd.append('_token', window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '');
      fd.append('document_type', type);
      fd.append('document', fileInput.files[0]);
      fd.append('description', fileInput.files[0].name);

      fetch('<?= app_url('patients/' . $patientId . '/documents') ?>', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
        },
        body: fd
      }).then(function (r) { return r.json(); }).then(function (res) {
        if (res.status === 'success' || res.success) {
          toastr.success(res.message || 'Uploaded.');
          // Reload clinical tab
          const active = document.querySelector('#patientTabs .nav-link.active');
          active && active.click();
        } else {
          toastr.error(res.message || 'Upload failed.');
        }
      }).catch(function () {
        toastr.error('Upload failed.');
      });
    });
  });
})();
</script>
<?php endif; ?>
