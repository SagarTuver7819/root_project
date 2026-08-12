<?php
$chart = $chart ?? [];
$toothNotes = $toothNotes ?? [];
$doctors = $doctors ?? [];
$xrays = $xrays ?? [];
$pictures = $pictures ?? [];
$patientId = (int) ($id ?? 0);
$canEdit = can('patients.edit');

$medicalConditionOptions = ['diabetes', 'cholesterol', 'blood pressure'];
$habitOptions = ['masala', 'betel nut'];

$parseClinicalChecklist = static function (?string $raw, array $knownKeys, string $listKey = 'conditions'): array {
    $out = ['selected' => [], 'other' => '', 'daily_medicine' => ''];
    $raw = trim((string) $raw);
    if ($raw === '') {
        return $out;
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $selected = $decoded[$listKey] ?? $decoded['conditions'] ?? $decoded['items'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }
        foreach ($selected as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $norm = strtolower($item) === 'cholestrol' ? 'cholesterol' : $item;
            foreach ($knownKeys as $known) {
                if (strcasecmp($norm, $known) === 0) {
                    $out['selected'][] = $known;
                    break;
                }
            }
        }
        $out['selected'] = array_values(array_unique($out['selected']));
        $out['other'] = trim((string) ($decoded['other'] ?? ''));
        $out['daily_medicine'] = trim((string) ($decoded['daily_medicine'] ?? ''));
        return $out;
    }

    $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
    $otherParts = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        $norm = strtolower($part) === 'cholestrol' ? 'cholesterol' : $part;
        $matched = false;
        foreach ($knownKeys as $known) {
            if (strcasecmp($norm, $known) === 0) {
                $out['selected'][] = $known;
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $otherParts[] = $part;
        }
    }
    $out['selected'] = array_values(array_unique($out['selected']));
    $out['other'] = implode(', ', $otherParts);
    return $out;
};

$medicalHistory = $parseClinicalChecklist($chart['drug_list'] ?? '', $medicalConditionOptions, 'conditions');
$habitData = $parseClinicalChecklist($chart['habit'] ?? '', $habitOptions, 'items');

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

