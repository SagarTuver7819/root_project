<?php
$chart = $chart ?? [];
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
?>

<form method="post" action="<?= app_url('patients/' . $patientId . '/clinical-chart') ?>" class="ajax-form clinical-chart-form" data-redirect="<?= e(app_url('patients/' . $patientId . '?tab=plan')) ?>" data-patient-id="<?= (int) $patientId ?>" data-upload-url="<?= e(app_url('patients/' . $patientId . '/documents')) ?>">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold">1. Chief Complaint</label>
            <textarea class="form-control" name="chief_complaint" rows="3" <?= $canEdit ? '' : 'readonly' ?>><?= e($chart['chief_complaint'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="fw-semibold mb-2">2. Medical History</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex flex-wrap gap-3">
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
                    </div>
                    <div id="medicalOtherWrap" class="mt-2 <?= $medicalHistory['other'] !== '' ? '' : 'd-none' ?>">
                        <input
                            class="form-control"
                            type="text"
                            name="medical_other"
                            value="<?= e($medicalHistory['other']) ?>"
                            placeholder="Add other illness"
                            <?= $canEdit ? '' : 'readonly' ?>
                        >
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-label mb-2">Habit</div>
                    <div class="d-flex flex-wrap gap-3">
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
                    </div>
                    <div id="habitOtherWrap" class="mt-2 <?= $habitData['other'] !== '' ? '' : 'd-none' ?>">
                        <input
                            class="form-control"
                            type="text"
                            name="habit_other"
                            value="<?= e($habitData['other']) ?>"
                            placeholder="Add other habit details"
                            <?= $canEdit ? '' : 'readonly' ?>
                        >
                    </div>
                </div>
                <div class="col-12">
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

        <div class="col-12">
            <label class="form-label fw-semibold" for="onExamination">3. On examination</label>
            <input
                class="form-control"
                type="text"
                name="on_examination"
                id="onExamination"
                value="<?= e($chart['on_examination'] ?? '') ?>"
                placeholder="On examination"
                <?= $canEdit ? '' : 'readonly' ?>
            >
        </div>

        <div class="col-md-6">
            <div class="fw-semibold mb-2">4. X-Ray</div>
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
            <div class="fw-semibold mb-2">5. Clinical Pictures</div>
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

        <?php if ($canEdit): ?>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Save Clinical Chart</button>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($canEdit): ?>
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
})();
</script>
<?php endif; ?>
