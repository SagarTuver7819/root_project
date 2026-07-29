<?php
$invoiceUrl = app_url('billing/' . ($bill['id'] ?? '') . '/invoice');
$printUrl = app_url('billing/' . ($bill['id'] ?? '') . '/invoice?print=1');
$actions = '<a href="' . app_url('billing') . '" class="btn btn-light me-2"><i class="bi bi-arrow-left me-1"></i>Back to Bills</a>'
    . '<a href="' . e($invoiceUrl) . '" class="btn btn-outline-primary me-2" target="_blank"><i class="bi bi-eye me-1"></i>View Invoice</a>'
    . '<a href="' . e($printUrl) . '" class="btn btn-success me-2" target="_blank"><i class="bi bi-printer me-1"></i>Print / PDF</a>'
    . '<a href="' . app_url('billing/' . ($bill['id'] ?? '') . '/edit') . '" class="btn btn-primary">Edit Bill</a>';
require __DIR__ . '/../../components/page-header.php';
$pendingAmount = (float) ($bill['pending_amount'] ?? 0);
$isPaid = $pendingAmount <= 0;
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card content-card">
            <div class="card-body">
                <h3><?= e($bill['bill_number'] ?? '') ?></h3>
                <p><?= e($bill['patient_name'] ?? '') ?> | <?= status_badge($bill['status'] ?? 'pending') ?></p>
                <div class="row">
                    <div class="col">Treatment<br><strong><?= e(format_money(max(0, (float) ($bill['gross_amount'] ?? 0) - (float) ($bill['booking_amount'] ?? 0)))) ?></strong></div>
                    <div class="col">Booking<br><strong><?= e(format_money($bill['booking_amount'] ?? 0)) ?></strong></div>
                    <div class="col">Net<br><strong><?= e(format_money($bill['net_amount'] ?? 0)) ?></strong></div>
                    <div class="col">Paid<br><strong><?= e(format_money($bill['paid_amount'] ?? 0)) ?></strong></div>
                    <div class="col">Pending<br><strong><?= e(format_money($bill['pending_amount'] ?? 0)) ?></strong></div>
                </div>
                <hr>
                <h6 class="mb-3">Payment History</h6>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Mode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="5" class="text-muted">No payments yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach (($payments ?? []) as $p): ?>
                            <tr>
                                <td><?= e($p['receipt_number'] ?? '') ?></td>
                                <td><?= e(format_date($p['payment_date'] ?? null)) ?></td>
                                <td><?= e(format_money($p['amount'] ?? 0)) ?></td>
                                <td><?= e($p['payment_mode'] ?? '') ?></td>
                                <td><?= status_badge($p['status'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <?php if ($isPaid): ?>
            <div class="card content-card mb-3">
                <div class="card-body text-center py-4">
                    <i class="bi bi-check-circle-fill text-success fs-1"></i>
                    <h6 class="mt-2 mb-1">Fully Paid</h6>
                    <p class="text-muted small mb-3">Invoice ready for patient. View or print / save as PDF.</p>
                    <a href="<?= e($invoiceUrl) ?>" class="btn btn-outline-primary w-100 mb-2" target="_blank">
                        <i class="bi bi-eye me-1"></i>View Invoice
                    </a>
                    <a href="<?= e($printUrl) ?>" class="btn btn-success w-100 mb-2" target="_blank">
                        <i class="bi bi-printer me-1"></i>Print / Save PDF
                    </a>
                    <a href="<?= app_url('billing') ?>" class="btn btn-light w-100">Back to Billing Grid</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card content-card mb-3">
                <div class="card-body">
                    <h6 class="mb-2">Patient Invoice</h6>
                    <p class="text-muted small mb-3">Share draft invoice with patient anytime.</p>
                    <a href="<?= e($invoiceUrl) ?>" class="btn btn-outline-primary w-100 mb-2" target="_blank">
                        <i class="bi bi-eye me-1"></i>View Invoice
                    </a>
                    <a href="<?= e($printUrl) ?>" class="btn btn-success w-100" target="_blank">
                        <i class="bi bi-printer me-1"></i>Print / Save PDF
                    </a>
                </div>
            </div>
            <form method="post" action="<?= app_url('payments') ?>" class="card content-card ajax-form" data-redirect="<?= e(app_url('billing')) ?>">
                <div class="card-body">
                    <h6 class="mb-3">Add Payment</h6>
                    <?= csrf_field() ?>
                    <input type="hidden" name="bill_id" value="<?= e($bill['id'] ?? '') ?>">
                    <label class="form-label">Payment Date</label>
                    <input class="form-control mb-3" type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" required>
                    <label class="form-label">Amount</label>
                    <input class="form-control mb-3" type="number" step="0.01" min="0.01" name="amount" value="<?= e((string) $pendingAmount) ?>" required>
                    <label class="form-label">Payment Mode</label>
                    <select class="form-select mb-3" name="payment_mode">
                        <option>Cash</option>
                        <option>Card</option>
                        <option>UPI</option>
                        <option>Bank Transfer</option>
                    </select>
                    <label class="form-label">Reference (optional)</label>
                    <input class="form-control mb-3" type="text" name="transaction_reference" placeholder="UPI / Card ref">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control mb-3" name="remarks" rows="2"></textarea>
                    <button class="btn btn-primary w-100" type="submit">Add Payment</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