<form method="post" action="<?= app_url('patients/' . $patientId . '/clinical-chart') ?>" class="ajax-form clinical-chart-form" data-redirect="<?= e(app_url('patients/' . $patientId . '?tab=clinical')) ?>" data-patient-id="<?= (int) $patientId ?>" data-upload-url="<?= e(app_url('patients/' . $patientId . '/documents')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="tooth_notes" id="toothNotesJson" value="<?= e(json_encode($toothNotes ?: new \stdClass(), JSON_UNESCAPED_UNICODE)) ?>">

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold">1. Chief Complaint</label>
            <textarea class="form-control" name="chief_complaint" rows="2" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['chief_complaint'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="fw-semibold mb-2">2. Medical History</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($medicalConditionOptions as $condition): ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="medical_conditions[]"
                                    id="med_<?= e(str_replace(' ', '_', $condition)) ?>"
                                    value="<?= e($condition) ?>"
                                    <?= in_array($condition, $medicalHistory['selected'], true) ? 'checked' : '' ?>
                                    <?= $canEdit ? '' : 'disabled' ?>
                                >
                                <label class="form-check-label" for="med_<?= e(str_replace(' ', '_', $condition)) ?>">
                                    <?= e(ucfirst($condition)) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-check">
                            <input
                                class="form-check-input clinical-other-toggle"
                                type="checkbox"
                                name="medical_other_check"
                                id="medicalOtherCheck"
                                value="1"
                                data-target="#medicalOtherWrap"
                                <?= $medicalHistory['other'] !== '' ? 'checked' : '' ?>
                                <?= $canEdit ? '' : 'disabled' ?>
                            >
                            <label class="form-check-label" for="medicalOtherCheck">Other</label>
                        </div>
                        <div id="medicalOtherWrap" class="<?= $medicalHistory['other'] !== '' ? '' : 'd-none' ?>">
                            <input
                                class="form-control form-control-sm"
                                type="text"
                                name="medical_other"
                                value="<?= e($medicalHistory['other']) ?>"
                                placeholder="Add other illness"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                        <div class="mt-1">
                            <label class="form-label mb-1" for="dailyMedicine">Any daily medicine?</label>
                            <input
                                class="form-control"
                                type="text"
                                name="daily_medicine"
                                id="dailyMedicine"
                                value="<?= e($medicalHistory['daily_medicine']) ?>"
                                placeholder="Current medicines"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Habit</label>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($habitOptions as $habit): ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="habits[]"
                                    id="habit_<?= e(str_replace(' ', '_', $habit)) ?>"
                                    value="<?= e($habit) ?>"
                                    <?= in_array($habit, $habitData['selected'], true) ? 'checked' : '' ?>
                                    <?= $canEdit ? '' : 'disabled' ?>
                                >
                                <label class="form-check-label" for="habit_<?= e(str_replace(' ', '_', $habit)) ?>">
                                    <?= e(ucfirst($habit)) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-check">
                            <input
                                class="form-check-input clinical-other-toggle"
                                type="checkbox"
                                name="habit_other_check"
                                id="habitOtherCheck"
                                value="1"
                                data-target="#habitOtherWrap"
                                <?= $habitData['other'] !== '' ? 'checked' : '' ?>
                                <?= $canEdit ? '' : 'disabled' ?>
                            >
                            <label class="form-check-label" for="habitOtherCheck">Other</label>
                        </div>
                        <div id="habitOtherWrap" class="<?= $habitData['other'] !== '' ? '' : 'd-none' ?>">
                            <input
                                class="form-control form-control-sm"
                                type="text"
                                name="habit_other"
                                value="<?= e($habitData['other']) ?>"
                                placeholder="Add other habit details"
                                <?= $canEdit ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="fw-semibold mb-2">3. X-Ray</div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2 mb-2 clinical-doc-upload-row" data-type="xray">
                <input type="file" class="form-control form-control-sm clinical-doc-file" data-type="xray" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <button type="button" class="btn btn-sm btn-outline-primary clinical-doc-upload" data-type="xray">Upload</button>
            </div>
            <?php endif; ?>
            <div class="clinical-doc-list small" data-doc-list="xray">
                <?php if (empty($xrays)): ?>
                    <div class="text-muted">No X-Ray uploaded.</div>
                <?php else: ?>
                    <?php foreach ($xrays as $doc): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <a href="<?= e(upload_url((string) ($doc['file_path'] ?? ''))) ?>" target="_blank" rel="noopener"><?= e($doc['description'] ?: basename((string) $doc['file_path'])) ?></a>
                            <span class="text-muted"><?= e(format_date($doc['created_at'] ?? null, 'd-m-Y')) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="fw-semibold mb-2">4. Clinical Pictures</div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2 mb-2 clinical-doc-upload-row" data-type="clinical_picture">
                <input type="file" class="form-control form-control-sm clinical-doc-file" data-type="clinical_picture" accept=".jpg,.jpeg,.png,.webp">
                <button type="button" class="btn btn-sm btn-outline-primary clinical-doc-upload" data-type="clinical_picture">Upload</button>
            </div>
            <?php endif; ?>
            <div class="clinical-doc-list small" data-doc-list="clinical_picture">
                <?php if (empty($pictures)): ?>
                    <div class="text-muted">No clinical pictures uploaded.</div>
                <?php else: ?>
                    <?php foreach ($pictures as $doc): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <a href="<?= e(upload_url((string) ($doc['file_path'] ?? ''))) ?>" target="_blank" rel="noopener"><?= e($doc['description'] ?: basename((string) $doc['file_path'])) ?></a>
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
            <div class="fw-semibold mb-2">8. Next Appt (Treatment — Calendar)</div>
            <div class="text-muted small mb-2">Aa date/time treatment appointment calendar ma book thase. Doctor + free slot select karo (overlap slot par error aavse with details).</div>
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

        <?php
        $lab = $labWork ?? [];
        $implant = $implantWork ?? [];
        ?>

        <div class="col-12">
            <div class="fw-semibold mb-2">10. Lab work</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <input class="form-control" name="lab_product" value="<?= e($lab['product'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Shade</label>
                    <input class="form-control" name="lab_shade" value="<?= e($lab['shade'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Brand</label>
                    <input class="form-control" name="lab_brand" value="<?= e($lab['brand'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lab (which lab)</label>
                    <input class="form-control" name="lab_name" value="<?= e($lab['lab_name'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="fw-semibold mb-2">11. Implant work</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">A. Brand / Company of Implant</label>
                    <input class="form-control" name="implant_brand" value="<?= e($implant['brand'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label">B. Internal Hex / Multiunit</label>
                    <select class="form-select" name="implant_hex_type" <?= $canEdit ? '' : 'disabled' ?>>
                        <?php $hex = (string) ($implant['hex_type'] ?? ''); ?>
                        <option value="">Select</option>
                        <option value="internal_hex" <?= $hex === 'internal_hex' ? 'selected' : '' ?>>Internal Hex</option>
                        <option value="multiunit" <?= $hex === 'multiunit' ? 'selected' : '' ?>>Multiunit</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">C. Healing cap</label>
                    <select class="form-select" name="implant_healing_cap" <?= $canEdit ? '' : 'disabled' ?>>
                        <?php $hc = (string) ($implant['healing_cap'] ?? ''); ?>
                        <option value="">Select</option>
                        <option value="yes" <?= $hc === 'yes' ? 'selected' : '' ?>>Yes</option>
                        <option value="no" <?= $hc === 'no' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">D. Loading</label>
                    <select class="form-select" name="implant_loading" <?= $canEdit ? '' : 'disabled' ?>>
                        <?php $ld = (string) ($implant['loading'] ?? ''); ?>
                        <option value="">Select</option>
                        <option value="immediate" <?= $ld === 'immediate' ? 'selected' : '' ?>>Immediate Loading</option>
                        <option value="delayed" <?= $ld === 'delayed' ? 'selected' : '' ?>>Delayed Loading</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">F. Product for lab</label>
                    <input class="form-control" name="implant_lab_product" value="<?= e($implant['lab_product'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">E. Next Appt Date</label>
                    <input class="form-control" type="date" name="implant_next_date" value="<?= e($implant['next_date'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">E. Next Appt Time</label>
                    <input class="form-control" type="time" name="implant_next_time" value="<?= e(isset($implant['next_time']) ? substr((string) $implant['next_time'], 0, 5) : '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E. Work to be done</label>
                    <input class="form-control" name="implant_work_done" value="<?= e($implant['work_to_be_done'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    <div class="form-text">Implant next appt pan alag treatment slot tarike calendar ma book thase.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">F.a Substructure</label>
                    <select class="form-select" name="implant_substructure" <?= $canEdit ? '' : 'disabled' ?>>
                        <?php $sub = (string) ($implant['substructure'] ?? ''); ?>
                        <option value="">Select</option>
                        <option value="co_cr" <?= $sub === 'co_cr' ? 'selected' : '' ?>>Co-Cr</option>
                        <option value="titanium" <?= $sub === 'titanium' ? 'selected' : '' ?>>Titanium</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">F.b Superstructure</label>
                    <input class="form-control" name="implant_superstructure" value="<?= e($implant['superstructure'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>>
                </div>
                <div class="col-12">
                    <label class="form-label">B. Notation (tooth notes for implant)</label>
                    <textarea class="form-control" name="implant_notation" rows="2" placeholder="Palmer / tooth notation notes" <?= $canEdit ? '' : 'readonly' ?>><?= e($implant['notation'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <?php if ($canEdit): ?>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Save Clinical Chart</button>
            <span class="text-muted small ms-2">Save pachi Next Appt calendar ma auto book thase</span>
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

  root.querySelectorAll('.clinical-other-toggle').forEach(function (toggle) {
    const target = document.querySelector(toggle.getAttribute('data-target'));
    if (!target) return;
    const sync = function () {
      target.classList.toggle('d-none', !toggle.checked);
      if (!toggle.checked) {
        const input = target.querySelector('input, textarea');
        if (input) input.value = '';
      } else {
        const input = target.querySelector('input, textarea');
        if (input) input.focus();
      }
    };
    toggle.addEventListener('change', sync);
  });

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
})();
</script>
<?php endif; ?>
