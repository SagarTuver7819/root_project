<?php
$rows = $rows ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Code</th>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Diagnosis</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No visits found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['visit_code'] ?? '') ?></td>
                    <td><?= e(format_date($row['visit_date'] ?? null)) ?></td>
                    <td><?= e(format_time($row['visit_time'] ?? null)) ?></td>
                    <td><?= e(doctor_label($row['doctor_name'] ?? null)) ?></td>
                    <td><?= e($row['diagnosis'] ?? '—') ?></td>
                    <td><?= status_badge($row['status'] ?? '') ?></td>
                    <td><a class="btn btn-sm btn-light" href="<?= app_url('visits/' . ($row['id'] ?? '')) ?>"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
