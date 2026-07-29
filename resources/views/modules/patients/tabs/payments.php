<?php
$rows = $rows ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Bill</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Mode</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No payments found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['receipt_number'] ?? '') ?></td>
                    <td><?= e($row['bill_number'] ?? '') ?></td>
                    <td><?= e(format_date($row['payment_date'] ?? null)) ?></td>
                    <td><?= e(format_money($row['amount'] ?? 0)) ?></td>
                    <td><?= e($row['payment_mode'] ?? '') ?></td>
                    <td><?= status_badge($row['status'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
