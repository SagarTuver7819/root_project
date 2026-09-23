<?php
$patientId = (int) ($id ?? 0);
$canEdit = can('patients.edit');
$doctors = $doctors ?? [];
$savedItems = $savedItems ?? [];
$toothNotes = $toothNotes ?? [];
$treatmentSuggestions = $treatmentSuggestions ?? [];

$minRows = 5;
$rows = $savedItems;
while (count($rows) < $minRows) {
    $rows[] = ['id' => '', 'description' => '', 'doctor_id' => '', 'teeth' => '', 'amount' => ''];
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

$toothLabel = static function (string $code): string {
    $q = ['UR' => 'Upper Right', 'UL' => 'Upper Left', 'LR' => 'Lower Right', 'LL' => 'Lower Left'];
    $prefix = substr($code, 0, 2);
    $num = substr($code, 2);
    return ($q[$prefix] ?? $prefix) . ' ' . $num;
};

$renderPalmerTeeth = static function (array $codes, bool $canEdit, array $selectedTeeth, array $toothNotes, string $arch): void {
    foreach ($codes as $code) {
        $label = preg_replace('/^[A-Z]{2}/', '', $code) ?? $code;
        $on = !empty($selectedTeeth[$code]);
        $note = trim((string) ($toothNotes[$code] ?? ''));
        $noteShort = $note;
        if (mb_strlen($noteShort) > 18) {
            $noteShort = mb_substr($noteShort, 0, 18) . '…';
        }
        ?>
        <div class="palmer-tooth-cell palmer-arch-<?= e($arch) ?>" data-tooth-cell="<?= e($code) ?>">
            <?php if ($arch === 'upper'): ?>
                <span class="palmer-tooth-note<?= $note === '' ? ' is-empty' : '' ?>" title="<?= e($note) ?>"><?= e($noteShort) ?></span>
            <?php endif; ?>
            <button type="button" class="palmer-tooth<?= $on ? ' is-selected' : '' ?>" data-tooth="<?= e($code) ?>" <?= $canEdit ? '' : 'disabled' ?>><?= e($label) ?></button>
            <?php if ($arch === 'lower'): ?>
                <span class="palmer-tooth-note<?= $note === '' ? ' is-empty' : '' ?>" title="<?= e($note) ?>"><?= e($noteShort) ?></span>
            <?php endif; ?>
        </div>
        <?php
    }
};
?>

<form method="post" action="<?= app_url('patients/' . $patientId . '/suggested-plan') ?>" class="ajax-form suggested-plan-form" data-redirect="<?= e(app_url('patients/' . $patientId . '?tab=estimate')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="tooth_notes" id="suggestedPlanToothNotes" value="<?= e(json_encode($toothNotes ?: new stdClass(), JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT)) ?>">

    <div class="palmer-wrap">
        <div class="palmer-wrap-title">Dental Chart</div>
        <div class="palmer-arch">
            <div class="palmer-arch-label permanent"><i class="bi bi-emoji-smile"></i> Permanent Teeth</div>
            <div class="palmer-quad-labels"><span>Upper Right</span><span class="text-end">Upper Left</span></div>
            <div class="palmer-grid">
                <?php $renderPalmerTeeth(['UR8','UR7','UR6','UR5','UR4','UR3','UR2','UR1','UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8'], $canEdit, $selectedTeeth, $toothNotes, 'upper'); ?>
                <?php $renderPalmerTeeth(['LR8','LR7','LR6','LR5','LR4','LR3','LR2','LR1','LL1','LL2','LL3','LL4','LL5','LL6','LL7','LL8'], $canEdit, $selectedTeeth, $toothNotes, 'lower'); ?>
            </div>
            <div class="palmer-quad-labels lower"><span>Lower Right</span><span class="text-end">Lower Left</span></div>
        </div>
        <div class="palmer-arch">
            <div class="palmer-arch-label deciduous"><i class="bi bi-emoji-smile"></i> Deciduous Teeth</div>
            <div class="palmer-quad-labels"><span>Upper Right</span><span class="text-end">Upper Left</span></div>
            <div class="palmer-grid deciduous">
                <?php $renderPalmerTeeth(['URE','URD','URC','URB','URA','ULA','ULB','ULC','ULD','ULE'], $canEdit, $selectedTeeth, $toothNotes, 'upper'); ?>
                <?php $renderPalmerTeeth(['LRE','LRD','LRC','LRB','LRA','LLA','LLB','LLC','LLD','LLE'], $canEdit, $selectedTeeth, $toothNotes, 'lower'); ?>
            </div>
            <div class="palmer-quad-labels lower"><span>Lower Right</span><span class="text-end">Lower Left</span></div>
        </div>
        <p class="palmer-hint mb-0">Tooth note save thay pachhi chart par nana axar ma note dekhase.</p>
    </div>

    <div class="suggested-plan-head">
        <h3 class="h5 mb-1">Suggested Treatment Plan <span class="badge text-bg-warning ms-1">Pending</span></h3>
        <p class="text-muted small mb-0">Tooth-wise hierarchy — ek tooth, ek line. Amount = full treatment. Navi / unpaid treatment par <strong>Add appointment</strong> chalse. Payment/complete thay pachhi calendar button hide. Next visit booking Complete modal mathi pan thase.</p>
    </div>

    <div id="suggestedPlanRows" class="suggested-plan-list">
        <?php foreach ($rows as $index => $row): ?>
            <?php
            $n = $index + 1;
            $rowId = (string) ($row['id'] ?? '');
            $desc = (string) ($row['description'] ?? '');
            $docId = (string) ($row['doctor_id'] ?? '');
            $amt = $row['amount'] ?? '';
            $paidAmt = (float) ($row['paid_amount'] ?? 0);
            if ($amt !== '' && $amt !== null) {
                $amt = number_format((float) $amt, 2, '.', '');
                if ((float) $amt <= 0) {
                    $amt = '';
                }
            } else {
                $amt = '';
            }
            $dueAmt = max(0, (float) ($amt !== '' ? $amt : 0) - $paidAmt);
            $isLocked = $paidAmt > 0; // advance / payment start thay gya to edit bandh
            $teeth = trim((string) ($row['teeth'] ?? ''));
            $toothList = $teeth === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $teeth))));
            $primaryTooth = $toothList[0] ?? '';
            ?>
            <div class="suggested-plan-row<?= $index === 0 ? ' is-active' : '' ?><?= $isLocked ? ' is-locked' : '' ?>" data-index="<?= (int) $index ?>" data-tooth="<?= e($primaryTooth) ?>" data-locked="<?= $isLocked ? '1' : '0' ?>">
                <div class="suggested-plan-num"><?= (int) $n ?></div>
                <div class="suggested-plan-fields">
                    <input type="hidden" name="items[<?= (int) $index ?>][id]" value="<?= e($rowId) ?>">
                    <input type="hidden" class="suggested-plan-teeth-input" name="items[<?= (int) $index ?>][teeth]" value="<?= e($teeth) ?>">
                    <div class="suggested-plan-tooth-label<?= $primaryTooth === '' ? ' d-none' : '' ?>">
                        <i class="bi bi-tooth me-1"></i><span class="tooth-label-text"><?= $primaryTooth !== '' ? e($toothLabel($primaryTooth)) : '' ?></span>
                    </div>
                    <div class="suggested-plan-desc-amount">
                        <input
                            class="form-control suggested-plan-desc"
                            type="text"
                            name="items[<?= (int) $index ?>][description]"
                            value="<?= e($desc) ?>"
                            placeholder="Treatment <?= (int) $n ?>"
                            <?= $n === 1 ? 'required' : '' ?>
                            <?= ($canEdit && !$isLocked) ? '' : 'readonly' ?>
                        >
                        <div class="suggested-plan-amount-wrap">
                            <span class="suggested-plan-amount-prefix">₹</span>
                            <input
                                class="form-control suggested-plan-amount"
                                type="number"
                                step="0.01"
                                min="0"
                                name="items[<?= (int) $index ?>][amount]"
                                value="<?= e($amt) ?>"
                                placeholder="Amount"
                                <?= ($canEdit && !$isLocked) ? '' : 'readonly' ?>
                            >
                        </div>
                    </div>
                    <?php if ($paidAmt > 0 || ($dueAmt > 0 && $amt !== '')): ?>
                        <div class="small mt-1">
                            <?php if ($paidAmt > 0 && $dueAmt <= 0): ?>
                                <span class="badge text-bg-success">Payment Done ₹<?= e(number_format($paidAmt, 2)) ?></span>
                                <span class="badge text-bg-secondary">Locked — edit nathi</span>
                            <?php else: ?>
                                <?php if ($paidAmt > 0): ?>
                                    <span class="badge text-bg-success">Paid ₹<?= e(number_format($paidAmt, 2)) ?></span>
                                <?php endif; ?>
                                <?php if ($dueAmt > 0 && $amt !== ''): ?>
                                    <span class="badge text-bg-warning">Due ₹<?= e(number_format($dueAmt, 2)) ?></span>
                                <?php endif; ?>
                                <?php if ($isLocked): ?>
                                    <span class="badge text-bg-secondary">Name/Amount locked</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="suggested-plan-teeth">
                        <?php foreach ($toothList as $tooth): ?>
                            <span class="tooth-chip"><?= e($tooth) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="suggested-plan-actions">
                        <select class="form-select no-select2 suggested-plan-doctor" name="items[<?= (int) $index ?>][doctor_id]" <?= ($canEdit && !$isLocked) ? '' : 'disabled' ?>>
                            <option value="">Add treating doctor</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?= e((string) $d['id']) ?>" <?= $docId === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isLocked): ?>
                            <input type="hidden" name="items[<?= (int) $index ?>][doctor_id]" value="<?= e($docId) ?>">
                        <?php endif; ?>
                        <?php if ($canEdit && can('appointments.add') && !$isLocked): ?>
                            <button type="button" class="btn btn-outline-primary suggested-plan-book">
                                <i class="bi bi-calendar-plus me-1"></i>Add appointment in calendar
                            </button>
                        <?php endif; ?>
                        <?php if ($canEdit && $rowId !== '' && $dueAmt > 0): ?>
                            <button
                                type="button"
                                class="btn btn-outline-success suggested-plan-collect"
                                data-id="<?= e($rowId) ?>"
                                data-desc="<?= e($desc) ?>"
                                data-due="<?= e(number_format($dueAmt, 2, '.', '')) ?>"
                            >
                                <i class="bi bi-cash-coin me-1"></i>Collect Advance
                            </button>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-success suggested-plan-complete"
                                data-paid="<?= e(number_format($paidAmt, 2, '.', '')) ?>"
                                data-due="<?= e(number_format($dueAmt, 2, '.', '')) ?>">
                                <i class="bi bi-check2-circle me-1"></i>Treatment Complete
                            </button>
                        <?php endif; ?>
                        <?php if ($canEdit && $n > $minRows && !$isLocked): ?>
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
            <?php if (can('quotations.add')): ?>
                <a class="btn btn-outline-primary" href="<?= app_url('patients/' . $patientId . '?tab=estimate') ?>">
                    <i class="bi bi-file-earmark-text me-1"></i>Treatment Estimate
                </a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Save &amp; Open Estimate</button>
        </div>
    <?php endif; ?>
