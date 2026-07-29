<?php
$rows = $rows ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Plan</th>
                <th>Treatment</th>
                <th>Doctor</th>
                <th>Tooth</th>
                <th>Amount</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No treatment plans found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['plan_code'] ?? '') ?></td>
                    <td><?= e($row['treatment_name'] ?? '') ?></td>
                    <td><?= e(doctor_label($row['doctor_name'] ?? null)) ?></td>
                    <td><?= e($row['tooth_number'] ?? '—') ?></td>
                    <td><?= e(format_money($row['final_amount'] ?? 0)) ?></td>
                    <td><?= status_badge($row['status'] ?? '') ?></td>
                    <td><a class="btn btn-sm btn-light" href="<?= app_url('treatment-plans/' . ($row['id'] ?? '')) ?>"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
