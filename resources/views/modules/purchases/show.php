<?php
$actions = '';
if (($purchase['status'] ?? '') !== 'confirmed' && can('purchases.edit')) {
    $actions .= '<form method="post" action="' . app_url('purchases/' . ($purchase['id'] ?? '') . '/confirm') . '" class="d-inline ajax-form" data-reload="1">' . csrf_field() . '<button class="btn btn-success"><i class="bi bi-box-seam me-1"></i>Confirm & Stock In</button></form>';
}
$actions .= '<a href="' . app_url('purchases') . '" class="btn btn-light">Back</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card content-card">
            <div class="card-body">
                <h5 class="mb-3"><?= e($purchase['purchase_number'] ?? '') ?></h5>
                <div class="mb-2"><span class="text-muted">Supplier</span><div class="fw-semibold"><?= e($purchase['supplier_name'] ?? '') ?></div></div>
                <div class="mb-2"><span class="text-muted">Purchase Date</span><div><?= e(format_date($purchase['purchase_date'] ?? null)) ?></div></div>
                <div class="mb-2"><span class="text-muted">Invoice</span><div><?= e($purchase['invoice_number'] ?? '-') ?></div></div>
                <div class="mb-2"><span class="text-muted">Total</span><div class="fw-semibold"><?= e(format_money($purchase['total'] ?? 0)) ?></div></div>
                <div class="mb-0"><span class="text-muted">Status</span><div><?= status_badge($purchase['status'] ?? 'draft') ?></div></div>
                <?php if (!empty($purchase['notes'])): ?>
                    <hr><p class="mb-0"><?= nl2br(e($purchase['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card content-card">
            <div class="card-header bg-white"><strong>Purchase Items</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($items ?? []) as $item): ?>
                            <tr>
                                <td><?= e($item['item_name'] ?? '') ?></td>
                                <td><?= e((string)$item['quantity']) ?></td>
                                <td><?= e(format_money($item['rate'] ?? 0)) ?></td>
                                <td><?= e(format_money($item['discount'] ?? 0)) ?></td>
                                <td><?= e(format_money($item['tax'] ?? 0)) ?></td>
                                <td><?= e(format_money($item['total'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No items.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