</form>

<?php if ($canEdit): ?>
<template id="suggestedPlanRowTpl">
    <div class="suggested-plan-row" data-index="__INDEX__" data-tooth="">
        <div class="suggested-plan-num">__NUM__</div>
        <div class="suggested-plan-fields">
            <input type="hidden" name="items[__INDEX__][id]" value="">
            <input type="hidden" class="suggested-plan-teeth-input" name="items[__INDEX__][teeth]" value="">
            <div class="suggested-plan-tooth-label d-none">
                <i class="bi bi-tooth me-1"></i><span class="tooth-label-text"></span>
            </div>
            <div class="suggested-plan-desc-amount">
                <input class="form-control suggested-plan-desc" type="text" name="items[__INDEX__][description]" placeholder="Treatment __NUM__">
                <div class="suggested-plan-amount-wrap">
                    <span class="suggested-plan-amount-prefix">₹</span>
                    <input class="form-control suggested-plan-amount" type="number" step="0.01" min="0" name="items[__INDEX__][amount]" placeholder="Amount">
                </div>
            </div>
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
                <button type="button" class="btn btn-success suggested-plan-complete" data-paid="0" data-due="0">
                    <i class="bi bi-check2-circle me-1"></i>Treatment Complete
                </button>
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
                <div class="tooth-note-autocomplete position-relative">
                    <textarea class="form-control" id="toothNoteInput" rows="4" placeholder="Write note for this tooth..." autocomplete="off"></textarea>
                    <div id="toothNoteSuggestDropdown" class="tooth-note-suggest-dropdown d-none" role="listbox"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="toothNoteClear">Clear</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="toothNoteSave">Save Note</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="treatmentCompleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog tc-complete-dialog modal-dialog-scrollable">
        <form class="modal-content" id="treatmentCompleteForm">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Treatment Complete — <span id="tcTreatmentLabel"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body tc-complete-body">
                <?= csrf_field() ?>
                <input type="hidden" id="tcItemId" value="">
                <input type="hidden" id="tcDoctorId" value="">
                <div class="row g-3 tc-complete-row h-100">
                    <div class="col-lg-3 tc-complete-form-col">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" id="tcRemarks" rows="2" placeholder="Treatment remarks..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Consent Book Number</label>
                                <input class="form-control" type="text" id="tcConsentBook" placeholder="Aaje kareli treatment no consent book no." maxlength="100">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Next Appointment Date</label>
                                <input class="form-control" type="date" id="tcNextApptDate">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Next Appointment Time</label>
                                <input class="form-control" type="time" id="tcNextApptTime" step="60">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Next appointment ma kai treatment?</label>
                                <select class="form-select no-select2" id="tcNextTreatmentId">
                                    <option value="">— Select next treatment —</option>
                                </select>
                                <div class="form-text">Niche plan ma jei treatments che te select karo. Calendar booking e treatment par link thase.</div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border small mb-0 py-2" id="tcSlotHint">
                                    Right side calendar ma doctor ni appointments jovo. Free slot par click kariye to date/time auto fill thase.
                                </div>
                                <div class="alert alert-warning small d-none mb-0 mt-2 py-2" id="tcConflictWarn">
                                    <i class="bi bi-exclamation-triangle me-1"></i><span id="tcConflictText">Aa time e doctor ni biji appointment che.</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Specific Instruction for Patient</label>
                                <textarea class="form-control" id="tcPatientInstruction" rows="2" placeholder="Instructions for patient..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Treatment Amount (₹)</label>
                                <input class="form-control" type="number" step="0.01" min="0" id="tcAmount" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Already Paid (₹)</label>
                                <input class="form-control" type="text" id="tcPaidDisplay" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Due (₹)</label>
                                <input class="form-control" type="text" id="tcDueDisplay" readonly>
                            </div>
                            <div class="col-12">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="tcCollectNow" value="1" checked>
                                    <label class="form-check-label" for="tcCollectNow">
                                        Aaje advance / remaining collect kari lidhu
                                    </label>
                                </div>
                                <label class="form-label">Collect Amount Now (₹)</label>
                                <input class="form-control" type="number" step="0.01" min="0" id="tcCollectAmount" placeholder="0.00">
                                <div class="form-text">Full due collect karo athva partial advance. Tick hati rakho to Payments ma receipt banse.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9 tc-complete-cal-col">
                        <div class="tc-cal-panel border rounded p-2 p-md-3 h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold small text-uppercase text-muted mb-0">Doctor Calendar</div>
                                    <div class="fw-bold fs-5" id="tcCalDoctorLabel">—</div>
                                </div>
                                <span class="badge text-bg-secondary" id="tcCalDayLabel">Select doctor</span>
                            </div>
                            <div id="tcDoctorCalendar" class="tc-doctor-calendar"></div>
                            <p class="small text-muted mb-0 mt-2">Booked slots colored cards ma dekhase. Free area click = next appointment time.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" id="tcSubmitBtn">
                    <i class="bi bi-check2 me-1"></i>Ok — Mark Completed
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="planCollectPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="planCollectPaymentForm">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Collect Advance — <span id="planCollectDescLabel"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" id="planCollectItemId" value="">
                <div class="alert alert-light border small">
                    Full amount plan ma lakho (ex. ₹4500). 1st visit ma ahiya partial amount collect karo (ex. ₹2000). Treatment Complete last visit ma karo.
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Amount (₹)</label>
                    <input class="form-control" type="text" id="planCollectDueDisplay" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Collect Now (₹) <span class="required-star">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" id="planCollectAmount" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Mode</label>
                    <select class="form-select no-select2" id="planCollectMode" data-no-select2="1">
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Card">Card</option>
                        <option value="GPay">GPay</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input class="form-control" type="date" id="planCollectDate" value="<?= e(date('Y-m-d')) ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" id="planCollectRemarks" rows="2" placeholder="Advance / Visit 1"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" id="planCollectSubmitBtn">
                    <i class="bi bi-check2 me-1"></i>Collect
                </button>
            </div>
        </form>
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
  const masterSuggestions = <?= json_encode(array_values(array_filter(array_map(static fn ($r) => trim((string) ($r['name'] ?? '')), $treatmentSuggestions)))) ?>;
  const minRows = 5;
  let activeTooth = '';
  const notesField = document.getElementById('suggestedPlanToothNotes');
  const input = document.getElementById('toothNoteInput');
  const suggestDropdown = document.getElementById('toothNoteSuggestDropdown');
  const modalEl = document.getElementById('toothNoteModal');
  const modal = (modalEl && window.bootstrap) ? new bootstrap.Modal(modalEl) : null;
  const quad = { UR: 'Upper Right', UL: 'Upper Left', LR: 'Lower Right', LL: 'Lower Left' };
  let suggestActiveIndex = -1;

  function parseTeethNotes() {
    try {
      const data = JSON.parse(notesField && notesField.value ? notesField.value : '{}');
      return (data && typeof data === 'object' && !Array.isArray(data)) ? data : {};
    } catch (err) {
      return {};
    }
  }

  function toothTitle(code) {
    const prefix = (code || '').slice(0, 2);
    const num = (code || '').slice(2);
    return (quad[prefix] ? quad[prefix] + ' ' : '') + num;
  }

  function parseTeeth(value) {
    return String(value || '').split(',').map(function (v) { return v.trim(); }).filter(Boolean);
  }

  function escapeHtml(text) {
    return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function updateRowToothMeta(row) {
    const teeth = parseTeeth(row.querySelector('.suggested-plan-teeth-input')?.value || '');
    const primary = teeth[0] || '';
    row.dataset.tooth = primary;
    const labelWrap = row.querySelector('.suggested-plan-tooth-label');
    const labelText = row.querySelector('.tooth-label-text');
    if (labelWrap && labelText) {
      if (primary) {
        labelText.textContent = toothTitle(primary);
        labelWrap.classList.remove('d-none');
      } else {
        labelText.textContent = '';
        labelWrap.classList.add('d-none');
      }
    }
  }

  function renderChips(row) {
    const teethInput = row.querySelector('.suggested-plan-teeth-input');
    const box = row.querySelector('.suggested-plan-teeth');
    if (!teethInput || !box) return;
    const teeth = parseTeeth(teethInput.value);
    const notes = parseTeethNotes();
    box.innerHTML = teeth.map(function (t) {
      const note = notes[t] ? escapeHtml(notes[t]) : '';
      return '<span class="tooth-chip"' + (note ? ' title="' + note + '"' : '') + '>' + escapeHtml(t) + '</span>';
    }).join('');
    updateRowToothMeta(row);
  }

  function paintChart() {
    const selected = {};
    list.querySelectorAll('.suggested-plan-teeth-input').forEach(function (teethInput) {
      parseTeeth(teethInput.value).forEach(function (t) { selected[t] = true; });
    });
    form.querySelectorAll('.palmer-tooth').forEach(function (btn) {
      btn.classList.toggle('is-selected', !!selected[btn.dataset.tooth]);
    });
    paintToothNotes();
  }

  function paintToothNotes() {
    const notes = parseTeethNotes();
    form.querySelectorAll('.palmer-tooth-cell').forEach(function (cell) {
      const code = cell.getAttribute('data-tooth-cell') || '';
      const noteEl = cell.querySelector('.palmer-tooth-note');
      if (!noteEl) return;
      const note = String(notes[code] || '').trim();
      const short = note.length > 18 ? (note.slice(0, 18) + '…') : note;
      noteEl.textContent = short;
      noteEl.setAttribute('title', note);
      noteEl.classList.toggle('is-empty', note === '');
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
      updateRowToothMeta(row);
    });
  }

  function ensureExtraRow() {
    if (!tpl) return null;
    const html = tpl.innerHTML
      .replace(/__INDEX__/g, String(list.children.length))
      .replace(/__NUM__/g, String(list.children.length + 1));
    list.insertAdjacentHTML('beforeend', html);
    reindex();
    return list.querySelector('.suggested-plan-row:last-child');
  }

  function findRowForTooth(code) {
    const rows = Array.from(list.querySelectorAll('.suggested-plan-row'));
    // Exact single-tooth match first
    for (let i = 0; i < rows.length; i++) {
      const teeth = parseTeeth(rows[i].querySelector('.suggested-plan-teeth-input')?.value || '');
      if (teeth.length === 1 && teeth[0] === code) return rows[i];
    }
    // Any row already linked to this tooth
    for (let i = 0; i < rows.length; i++) {
      const teeth = parseTeeth(rows[i].querySelector('.suggested-plan-teeth-input')?.value || '');
      if (teeth.indexOf(code) >= 0) return rows[i];
    }
    // Empty row
    for (let i = 0; i < rows.length; i++) {
      const teeth = parseTeeth(rows[i].querySelector('.suggested-plan-teeth-input')?.value || '');
      const desc = (rows[i].querySelector('.suggested-plan-desc')?.value || '').trim();
      if (teeth.length === 0 && desc === '') return rows[i];
    }
    return ensureExtraRow();
  }

  function detachToothFromOtherRows(code, keepRow) {
    list.querySelectorAll('.suggested-plan-row').forEach(function (row) {
      if (row === keepRow) return;
      const teethInput = row.querySelector('.suggested-plan-teeth-input');
      if (!teethInput) return;
      const teeth = parseTeeth(teethInput.value).filter(function (t) { return t !== code; });
      teethInput.value = teeth.join(',');
      renderChips(row);
    });
  }

  function setActiveRow(row) {
    list.querySelectorAll('.suggested-plan-row').forEach(function (r) { r.classList.remove('is-active'); });
    if (row) row.classList.add('is-active');
  }

  function collectOldSuggestions() {
    const notes = parseTeethNotes();
    const items = [];
    const seen = {};

    function push(value) {
      const text = String(value || '').trim();
      const key = text.toLowerCase();
      if (!text || seen[key]) return;
      seen[key] = true;
      items.push(text);
    }

    Object.keys(notes).forEach(function (t) {
      push(notes[t]);
    });

    list.querySelectorAll('.suggested-plan-desc').forEach(function (el) {
      push(el.value);
    });

    (masterSuggestions || []).forEach(function (name) {
      push(name);
    });

    return items;
  }

  function hideSuggestDropdown() {
    if (!suggestDropdown) return;
    suggestDropdown.classList.add('d-none');
    suggestDropdown.innerHTML = '';
    suggestActiveIndex = -1;
  }

  function showSuggestDropdown(query) {
    if (!suggestDropdown || !input) return;
    const q = String(query || '').trim().toLowerCase();
    if (q.length < 1) {
      hideSuggestDropdown();
      return;
    }

    const matches = collectOldSuggestions().filter(function (item) {
      return item.toLowerCase().indexOf(q) >= 0;
    }).slice(0, 8);

    if (!matches.length) {
      hideSuggestDropdown();
      return;
    }

    suggestActiveIndex = -1;
    suggestDropdown.innerHTML = matches.map(function (item, idx) {
      return '<button type="button" class="tooth-note-suggest-item" role="option" data-index="' + idx + '" data-value="' + escapeHtml(item) + '">'
        + escapeHtml(item)
        + '</button>';
    }).join('');
    suggestDropdown.classList.remove('d-none');
  }

  function applySuggestion(value) {
    if (!input) return;
    input.value = value;
    hideSuggestDropdown();
    input.focus();
  }

  function setActiveSuggestion(index) {
    if (!suggestDropdown) return;
    const items = suggestDropdown.querySelectorAll('.tooth-note-suggest-item');
    if (!items.length) return;
    suggestActiveIndex = Math.max(0, Math.min(index, items.length - 1));
    items.forEach(function (el, i) {
      el.classList.toggle('is-active', i === suggestActiveIndex);
    });
    items[suggestActiveIndex]?.scrollIntoView({ block: 'nearest' });
  }

  list.addEventListener('click', function (e) {
    const row = e.target.closest('.suggested-plan-row');
    if (row && !e.target.closest('.suggested-plan-remove, .suggested-plan-book, .suggested-plan-complete, .suggested-plan-collect')) {
      setActiveRow(row);
    }
  });

  form.querySelectorAll('.palmer-tooth').forEach(function (btn) {
    btn.addEventListener('click', function () {
      activeTooth = this.dataset.tooth;
      const row = findRowForTooth(activeTooth) || list.querySelector('.suggested-plan-row');
      if (row) setActiveRow(row);
      document.getElementById('toothNoteIdLabel').textContent = toothTitle(activeTooth);
      const notes = parseTeethNotes();
      const existing = notes[activeTooth] || (row?.querySelector('.suggested-plan-desc')?.value || '').trim();
      input.value = notes[activeTooth] || '';
      if (!input.value && existing && parseTeeth(row?.querySelector('.suggested-plan-teeth-input')?.value || '').indexOf(activeTooth) >= 0) {
        input.value = existing;
      }
      hideSuggestDropdown();
      modal && modal.show();
    });
  });

  // Focus note box after modal is fully open (so no extra click needed).
  modalEl?.addEventListener('shown.bs.modal', function () {
    if (!input) return;
    input.focus({ preventScroll: true });
    const len = input.value.length;
    try { input.setSelectionRange(len, len); } catch (err) { /* ignore */ }
  });

  input?.addEventListener('input', function () {
    showSuggestDropdown(this.value);
  });

  input?.addEventListener('keydown', function (e) {
    if (!suggestDropdown || suggestDropdown.classList.contains('d-none')) return;
    const items = suggestDropdown.querySelectorAll('.tooth-note-suggest-item');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveSuggestion(suggestActiveIndex + 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveSuggestion(suggestActiveIndex <= 0 ? items.length - 1 : suggestActiveIndex - 1);
    } else if (e.key === 'Enter' && suggestActiveIndex >= 0) {
      e.preventDefault();
      const selected = items[suggestActiveIndex];
      if (selected) applySuggestion(selected.getAttribute('data-value') || '');
    } else if (e.key === 'Escape') {
      hideSuggestDropdown();
    }
  });

  suggestDropdown?.addEventListener('mousedown', function (e) {
    const item = e.target.closest('.tooth-note-suggest-item');
    if (!item) return;
    e.preventDefault();
    applySuggestion(item.getAttribute('data-value') || '');
  });

  input?.addEventListener('blur', function () {
    setTimeout(hideSuggestDropdown, 150);
  });

  modalEl?.addEventListener('hidden.bs.modal', hideSuggestDropdown);

  document.getElementById('toothNoteSave')?.addEventListener('click', function () {
    if (!activeTooth) return;
    const val = input.value.trim();
    const notes = parseTeethNotes();
    let row = findRowForTooth(activeTooth);
    if (!row) return;
    if (row.dataset.locked === '1') {
      toastr.warning('Payment start thai gaya line edit nathi thai shakti.');
      return;
    }

    detachToothFromOtherRows(activeTooth, row);
    const hiddenTeeth = row.querySelector('.suggested-plan-teeth-input');
    const desc = row.querySelector('.suggested-plan-desc');

    if (val) {
      notes[activeTooth] = val;
      hiddenTeeth.value = activeTooth;
      if (desc) desc.value = val;
    } else {
      delete notes[activeTooth];
      hiddenTeeth.value = '';
    }

    notesField.value = JSON.stringify(notes);
    setActiveRow(row);
    renderChips(row);
    paintChart();
    hideSuggestDropdown();
    modal && modal.hide();
    if (window.toastr) {
      toastr.success(val
        ? ('Saved for ' + toothTitle(activeTooth) + ' as separate treatment line.')
        : 'Tooth note cleared.');
    }
  });

  document.getElementById('toothNoteClear')?.addEventListener('click', function () {
    input.value = '';
    hideSuggestDropdown();
  });

  document.getElementById('addSuggestedPlanRow')?.addEventListener('click', function () {
    ensureExtraRow();
  });

  const completeUrlBase = <?= json_encode(rtrim(app_url('patients/' . $patientId . '/suggested-plan'), '/') . '/') ?>;
  const eventsUrl = <?= json_encode(app_url('calendar/events')) ?>;
  const tcModalEl = document.getElementById('treatmentCompleteModal');
  const tcModal = (tcModalEl && window.bootstrap) ? new bootstrap.Modal(tcModalEl) : null;
  const planCollectModalEl = document.getElementById('planCollectPaymentModal');
  const planCollectModal = (planCollectModalEl && window.bootstrap) ? new bootstrap.Modal(planCollectModalEl) : null;
  let tcActiveRow = null;
  let tcCalendar = null;
  let tcBookedRanges = [];
  let tcPickEvent = null;

  function pad2(n) { return String(n).padStart(2, '0'); }

  function toDateInputValue(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  function toTimeInputValue(d) {
    return pad2(d.getHours()) + ':' + pad2(d.getMinutes());
  }

  function parseLocalDateTime(dateStr, timeStr) {
    if (!dateStr || !timeStr) return null;
    const t = timeStr.length === 5 ? timeStr + ':00' : timeStr;
    const d = new Date(dateStr + 'T' + t);
    return isNaN(d.getTime()) ? null : d;
  }

  function checkTcConflict() {
    const warn = document.getElementById('tcConflictWarn');
    const text = document.getElementById('tcConflictText');
    const dateStr = document.getElementById('tcNextApptDate').value;
    const timeStr = document.getElementById('tcNextApptTime').value;
    if (!warn) return;
    if (!dateStr || !timeStr) {
      warn.classList.add('d-none');
      return;
    }
    const start = parseLocalDateTime(dateStr, timeStr);
    if (!start) {
      warn.classList.add('d-none');
      return;
    }
    const end = new Date(start.getTime() + 30 * 60 * 1000);
    let hit = null;
    tcBookedRanges.forEach(function (r) {
      if (hit) return;
      if (start < r.end && end > r.start) hit = r;
    });
    if (hit) {
      text.textContent = 'Aa time e doctor ni biji appointment che: ' + (hit.title || 'Booked slot');
      warn.classList.remove('d-none');
    } else {
      warn.classList.add('d-none');
    }
  }

  function destroyTcCalendar() {
    tcPickEvent = null;
    if (tcCalendar) {
      tcCalendar.destroy();
      tcCalendar = null;
    }
    tcBookedRanges = [];
  }

  function setTcPickSlot(startDate, endDate) {
    if (!tcCalendar || !startDate) return;
    const start = startDate instanceof Date ? startDate : new Date(startDate);
    if (isNaN(start.getTime())) return;
    let end = endDate instanceof Date ? endDate : (endDate ? new Date(endDate) : null);
    if (!end || isNaN(end.getTime())) {
      end = new Date(start.getTime() + 30 * 60 * 1000);
    }
    if (tcPickEvent) {
      tcPickEvent.remove();
      tcPickEvent = null;
    }
    tcPickEvent = tcCalendar.addEvent({
      id: 'tc-selected-slot',
      title: 'Selected next appointment',
      start: start,
      end: end,
      backgroundColor: '#0EA5E9',
      borderColor: '#0284C7',
      textColor: '#ffffff',
      editable: false,
      overlap: true,
      classNames: ['tc-pick-slot'],
      extendedProps: { entry_type: 'tc_pick' }
    });
    document.getElementById('tcNextApptDate').value = toDateInputValue(start);
    document.getElementById('tcNextApptTime').value = toTimeInputValue(start);
    document.getElementById('tcCalDayLabel').textContent = toDateInputValue(start);
    checkTcConflict();
  }

  function syncTcPickFromInputs() {
    const dateStr = document.getElementById('tcNextApptDate')?.value || '';
    const timeStr = document.getElementById('tcNextApptTime')?.value || '';
    if (!dateStr || !timeStr || !tcCalendar) return;
    const start = parseLocalDateTime(dateStr, timeStr);
    if (!start) return;
    setTcPickSlot(start);
  }

  function initTcCalendar(doctorId, jumpDate) {
    const el = document.getElementById('tcDoctorCalendar');
    if (!el || !window.FullCalendar || !doctorId) return;
    destroyTcCalendar();
    const initial = jumpDate || document.getElementById('tcNextApptDate').value || toDateInputValue(new Date());
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const fmtDMY = function (d) {
      return pad2(d.getDate()) + '-' + pad2(d.getMonth() + 1) + '-' + d.getFullYear();
    };
    const calH = Math.max(480, Math.floor(window.innerHeight * 0.97 - 220));

    tcCalendar = new FullCalendar.Calendar(el, {
      views: {
        timeGridFourDay: {
          type: 'timeGrid',
          duration: { days: 4 },
          buttonText: '4 Days'
        }
      },
      initialView: 'timeGridFourDay',
      firstDay: 1,
      initialDate: initial,
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'timeGridFourDay,timeGridDay,timeGridWeek,dayGridMonth'
      },
      buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day' },
      height: calH,
      expandRows: true,
      allDaySlot: false,
      nowIndicator: true,
      selectable: true,
      selectMirror: true,
      slotMinTime: '07:00:00',
      slotMaxTime: '22:00:00',
      slotDuration: '00:30:00',
      slotLabelInterval: '01:00:00',
      eventDisplay: 'block',
      stickyHeaderDates: true,
      slotLabelFormat: {
        hour: 'numeric',
        minute: '2-digit',
        omitZeroMinute: false,
        meridiem: 'short',
        hour12: true
      },
      eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short',
        hour12: true
      },
      dayHeaderContent: function (arg) {
        return {
          html: '<div class="fc-day-head"><span class="fc-day-name">' + weekdays[arg.date.getDay()] + '</span><span class="fc-day-date">' + fmtDMY(arg.date) + '</span></div>'
        };
      },
      eventContent: function (arg) {
        const p = arg.event.extendedProps || {};
        const esc = function (s) {
          return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        };
        if ((p.entry_type || '') === 'tc_pick') {
          return {
            html: '<div class="fc-ev"><div class="fc-ev-time">' + esc(arg.timeText) + '</div>'
              + '<div class="fc-ev-name">Selected slot</div></div>'
          };
        }
        if ((p.entry_type || '') === 'doctor_remark') {
          return {
            html: '<div class="fc-ev"><div class="fc-ev-time">' + esc(arg.timeText) + '</div>'
              + '<div class="fc-ev-name">' + esc(arg.event.title) + '</div></div>'
          };
        }
        const name = p.patient_name || arg.event.title || '';
        let sub = p.subtitle || p.treatment_name || '';
        if (!sub && p.visit_reason) {
          sub = String(p.visit_reason).split('|')[0].trim();
          if (sub.length > 60) sub = sub.slice(0, 57) + '…';
        }
        const mobile = p.mobile || '';
        let html = '<div class="fc-ev">';
        html += '<div class="fc-ev-time">' + esc(arg.timeText) + '</div>';
        html += '<div class="fc-ev-name">' + esc(name) + '</div>';
        if (sub) html += '<div class="fc-ev-sub">' + esc(sub) + '</div>';
        if (mobile) html += '<div class="fc-ev-phone">' + esc(mobile) + '</div>';
        html += '</div>';
        return { html: html };
      },
      events: function (info, success, failure) {
        const params = new URLSearchParams({
          start: info.startStr,
          end: info.endStr,
          doctor_id: String(doctorId)
        });
        fetch(eventsUrl + '?' + params.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
          .then(function (r) { return r.json(); })
          .then(function (rows) {
            tcBookedRanges = (rows || []).map(function (ev) {
              return {
                start: new Date(ev.start),
                end: new Date(ev.end),
                title: ev.title || 'Booked'
              };
            });
            checkTcConflict();
            success(rows || []);
          })
          .catch(failure);
      },
      dateClick: function (info) {
        if (!info.dateStr.includes('T')) return;
        setTcPickSlot(info.date);
      },
      select: function (info) {
        setTcPickSlot(info.start, info.end);
        tcCalendar.unselect();
      },
      datesSet: function (info) {
        const titleEl = el.querySelector('.fc-toolbar-title');
        const start = info.start;
        const end = new Date(info.end.getTime() - 1);
        if (titleEl) {
          if (info.view.type === 'timeGridDay') {
            titleEl.textContent = fmtDMY(start);
          } else if (info.view.type === 'dayGridMonth') {
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            titleEl.textContent = months[info.view.currentStart.getMonth()] + ' ' + info.view.currentStart.getFullYear();
          } else {
            titleEl.textContent = fmtDMY(start) + ' – ' + fmtDMY(end);
          }
        }
        const label = document.getElementById('tcCalDayLabel');
        if (label) label.textContent = toDateInputValue(info.view.currentStart || start);
      }
    });
    tcCalendar.render();
    setTimeout(function () { tcCalendar && tcCalendar.updateSize(); }, 80);
  }

  function fillNextTreatmentOptions(currentRow) {
    const sel = document.getElementById('tcNextTreatmentId');
    if (!sel) return;
    const currentId = (currentRow.querySelector('input[name*="[id]"]')?.value || '').trim();
    sel.innerHTML = '<option value="">— Select next treatment —</option>';
    list.querySelectorAll('.suggested-plan-row').forEach(function (r) {
      if (r === currentRow) return;
      const id = (r.querySelector('input[name*="[id]"]')?.value || '').trim();
      const desc = (r.querySelector('.suggested-plan-desc')?.value || '').trim();
      if (!id || !desc) return;
      const teeth = (r.querySelector('.suggested-plan-teeth-input')?.value || '').trim();
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = desc + (teeth ? ' (' + teeth + ')' : '');
      sel.appendChild(opt);
    });
    if (sel.options.length === 1) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.disabled = true;
      opt.textContent = 'Biji pending treatment nathi — pehla Add next treatment karo';
      sel.appendChild(opt);
    }
  }

  function syncTcCollectAmountFromDue() {
    const dueEl = document.getElementById('tcDueDisplay');
    const collectEl = document.getElementById('tcCollectAmount');
    const amountEl = document.getElementById('tcAmount');
    const paidEl = document.getElementById('tcPaidDisplay');
    if (!collectEl) return;
    const full = parseFloat(amountEl?.value || '0') || 0;
    const paid = parseFloat(String(paidEl?.value || '0').replace(/,/g, '')) || 0;
    const due = Math.max(0, full - paid);
    if (dueEl) dueEl.value = due.toFixed(2);
    if (document.getElementById('tcCollectNow')?.checked) {
      collectEl.value = due > 0 ? due.toFixed(2) : '';
    }
  }

  function openCompleteModal(row) {
    const itemId = (row.querySelector('input[name*="[id]"]')?.value || '').trim();
    const desc = (row.querySelector('.suggested-plan-desc')?.value || '').trim();
    const doctorSelect = row.querySelector('.suggested-plan-doctor');
    const doctorId = (doctorSelect?.value || '').trim();
    const doctorName = doctorSelect && doctorSelect.selectedIndex > 0
      ? doctorSelect.options[doctorSelect.selectedIndex].text
      : '';
    if (!desc) {
      toastr.warning('Pehla treatment lakho.');
      row.querySelector('.suggested-plan-desc')?.focus();
      return;
    }
    if (!itemId) {
      toastr.warning('Pehla plan Save karo, pachhi Treatment Complete kari shakay.');
      return;
    }
    tcActiveRow = row;
    document.getElementById('tcItemId').value = itemId;
    document.getElementById('tcDoctorId').value = doctorId;
    document.getElementById('tcTreatmentLabel').textContent = desc;
    document.getElementById('tcRemarks').value = '';
    document.getElementById('tcNextApptDate').value = '';
    document.getElementById('tcNextApptTime').value = '';
    document.getElementById('tcPatientInstruction').value = '';
    const planAmt = (row.querySelector('.suggested-plan-amount')?.value || '').trim();
    document.getElementById('tcAmount').value = planAmt !== '' ? planAmt : '';
    const completeBtn = row.querySelector('.suggested-plan-complete');
    const paid = parseFloat(completeBtn?.dataset.paid || '0') || 0;
    document.getElementById('tcPaidDisplay').value = paid.toFixed(2);
    const full = parseFloat(planAmt || '0') || 0;
    const due = Math.max(0, full - paid);
    document.getElementById('tcDueDisplay').value = due.toFixed(2);
    const collectNow = document.getElementById('tcCollectNow');
    if (collectNow) collectNow.checked = due > 0;
    document.getElementById('tcCollectAmount').value = due > 0 ? due.toFixed(2) : '';
    document.getElementById('tcConsentBook').value = '';
    document.getElementById('tcConflictWarn')?.classList.add('d-none');
    document.getElementById('tcCalDoctorLabel').textContent = doctorName || 'Doctor select nathi';
    document.getElementById('tcCalDayLabel').textContent = doctorId ? 'Loading…' : 'Select treating doctor';
    fillNextTreatmentOptions(row);
    const hint = document.getElementById('tcSlotHint');
    if (hint) {
      hint.textContent = doctorId
        ? 'Right side calendar ma doctor ni appointments jovo. Free slot par click kariye to date/time auto fill thase. Next treatment dropdown thi select karo.'
        : 'Pehla treating doctor select karo — pachhi calendar ma schedule joi shakay.';
    }
    tcModal && tcModal.show();
  }

  tcModalEl?.addEventListener('shown.bs.modal', function () {
    const doctorId = document.getElementById('tcDoctorId').value;
    if (doctorId) {
      initTcCalendar(doctorId);
    } else {
      destroyTcCalendar();
      const el = document.getElementById('tcDoctorCalendar');
      if (el) el.innerHTML = '<div class="text-center text-muted py-5 small">Treating doctor select kari ne calendar jovo.</div>';
    }
  });

  tcModalEl?.addEventListener('hidden.bs.modal', function () {
    destroyTcCalendar();
  });

  document.getElementById('tcNextApptDate')?.addEventListener('change', function () {
    const dateStr = this.value;
    if (tcCalendar && dateStr) {
      tcCalendar.gotoDate(dateStr);
      document.getElementById('tcCalDayLabel').textContent = dateStr;
    }
    syncTcPickFromInputs();
    checkTcConflict();
  });
  document.getElementById('tcNextApptTime')?.addEventListener('change', function () {
    syncTcPickFromInputs();
    checkTcConflict();
  });
  document.getElementById('tcNextApptTime')?.addEventListener('input', checkTcConflict);

  document.getElementById('treatmentCompleteForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const itemId = document.getElementById('tcItemId').value;
    if (!itemId) {
      toastr.error('Treatment line not found.');
      return;
    }
    const nextDate = document.getElementById('tcNextApptDate').value || '';
    const nextTime = document.getElementById('tcNextApptTime').value || '';
    const doctorId = document.getElementById('tcDoctorId').value || '';
    const nextTreatmentId = document.getElementById('tcNextTreatmentId')?.value || '';
    const amount = document.getElementById('tcAmount').value || '0';
    const collectNow = document.getElementById('tcCollectNow')?.checked ? '1' : '0';
    const collectAmount = document.getElementById('tcCollectAmount')?.value || '0';
    if ((nextDate || nextTime) && !doctorId) {
      toastr.warning('Next appointment mate pehla treating doctor select karo.');
      return;
    }
    if ((nextDate && nextTime) && !nextTreatmentId) {
      toastr.warning('Next appointment mate niche thi treatment select karo — kai treatment karvi che?');
      document.getElementById('tcNextTreatmentId')?.focus();
      return;
    }
    let forceOverlap = '0';
    if (nextDate && nextTime && !document.getElementById('tcConflictWarn').classList.contains('d-none')) {
      if (!window.confirm('Aa time e doctor ni biji appointment dekhay che. Tori pan continue karo?')) {
        return;
      }
      forceOverlap = '1';
    }
    const btn = document.getElementById('tcSubmitBtn');
    if (btn) {
      btn.disabled = true;
      btn.dataset.original = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    }
    const body = new FormData();
    body.append('_token', window.CSRF_TOKEN || document.querySelector('#treatmentCompleteForm [name="_token"]')?.value || '');
    body.append('remarks', document.getElementById('tcRemarks').value || '');
    body.append('next_appointment_date', nextDate);
    body.append('next_appointment_time', nextTime);
    body.append('patient_instruction', document.getElementById('tcPatientInstruction').value || '');
    body.append('amount', document.getElementById('tcAmount').value || '0');
    body.append('consent_book_number', document.getElementById('tcConsentBook').value || '');
    body.append('force_overlap', forceOverlap);
    body.append('collect_now', document.getElementById('tcCollectNow')?.checked ? '1' : '0');
    body.append('collect_amount', document.getElementById('tcCollectAmount')?.value || '0');
    body.append('next_treatment_id', document.getElementById('tcNextTreatmentId')?.value || '');

    fetch(completeUrlBase + encodeURIComponent(itemId) + '/complete', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
      },
      body: body
    }).then(function (r) { return r.json().then(function (res) { return { ok: r.ok, status: r.status, res: res }; }).catch(function (err) { return { ok: false, status: r.status, parseError: String(err), res: null }; }); }).then(function (payload) {
      const res = payload.res || {};
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.original || 'Ok — Mark Completed';
      }
      const redirectTo = (res.data && res.data.redirect)
        || <?= json_encode(app_url('patients/' . $patientId . '?tab=plan')) ?>;
      if (payload.parseError || res.success === false) {
        toastr.error((res && res.message) || 'Unable to complete treatment.');
        return;
      }
      toastr.success(res.message || 'Treatment completed.');
      tcModal && tcModal.hide();
      if (tcActiveRow && tcActiveRow.parentNode) {
        tcActiveRow.remove();
      }
      setTimeout(function () { window.location.href = redirectTo; }, 300);
    }).catch(function (err) {
      toastr.error('Unable to complete treatment.');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.original || 'Ok — Mark Completed';
      }
    });
  });

  document.getElementById('tcAmount')?.addEventListener('input', syncTcCollectAmountFromDue);
  document.getElementById('tcCollectNow')?.addEventListener('change', function () {
    const collectEl = document.getElementById('tcCollectAmount');
    if (!collectEl) return;
    if (this.checked) {
      syncTcCollectAmountFromDue();
      collectEl.removeAttribute('readonly');
    } else {
      collectEl.value = '';
      collectEl.setAttribute('readonly', 'readonly');
    }
  });

  list.addEventListener('click', function (e) {
    const remove = e.target.closest('.suggested-plan-remove');
    if (remove) {
      const row = remove.closest('.suggested-plan-row');
      if (row && row.dataset.locked === '1') {
        toastr.warning('Payment valu line delete nathi thai shaktu.');
        return;
      }
      if (list.querySelectorAll('.suggested-plan-row').length > minRows) {
        row.remove();
        reindex();
        paintChart();
      }
      return;
    }

    const completeBtn = e.target.closest('.suggested-plan-complete');
    if (completeBtn) {
      openCompleteModal(completeBtn.closest('.suggested-plan-row'));
      return;
    }

    const collectBtn = e.target.closest('.suggested-plan-collect');
    if (collectBtn) {
      const due = collectBtn.dataset.due || '0';
      document.getElementById('planCollectItemId').value = collectBtn.dataset.id || '';
      document.getElementById('planCollectDescLabel').textContent = collectBtn.dataset.desc || '';
      document.getElementById('planCollectDueDisplay').value = due;
      document.getElementById('planCollectAmount').value = due;
      document.getElementById('planCollectRemarks').value = 'Advance / Visit 1 payment';
      document.getElementById('planCollectDate').value = '<?= e(date('Y-m-d')) ?>';
      document.getElementById('planCollectMode').value = 'Cash';
      planCollectModal && planCollectModal.show();
      return;
    }

    const book = e.target.closest('.suggested-plan-book');
    if (!book) return;
    const row = book.closest('.suggested-plan-row');
    if (row && row.dataset.locked === '1') {
      toastr.warning('Payment lidhu treatment — calendar booking Complete modal mathi karo.');
      return;
    }
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

  document.getElementById('planCollectPaymentForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const itemId = document.getElementById('planCollectItemId').value;
    if (!itemId) {
      toastr.error('Pehla plan Save karo, pachhi advance collect kari shakay.');
      return;
    }
    const btn = document.getElementById('planCollectSubmitBtn');
    if (btn) {
      btn.disabled = true;
      btn.dataset.original = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    }
    const body = new FormData();
    body.append('_token', window.CSRF_TOKEN || document.querySelector('#planCollectPaymentForm [name="_token"]')?.value || '');
    body.append('amount', document.getElementById('planCollectAmount').value || '0');
    body.append('payment_mode', document.getElementById('planCollectMode').value || 'Cash');
    body.append('payment_date', document.getElementById('planCollectDate').value || '');
    body.append('remarks', document.getElementById('planCollectRemarks').value || '');

    fetch(completeUrlBase + encodeURIComponent(itemId) + '/collect', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
      },
      body: body
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (res && res.success === false) {
        toastr.error(res.message || 'Collection failed.');
        return;
      }
      toastr.success((res && res.message) || 'Advance collected.');
      planCollectModal && planCollectModal.hide();
      setTimeout(function () { window.location.reload(); }, 250);
    }).catch(function () {
      toastr.error('Collection failed.');
    }).finally(function () {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.original || 'Collect';
      }
    });
  });
})();
</script>
<?php endif; ?>
