<?php
$rows = $rows ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Rx No</th>
                <th>Date</th>
                <th>Doctor</th>
                <th>Diagnosis</th>
                <th>Follow-up</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No prescriptions found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['prescription_number'] ?? '') ?></td>
                    <td><?= e(format_date($row['prescription_date'] ?? null)) ?></td>
                    <td><?= e(doctor_label($row['doctor_name'] ?? null)) ?></td>
                    <td><?= e($row['diagnosis'] ?? '—') ?></td>
                    <td><?= e(format_date($row['follow_up_date'] ?? null)) ?></td>
                    <td>
                        <a class="btn btn-sm btn-light" href="<?= app_url('prescriptions/' . ($row['id'] ?? '') . '/print') ?>" target="_blank"><i class="bi bi-printer"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
