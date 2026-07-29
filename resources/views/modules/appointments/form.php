<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
$appointment = $appointment ?? [];
$isEdit = !empty($appointment['id']);
$entryType = old('entry_type', $appointment['entry_type'] ?? 'appointment');
?>
<form method="post" action="<?= $isEdit ? app_url('appointments/' . $appointment['id']) : app_url('appointments') ?>" class="ajax-form" data-redirect="<?= e(app_url('appointments')) ?>">
<?= csrf_field() ?>
<div class="card content-card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Entry Type</label>
                <select class="form-select" name="entry_type" id="entryType">
                    <option value="appointment" <?= $entryType === 'appointment' ? 'selected' : '' ?>>Patient Appointment</option>
                    <option value="doctor_remark" <?= $entryType === 'doctor_remark' ? 'selected' : '' ?>>Doctor Remark</option>
                </select>
            </div>
            <div class="col-md-4 appointment-only">
                <label class="form-label">Patient</label>
                <select class="form-select" name="patient_id" id="patientField">
                    <option value="">Select</option>
                    <?php $selected = old('patient_id', $appointment['patient_id'] ?? ''); foreach (($patients ?? []) as $option): ?>
                        <option value="<?= e($option['id']) ?>" <?= (string) $selected === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit && !empty($appointment['patient_id']) && can('patients.view')): ?>
                    <div class="mt-1">
                        <a class="small" href="<?= app_url('patients/' . $appointment['patient_id'] . '?tab=clinical') ?>">
                            <i class="bi bi-clipboard2-pulse me-1"></i>Open Clinical Chart
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Doctor</label>
                <?php
                $lockedDoctorId = $lockedDoctorId ?? current_doctor_id();
                $selectedDoctor = old('doctor_id', $appointment['doctor_id'] ?? $lockedDoctorId ?? '');
                ?>
                <select class="form-select" name="doctor_id" required <?= $lockedDoctorId ? 'disabled' : '' ?>>
                    <?php if (!$lockedDoctorId): ?><option value="">Select</option><?php endif; ?>
                    <?php foreach (($doctors ?? []) as $option): ?>
                        <option value="<?= e($option['id']) ?>" <?= (string) $selectedDoctor === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($lockedDoctorId): ?>
                    <input type="hidden" name="doctor_id" value="<?= e((string) $lockedDoctorId) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-4 appointment-only">
                <label class="form-label">Treatment</label>
                <select class="form-select" name="treatment_master_id">
                    <option value="">None</option>
                    <?php $selected = old('treatment_master_id', $appointment['treatment_master_id'] ?? ''); foreach (($treatments ?? []) as $option): ?>
                        <option value="<?= e($option['id']) ?>" <?= (string) $selected === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Appointment Date</label>
                <input class="form-control" type="date" name="appointment_date" value="<?= e(old('appointment_date', $appointment['appointment_date'] ?? '')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Start Time</label>
                <input class="form-control" type="time" name="start_time" value="<?= e(old('start_time', isset($appointment['start_time']) ? substr((string)$appointment['start_time'], 0, 5) : '')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">End Time</label>
                <input class="form-control" type="time" name="end_time" value="<?= e(old('end_time', isset($appointment['end_time']) ? substr((string)$appointment['end_time'], 0, 5) : '')) ?>" required>
            </div>
            <div class="col-md-4 appointment-only">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <?php
                    $statuses = appointment_statuses_list();
                    $currentStatus = old('status', $appointment['status'] ?? 'scheduled');
                    foreach ($statuses as $st):
                    ?>
                        <option value="<?= e($st['slug']) ?>" <?= $currentStatus === $st['slug'] ? 'selected' : '' ?>><?= e($st['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label" id="notesLabel">Notes / Remark</label>
                <textarea class="form-control" name="notes" id="notesField" rows="3"><?= e(old('notes', $appointment['notes'] ?? $appointment['visit_reason'] ?? '')) ?></textarea>
                <input type="hidden" name="visit_reason" id="visitReasonField" value="<?= e(old('visit_reason', $appointment['visit_reason'] ?? '')) ?>">
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a class="btn btn-light" href="<?= app_url('appointments') ?>">Cancel</a>
        </div>
    </div>
</div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  function sync() {
    const isRemark = document.getElementById('entryType').value === 'doctor_remark';
    document.querySelectorAll('.appointment-only').forEach(el => el.classList.toggle('d-none', isRemark));
    document.getElementById('patientField').required = !isRemark;
    document.getElementById('notesField').required = isRemark;
    document.getElementById('notesLabel').textContent = isRemark ? 'Doctor Remark *' : 'Notes / Visit Reason';
  }
  document.getElementById('entryType').addEventListener('change', sync);
  document.getElementById('notesField').addEventListener('input', function () {
    document.getElementById('visitReasonField').value = this.value;
  });
  sync();
});
</script>
