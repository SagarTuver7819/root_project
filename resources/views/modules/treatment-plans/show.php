<?php
$actions = '<a href="' . app_url('treatment-plans/' . ($plan['id'] ?? '') . '/edit') . '" class="btn btn-primary">Edit</a>'
    . '<a href="' . app_url('treatment-plans/' . ($plan['id'] ?? '') . '/print') . '" class="btn btn-light" target="_blank"><i class="bi bi-printer"></i></a>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card content-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="mb-0"><?= e($plan['plan_code'] ?? '') ?></h5>
                    <?= status_badge($plan['status'] ?? 'recommended') ?>
                </div>
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Patient</dt><dd class="col-7"><?= e($plan['patient_name'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">Doctor</dt><dd class="col-7"><?= e($plan['doctor_name'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">Treatment</dt><dd class="col-7"><?= e($plan['treatment_name'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">Tooth</dt><dd class="col-7"><?= e($plan['tooth_number'] ?? '-') ?></dd>
                    <dt class="col-5 text-muted">Sessions</dt><dd class="col-7"><?= e((string)($plan['sessions'] ?? 1)) ?></dd>
                    <dt class="col-5 text-muted">Cost</dt><dd class="col-7"><?= e(format_money($plan['cost'] ?? 0)) ?></dd>
                    <dt class="col-5 text-muted">Discount</dt><dd class="col-7"><?= e(format_money($plan['discount'] ?? 0)) ?></dd>
                    <dt class="col-5 text-muted">Final</dt><dd class="col-7 fw-semibold"><?= e(format_money($plan['final_amount'] ?? 0)) ?></dd>
                </dl>
                <?php if (!empty($plan['diagnosis'])): ?>
                    <hr><p class="mb-1 text-muted">Diagnosis</p><p><?= nl2br(e($plan['diagnosis'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($plan['description'])): ?>
                    <p class="mb-1 text-muted">Description</p><p class="mb-0"><?= nl2br(e($plan['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card content-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Treatment Sessions</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Procedure</th>
                                <th>Tooth</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($sessions ?? []) as $session): ?>
                            <tr>
                                <td><?= e((string)$session['session_number']) ?></td>
                                <td><?= e(format_date($session['session_date'] ?? null)) ?></td>
                                <td>
                                    <?= e($session['procedure_performed'] ?? '-') ?>
                                    <?php if (!empty($session['clinical_notes'])): ?>
                                        <div class="small text-muted"><?= e($session['clinical_notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($session['tooth_number'] ?? '-') ?></td>
                                <td><?= status_badge($session['status'] ?? 'completed') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($sessions)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No sessions recorded yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (can('treatment_sessions.add')): ?>
        <form method="post" action="<?= app_url('treatment-plans/' . ($plan['id'] ?? '') . '/sessions') ?>" class="card content-card ajax-form" data-reload="1">
            <div class="card-header bg-white"><strong>Add Session</strong></div>
            <div class="card-body">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Session Date</label>
                        <input type="date" name="session_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <?php foreach (($doctors ?? []) as $doctor): ?>
                                <option value="<?= e((string)$doctor['id']) ?>" <?= ((int)($plan['doctor_id'] ?? 0) === (int)$doctor['id']) ? 'selected' : '' ?>><?= e($doctor['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tooth Number</label>
                        <input type="text" name="tooth_number" class="form-control" value="<?= e($plan['tooth_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Procedure Performed</label>
                        <input type="text" name="procedure_performed" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Material Used</label>
                        <input type="text" name="material_used" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Clinical Notes</label>
                        <textarea name="clinical_notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Next Session Date</label>
                        <input type="date" name="next_session_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2 justify-content-end">
                <a href="<?= app_url('treatment-plans/' . ($plan['id'] ?? '')) ?>" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Session</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
