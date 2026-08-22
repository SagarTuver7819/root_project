<?php
$rows = $rows ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Treatment</th>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Appt Code</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No appointments found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['treatment_name'] ?? '—') ?></td>
                    <td><?= e(format_date($row['appointment_date'] ?? null)) ?></td>
                    <td><?= e(format_time($row['start_time'] ?? null)) ?> – <?= e(format_time($row['end_time'] ?? null)) ?></td>
                    <td><?= e(doctor_label($row['doctor_name'] ?? null)) ?></td>
                    <td><?= e($row['appointment_code'] ?? '') ?></td>
                    <td><?= ($row['entry_type'] ?? '') === 'doctor_remark'
                        ? '<span class="badge" style="background:#DC2626">Remark</span>'
                        : status_badge($row['status'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
