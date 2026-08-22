<?php
$actions = '<a class="btn btn-light" href="' . app_url('quotations') . '"><i class="bi bi-arrow-left me-1"></i>Back to Quotations</a>';
if (!empty($quotation['id']) && can('quotations.print')) {
    $actions .= '<a class="btn btn-outline-primary" href="' . app_url('quotations/' . $quotation['id'] . '/print') . '" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>';
}
require __DIR__ . '/../../components/page-header.php';

$quotation = $quotation ?? [];
$isEdit = !empty($quotation['id']);
$items = $items ?? [];
$selectedPatient = old('patient_id', $quotation['patient_id'] ?? $_GET['patient_id'] ?? '');
$selectedDoctor = old('doctor_id', $quotation['doctor_id'] ?? '');
$quotationDate = old('quotation_date', $quotation['quotation_date'] ?? date('Y-m-d'));
$discount = old('discount', $quotation['discount'] ?? 0);
$status = old('status', $quotation['status'] ?? 'draft');
?>

<div class="card content-card">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="card-title mb-0"><?= e($isEdit ? 'Edit Quotation' : 'Create Quotation') ?></h5>
        <p class="text-muted small mb-0 mt-1">Suggested treatment plan ni lines ahiya auto-fill thay. Price adjust kari ne save karo.</p>
    </div>
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? app_url('quotations/' . $quotation['id']) : app_url('quotations') ?>" class="ajax-form" data-redirect="<?= e($isEdit ? app_url('quotations/' . $quotation['id'] . '/edit') : app_url('quotations')) ?>">
            <?= csrf_field() ?>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    <select class="form-select" name="patient_id" id="quotationPatient" required>
                        <option value="">Select Patient</option>
                        <?php foreach (($patients ?? []) as $p): ?>
                            <option value="<?= e($p['id']) ?>" <?= (string) $selectedPatient === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Consulting Doctor</label>
                    <select class="form-select" name="doctor_id">
                        <option value="">Select Doctor</option>
                        <?php foreach (($doctors ?? []) as $d): ?>
                            <option value="<?= e($d['id']) ?>" <?= (string) $selectedDoctor === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input class="form-control" type="date" name="quotation_date" value="<?= e($quotationDate) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="final" <?= $status === 'final' ? 'selected' : '' ?>>Final</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="quotationItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Treatment / Procedure</th>
                            <th style="width:140px">Teeth</th>
                            <th style="width:180px">Doctor</th>
                            <th style="width:130px">Amount (₹)</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody id="quotationItemsBody">
                        <?php foreach ($items as $index => $item): ?>
                            <tr class="quotation-item-row">
                                <td class="text-muted row-num"><?= (int) ($index + 1) ?></td>
                                <td>
                                    <input type="hidden" name="items[<?= (int) $index ?>][suggested_treatment_id]" value="<?= e((string) ($item['suggested_treatment_id'] ?? '')) ?>">
                                    <input class="form-control item-desc" type="text" name="items[<?= (int) $index ?>][description]" value="<?= e($item['description'] ?? '') ?>" placeholder="Treatment name" list="treatmentMasterList" required>
                                </td>
                                <td>
                                    <input class="form-control" type="text" name="items[<?= (int) $index ?>][teeth]" value="<?= e($item['teeth'] ?? '') ?>" placeholder="UR6, UL1">
                                </td>
                                <td>
                                    <select class="form-select no-select2" name="items[<?= (int) $index ?>][doctor_id]">
                                        <option value="">—</option>
                                        <?php foreach (($doctors ?? []) as $d): ?>
                                            <option value="<?= e($d['id']) ?>" <?= (string) ($item['doctor_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input class="form-control text-end item-price" type="number" step="0.01" min="0" name="items[<?= (int) $index ?>][unit_price]" value="<?= e((string) ($item['unit_price'] ?? $item['amount'] ?? 0)) ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-quotation-item" title="Remove">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <datalist id="treatmentMasterList">
                <?php foreach (($treatments ?? []) as $tm): ?>
                    <option value="<?= e($tm['name']) ?>" data-price="<?= e((string) ($tm['default_price'] ?? 0)) ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="addQuotationItem"><i class="bi bi-plus-lg me-1"></i>Add line</button>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Payment terms, validity, etc."><?= e(old('notes', $quotation['notes'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <div class="d-flex justify-content-between mb-2"><span>Gross</span><strong id="quotationGross">₹0.00</strong></div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Discount (₹)</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="discount" id="quotationDiscount" value="<?= e((string) $discount) ?>">
                        </div>
                        <div class="d-flex justify-content-between fs-5 text-primary"><span>Net</span><strong id="quotationNet">₹0.00</strong></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2 justify-content-end">
                <a class="btn btn-light" href="<?= app_url('quotations') ?>">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i><?= e($isEdit ? 'Update Quotation' : 'Save Quotation') ?></button>
            </div>
        </form>
    </div>
</div>

<template id="quotationItemTpl">
    <tr class="quotation-item-row">
        <td class="text-muted row-num">1</td>
        <td>
            <input type="hidden" name="items[__INDEX__][suggested_treatment_id]" value="">
            <input class="form-control item-desc" type="text" name="items[__INDEX__][description]" placeholder="Treatment name" list="treatmentMasterList" required>
        </td>
        <td><input class="form-control" type="text" name="items[__INDEX__][teeth]" placeholder="UR6, UL1"></td>
        <td>
            <select class="form-select no-select2" name="items[__INDEX__][doctor_id]">
                <option value="">—</option>
                <?php foreach (($doctors ?? []) as $d): ?>
                    <option value="<?= e($d['id']) ?>"><?= e(doctor_label($d['name'])) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input class="form-control text-end item-price" type="number" step="0.01" min="0" name="items[__INDEX__][unit_price]" value="0"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-quotation-item" title="Remove">&times;</button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const body = document.getElementById('quotationItemsBody');
  const tpl = document.getElementById('quotationItemTpl');
  const discountInput = document.getElementById('quotationDiscount');
  const grossEl = document.getElementById('quotationGross');
  const netEl = document.getElementById('quotationNet');
  const priceMap = {};
  document.querySelectorAll('#treatmentMasterList option').forEach(function (opt) {
    priceMap[(opt.value || '').toLowerCase()] = parseFloat(opt.dataset.price || '0') || 0;
  });

  function reindex() {
    Array.from(body.querySelectorAll('.quotation-item-row')).forEach(function (row, i) {
      row.querySelector('.row-num').textContent = String(i + 1);
      row.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
      });
    });
  }

  function totals() {
    let gross = 0;
    body.querySelectorAll('.item-price').forEach(function (input) {
      gross += parseFloat(input.value) || 0;
    });
    const disc = parseFloat(discountInput.value) || 0;
    const net = Math.max(0, gross - disc);
    grossEl.textContent = '₹' + gross.toFixed(2);
    netEl.textContent = '₹' + net.toFixed(2);
  }

  function applyPriceFromDesc(input) {
    const key = (input.value || '').trim().toLowerCase();
    if (!key || !priceMap[key]) return;
    const row = input.closest('.quotation-item-row');
    const priceInput = row && row.querySelector('.item-price');
    if (priceInput && (parseFloat(priceInput.value) || 0) === 0) {
      priceInput.value = priceMap[key].toFixed(2);
      totals();
    }
  }

  document.getElementById('addQuotationItem').addEventListener('click', function () {
    const html = tpl.innerHTML.replace(/__INDEX__/g, String(body.children.length));
    body.insertAdjacentHTML('beforeend', html);
    reindex();
  });

  body.addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-quotation-item');
    if (!btn) return;
    const rows = body.querySelectorAll('.quotation-item-row');
    if (rows.length <= 1) {
      rows[0].querySelector('.item-desc').value = '';
      rows[0].querySelector('.item-price').value = '0';
      totals();
      return;
    }
    btn.closest('.quotation-item-row').remove();
    reindex();
    totals();
  });

  body.addEventListener('input', function (e) {
    if (e.target.classList.contains('item-price')) totals();
    if (e.target.classList.contains('item-desc')) applyPriceFromDesc(e.target);
  });

  body.addEventListener('change', function (e) {
    if (e.target.classList.contains('item-desc')) applyPriceFromDesc(e.target);
  });

  discountInput.addEventListener('input', totals);
  reindex();
  totals();
});
</script>
