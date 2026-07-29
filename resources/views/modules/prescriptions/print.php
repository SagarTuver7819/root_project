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
    <h2>Prescription <?= e($prescription['prescription_number'] ?? '') ?></h2>
    <p>
        <strong>Patient:</strong> <?= e($prescription['patient_name'] ?? '') ?>
        | <strong>Doctor:</strong> <?= e(doctor_label($prescription['doctor_name'] ?? '')) ?>
    </p>
    <p><strong>Diagnosis:</strong> <?= e($prescription['diagnosis'] ?? '') ?></p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Instructions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($items ?? []) as $item): ?>
                <tr>
                    <td><?= e($item['medicine_name'] ?? '') ?></td>
                    <td><?= e($item['dosage'] ?? '') ?></td>
                    <td><?= e($item['frequency'] ?? '') ?></td>
                    <td><?= e($item['duration'] ?? '') ?></td>
                    <td><?= e($item['instructions'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
