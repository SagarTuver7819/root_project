<?php
$actions = '<a href="' . app_url('billing') . '" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>Billing</a>'
    . '<a href="' . app_url('outstanding') . '" class="btn btn-light"><i class="bi bi-exclamation-circle me-1"></i>Outstanding</a>';
require __DIR__ . '/../../components/page-header.php';

$statCards = [
    ['Pending Payments', 'pending_payments', 'bi-exclamation-circle', 'amber', app_url('outstanding'), true],
    ['Revenue Today', 'revenue_today', 'bi-cash-coin', 'green', app_url('payments'), true],
    ['Patients', 'patients', 'bi-people', 'cyan', app_url('patients')],
    ['Appointments Today', 'appointments_today', 'bi-calendar-check', 'blue', app_url('billing')],
];
?>
<div class="row g-3 mb-4">
<?php foreach ($statCards as $card):
    [$label, $key, $icon, $tone, $href] = $card;
    $isMoney = !empty($card[5]);
    $value = $isMoney ? format_money($stats[$key] ?? 0) : (string) ($stats[$key] ?? 0);
?>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= e($href) ?>" class="dash-stat dash-stat-<?= e($tone) ?> text-decoration-none">
            <div class="dash-stat-icon"><i class="bi <?= e($icon) ?>"></i></div>
            <div class="dash-stat-body">
                <div class="dash-stat-label"><?= e($label) ?></div>
                <div class="dash-stat-value"><?= e($value) ?></div>
            </div>
        </a>
    </div>
<?php endforeach; ?>
</div>

<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0">Recent Payments</h3>
            <a href="<?= app_url('payments') ?>" class="small">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Mode</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (($recentPayments ?? []) as $pay): ?>
                    <tr>
                        <td><?= e($pay['payment_date']) ?></td>
                        <td><?= e($pay['patient_name']) ?></td>
                        <td><?= e(ucfirst((string) $pay['payment_mode'])) ?></td>
                        <td class="text-end"><?= e(format_money($pay['amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentPayments)): ?>
                    <tr><td colspan="4" class="text-muted text-center py-3">No payments yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
