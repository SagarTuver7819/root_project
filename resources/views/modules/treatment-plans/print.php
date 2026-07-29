<?php /** @var array $plan */ ?>
<div class="print-sheet">
    <div class="mb-4" style="border-bottom:2px solid #00AEEF;padding-bottom:12px;">
        <h3 class="mb-1 text-primary"><?= e(branding('hospital_name')) ?></h3>
        <div class="text-muted small"><?= e(branding('hospital_tagline')) ?></div>
        <div class="text-muted small mt-1">
            <?= e(branding('hospital_address')) ?><br>
            <?php if (branding('hospital_phone')): ?>Phone: <?= e(branding('hospital_phone')) ?><?php endif; ?>
            <?php if (branding('hospital_phone') && branding('hospital_email')): ?> · <?php endif; ?>
            <?php if (branding('hospital_email')): ?>Email: <?= e(branding('hospital_email')) ?><?php endif; ?>
        </div>
    </div>
    <h2 class="mb-1">Treatment Plan <?= e($plan['plan_code'] ?? '') ?></h2>
    <div class="row mb-3">
        <div class="col-6"><strong>Patient:</strong> <?= e($plan['patient_name'] ?? '') ?></div>
        <div class="col-6"><strong>Doctor:</strong> <?= e(doctor_label($plan['doctor_name'] ?? '')) ?></div>
        <div class="col-6"><strong>Treatment:</strong> <?= e($plan['treatment_name'] ?? '') ?></div>
        <div class="col-6"><strong>Tooth:</strong> <?= e($plan['tooth_number'] ?? '-') ?></div>
        <div class="col-6"><strong>Final Amount:</strong> <?= e(format_money($plan['final_amount'] ?? 0)) ?></div>
        <div class="col-6"><strong>Status:</strong> <?= e(ucwords(str_replace('_', ' ', $plan['status'] ?? ''))) ?></div>
    </div>
    <?php if (!empty($plan['diagnosis'])): ?><p><strong>Diagnosis:</strong> <?= nl2br(e($plan['diagnosis'])) ?></p><?php endif; ?>
    <?php if (!empty($plan['description'])): ?><p><strong>Description:</strong> <?= nl2br(e($plan['description'])) ?></p><?php endif; ?>
    <h5 class="mt-4">Sessions</h5>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Date</th><th>Procedure</th><th>Notes</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach (($sessions ?? []) as $session): ?>
            <tr>
                <td><?= e((string)$session['session_number']) ?></td>
                <td><?= e(format_date($session['session_date'] ?? null)) ?></td>
                <td><?= e($session['procedure_performed'] ?? '-') ?></td>
                <td><?= e($session['clinical_notes'] ?? '-') ?></td>
                <td><?= e($session['status'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?>
            <tr><td colspan="5" class="text-center">No sessions</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<script>window.print();</script>
