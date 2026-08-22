<?php
$patientId = (int) ($id ?? 0);
$quotation = $quotation ?? null;
$items = $items ?? [];
$suggestedItems = $suggestedItems ?? [];
$hasQuotation = !empty($quotation['id']);
$hasSuggested = $suggestedItems !== [];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="h5 mb-1">Treatment Estimate</h3>
        <p class="text-muted small mb-0">Suggested treatment plan par thi estimate auto tayar thay. Price adjust kari ne print kari shakay.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($hasSuggested && can('quotations.add')): ?>
            <a class="btn btn-outline-primary btn-sm" href="<?= app_url('patients/' . $patientId . '/quotation') ?>">
                <i class="bi bi-arrow-repeat me-1"></i>Refresh from Plan
            </a>
        <?php endif; ?>
        <?php if ($hasQuotation && can('quotations.edit')): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= app_url('quotations/' . $quotation['id'] . '/edit') ?>">
                <i class="bi bi-pencil me-1"></i>Edit Estimate
            </a>
        <?php endif; ?>
        <?php if ($hasQuotation && can('quotations.print')): ?>
            <a class="btn btn-primary btn-sm" href="<?= app_url('quotations/' . $quotation['id'] . '/print') ?>" target="_blank">
                <i class="bi bi-printer me-1"></i>Print
            </a>
        <?php endif; ?>
        <?php if (can('quotations.view')): ?>
            <a class="btn btn-light btn-sm" href="<?= app_url('quotations') ?>">
                <i class="bi bi-list-ul me-1"></i>All Quotations
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$hasQuotation && !$hasSuggested): ?>
    <div class="alert alert-light border mb-0">
        Pehla <strong>Treatment Plan</strong> tab ma treatment lakhine save karo. Pachhi ahiya estimate auto aavse.
    </div>
<?php elseif (!$hasQuotation && $hasSuggested): ?>
    <div class="alert alert-info mb-3">
        Treatment plan save thay che. Estimate tayar karva mate button click karo.
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Treatment</th>
                    <th>Teeth</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suggestedItems as $i => $row): ?>
                    <tr>
                        <td><?= (int) ($i + 1) ?></td>
                        <td><?= e($row['description'] ?? '') ?></td>
                        <td><?= e($row['teeth'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (can('quotations.add')): ?>
        <a class="btn btn-primary" href="<?= app_url('patients/' . $patientId . '/quotation') ?>">
            <i class="bi bi-file-earmark-text me-1"></i>Create Treatment Estimate
        </a>
    <?php endif; ?>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><strong>Quotation No</strong><div><?= e($quotation['quotation_number'] ?? '') ?></div></div>
        <div class="col-md-3"><strong>Date</strong><div><?= e(format_date($quotation['quotation_date'] ?? null)) ?></div></div>
        <div class="col-md-3"><strong>Doctor</strong><div><?= e(doctor_label($quotation['doctor_name'] ?? '—')) ?></div></div>
        <div class="col-md-3"><strong>Status</strong><div><?= status_badge($quotation['status'] ?? 'draft') ?></div></div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Treatment / Procedure</th>
                    <th>Teeth</th>
                    <th>Doctor</th>
                    <th class="text-end">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No estimate lines found.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $i => $row): ?>
                    <tr>
                        <td><?= (int) ($i + 1) ?></td>
                        <td><?= e($row['description'] ?? '') ?></td>
                        <td><?= e($row['teeth'] ?? '—') ?></td>
                        <td><?= e(doctor_label($row['doctor_name'] ?? '—')) ?></td>
                        <td class="text-end"><?= e(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if ($items !== []): ?>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Gross</th>
                        <th class="text-end">₹<?= e(number_format((float) ($quotation['gross_amount'] ?? 0), 2)) ?></th>
                    </tr>
                    <?php if ((float) ($quotation['discount'] ?? 0) > 0): ?>
                        <tr>
                            <th colspan="4" class="text-end">Discount</th>
                            <th class="text-end text-danger">- ₹<?= e(number_format((float) $quotation['discount'], 2)) ?></th>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th colspan="4" class="text-end">Net Estimate</th>
                        <th class="text-end text-primary fs-5">₹<?= e(number_format((float) ($quotation['net_amount'] ?? 0), 2)) ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php if (!empty($quotation['notes'])): ?>
        <div class="alert alert-light border mb-0"><strong>Notes:</strong> <?= nl2br(e($quotation['notes'])) ?></div>
    <?php endif; ?>
<?php endif; ?>
