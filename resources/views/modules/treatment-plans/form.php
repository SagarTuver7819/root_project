<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';

$plan = $plan ?? [];
$isEdit = !empty($plan['id']);
$fromCalendar = !empty($fromCalendar);
$appointmentId = $appointmentId ?? null;
?>
<?php if ($fromCalendar): ?>
<div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <strong>Treatment appointment opened from calendar.</strong>
        <div class="small mb-0">Fill treatment details, then use History / Billing from patient profile after treatment.</div>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($plan['patient_id'])): ?>
            <a class="btn btn-sm btn-light" href="<?= app_url('patients/' . $plan['patient_id'] . '?tab=history') ?>">Patient History</a>
            <a class="btn btn-sm btn-success" href="<?= app_url('billing/create?patient_id=' . urlencode((string) $plan['patient_id']) . '&doctor_id=' . urlencode((string) ($plan['doctor_id'] ?? ''))) ?>">Billing</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<form method="post" action="<?= $isEdit ? app_url('treatment-plans/' . $plan['id']) : app_url('treatment-plans') ?>" class="ajax-form">
<?= csrf_field() ?>
<div class="card content-card">
<div class="card-body">
<div class="row g-3">
<div class="col-md-4">
<label class="form-label">Patient <span class="required-star">*</span></label>
<select class="form-control" name="patient_id" required>
<option value="">Select</option>
<?php $selected = old('patient_id', $plan['patient_id'] ?? ''); foreach (($patients ?? []) as $option): ?>
<option value="<?= e($option['id']) ?>" <?= (string) $selected === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<label class="form-label">Doctor <span class="required-star">*</span></label>
<select class="form-control" name="doctor_id" required>
<option value="">Select</option>
<?php $selected = old('doctor_id', $plan['doctor_id'] ?? ''); foreach (($doctors ?? []) as $option): ?>
<option value="<?= e($option['id']) ?>" <?= (string) $selected === (string) $option['id'] ? 'selected' : '' ?>><?= e(doctor_label($option['name'] ?? '')) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<label class="form-label">Treatment <span class="required-star">*</span></label>
<select class="form-control" name="treatment_master_id" required>
<option value="">Select</option>
<?php $selected = old('treatment_master_id', $plan['treatment_master_id'] ?? ''); foreach (($treatments ?? []) as $option): ?>
<option value="<?= e($option['id']) ?>" <?= (string) $selected === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4"><label class="form-label">Tooth Number</label><input class="form-control" type="text" name="tooth_number" value="<?= e(old('tooth_number', $plan['tooth_number'] ?? '')) ?>"></div>
<div class="col-md-4"><label class="form-label">Start Date</label><input class="form-control" type="date" name="start_date" value="<?= e(old('start_date', $plan['start_date'] ?? date('Y-m-d'))) ?>"></div>
<div class="col-md-4"><label class="form-label">Estimated Completion</label><input class="form-control" type="date" name="estimated_completion" value="<?= e(old('estimated_completion', $plan['estimated_completion'] ?? '')) ?>"></div>
<div class="col-md-4"><label class="form-label">Sessions</label><input class="form-control" type="number" name="sessions" value="<?= e(old('sessions', $plan['sessions'] ?? '1')) ?>"></div>
<div class="col-md-4"><label class="form-label">Cost</label><input class="form-control" type="number" step="0.01" name="cost" value="<?= e(old('cost', $plan['cost'] ?? '')) ?>"></div>
<div class="col-md-4"><label class="form-label">Discount</label><input class="form-control" type="number" step="0.01" name="discount" value="<?= e(old('discount', $plan['discount'] ?? '0')) ?>"></div>
<div class="col-md-4"><label class="form-label">Status</label>
<select class="form-select" name="status">
<?php $st = old('status', $plan['status'] ?? 'started'); foreach (['recommended','approved','started','in_progress','completed','cancelled'] as $s): ?>
<option value="<?= e($s) ?>" <?= (string) $st === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$s))) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-12"><label class="form-label">Diagnosis</label><textarea class="form-control" name="diagnosis" rows="3"><?= e(old('diagnosis', $plan['diagnosis'] ?? '')) ?></textarea></div>
<div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e(old('description', $plan['description'] ?? '')) ?></textarea></div>
</div>
<div class="mt-4 d-flex flex-wrap gap-2">
<button type="submit" class="btn btn-primary">Save Treatment</button>
<?php if (!empty($plan['patient_id'])): ?>
<a class="btn btn-outline-success" href="<?= app_url('billing/create?patient_id=' . urlencode((string) $plan['patient_id']) . '&doctor_id=' . urlencode((string) ($plan['doctor_id'] ?? ''))) ?>">Go to Billing</a>
<a class="btn btn-outline-secondary" href="<?= app_url('patients/' . $plan['patient_id'] . '?tab=history') ?>">Patient History</a>
<?php endif; ?>
<a class="btn btn-light" href="<?= app_url('treatment-plans') ?>">Cancel</a>
</div>
</div>
</div>
</form>
