<?php
$actions = '<a href="' . app_url('visits/' . ($visit['id'] ?? '')) . '" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Back to Report</a>';
require __DIR__ . '/../../components/page-header.php';
?>

<div class="card content-card">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="card-title mb-0">Edit Visit Clinical Notes — <?= e($visit['visit_code'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?= app_url('visits/' . ($visit['id'] ?? '')) ?>" class="ajax-form" data-redirect="<?= app_url('visits/' . ($visit['id'] ?? '')) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Chief Complaint</label>
                    <textarea class="form-control" name="chief_complaint" rows="3"><?= e($visit['chief_complaint'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Symptoms</label>
                    <textarea class="form-control" name="symptoms" rows="3"><?= e($visit['symptoms'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label font-weight-bold">Clinical Examination Findings</label>
                    <textarea class="form-control" name="clinical_examination" rows="3"><?= e($visit['clinical_examination'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Diagnosis</label>
                    <textarea class="form-control" name="diagnosis" rows="3"><?= e($visit['diagnosis'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Recommended Treatment</label>
                    <textarea class="form-control" name="recommended_treatment" rows="3"><?= e($visit['recommended_treatment'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label font-weight-bold">Doctor Advice / Notes</label>
                    <textarea class="form-control" name="doctor_notes" rows="3"><?= e($visit['doctor_notes'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="follow_up_required" value="1" id="followUpRequired" <?= !empty($visit['follow_up_required']) ? 'checked' : '' ?>>
                        <label class="form-check-label font-weight-bold" for="followUpRequired">Follow-up required</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Follow-up Date</label>
                    <input type="date" class="form-control" name="follow_up_date" value="<?= e($visit['follow_up_date'] ?? '') ?>">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= app_url('visits/' . ($visit['id'] ?? '')) ?>" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Visit Details</button>
            </div>
        </form>
    </div>
</div>
