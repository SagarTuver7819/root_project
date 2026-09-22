<?php
$rows = $rows ?? [];
$pendingCollections = $pendingCollections ?? [];
$pendingTotal = (float) ($pendingTotal ?? 0);
$patientId = (int) ($id ?? 0);
$canCollect = !empty($canCollect);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="h5 mb-1">Payments</h3>
        <p class="text-muted small mb-0">Treatment Complete thi avela pending amounts + collected receipts.</p>
    </div>
    <?php if ($pendingTotal > 0): ?>
        <div class="alert alert-warning py-2 px-3 mb-0">
            <strong>Pending collection:</strong> ₹<?= e(number_format($pendingTotal, 2)) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-success py-2 px-3 mb-0 mb-0">
            <strong>Pending:</strong> ₹0.00
        </div>
    <?php endif; ?>
</div>

<div class="card border mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-hourglass-split me-1 text-warning"></i>Pending from Treatment Completed</strong>
        <span class="badge text-bg-warning"><?= count($pendingCollections) ?> item(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive patient-tab-table-wrap">
            <table class="table table-hover align-middle patient-tab-table text-center mb-0 w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Treatment</th>
                        <th>Teeth</th>
                        <th>Doctor</th>
                        <th>Amount (₹)</th>
                        <th>Paid (₹)</th>
                        <th>Due (₹)</th>
                        <th>Consent</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingCollections === []): ?>
                        <tr>
                            <td colspan="9" class="text-muted py-4">No pending collection. Treatment Complete ma amount lakho to ahiya aavse.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($pendingCollections as $i => $row):
                        $due = (float) ($row['due_amount'] ?? 0);
                    ?>
                        <tr>
                            <td><?= (int) ($i + 1) ?></td>
                            <td><?= e($row['description'] ?? '') ?></td>
                            <td><?= e($row['teeth'] ?? '—') ?></td>
                            <td><?= e(doctor_label($row['doctor_name'] ?? null)) ?></td>
                            <td><?= e(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                            <td><?= e(number_format((float) ($row['paid_amount'] ?? 0), 2)) ?></td>
                            <td class="fw-semibold text-danger"><?= e(number_format($due, 2)) ?></td>
                            <td><?= e($row['consent_book_number'] ?? '—') ?></td>
                            <td>
                                <?php if ($canCollect && $due > 0): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success btn-collect-treatment"
                                        data-id="<?= e((string) ($row['id'] ?? '')) ?>"
                                        data-desc="<?= e((string) ($row['description'] ?? '')) ?>"
                                        data-due="<?= e(number_format($due, 2, '.', '')) ?>"
                                    >
                                        <i class="bi bi-cash-coin me-1"></i>Collection
                                    </button>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border">
    <div class="card-header bg-light">
        <strong><i class="bi bi-receipt me-1"></i>Collected Payment History</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive patient-tab-table-wrap">
            <table class="table table-hover align-middle patient-tab-table text-center mb-0 w-100">
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
                        <tr><td colspan="6" class="text-muted py-4">No payments collected yet.</td></tr>
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
    </div>
</div>

<?php if ($canCollect): ?>
<div class="modal fade" id="collectPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="collectPaymentForm">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Collect Payment — <span id="collectDescLabel"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" id="collectItemId" value="">
                <div class="mb-3">
                    <label class="form-label">Due Amount (₹)</label>
                    <input class="form-control" type="text" id="collectDueDisplay" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Collect Amount (₹) <span class="required-star">*</span></label>
                    <input class="form-control" type="number" step="0.01" min="0.01" id="collectAmount" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Mode</label>
                    <select class="form-select no-select2" id="collectMode" data-no-select2="1">
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Card">Card</option>
                        <option value="GPay">GPay</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input class="form-control" type="date" id="collectDate" value="<?= e(date('Y-m-d')) ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" id="collectRemarks" rows="2" placeholder="Optional"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success" id="collectSubmitBtn">
                    <i class="bi bi-check2 me-1"></i>Collect
                </button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
  const modalEl = document.getElementById('collectPaymentModal');
  const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  const collectBase = <?= json_encode(rtrim(app_url('patients/' . $patientId . '/suggested-plan'), '/') . '/') ?>;
  const paymentsUrl = <?= json_encode(app_url('patients/' . $patientId . '?tab=payments')) ?>;

  document.querySelectorAll('.btn-collect-treatment').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('collectItemId').value = this.dataset.id || '';
      document.getElementById('collectDescLabel').textContent = this.dataset.desc || '';
      document.getElementById('collectDueDisplay').value = this.dataset.due || '0';
      document.getElementById('collectAmount').value = this.dataset.due || '';
      document.getElementById('collectRemarks').value = '';
      document.getElementById('collectDate').value = '<?= e(date('Y-m-d')) ?>';
      document.getElementById('collectMode').value = 'Cash';
      modal && modal.show();
    });
  });

  document.getElementById('collectPaymentForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const itemId = document.getElementById('collectItemId').value;
    if (!itemId) {
      toastr.error('Treatment not found.');
      return;
    }
    const btn = document.getElementById('collectSubmitBtn');
    if (btn) {
      btn.disabled = true;
      btn.dataset.original = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    }
    const body = new FormData();
    body.append('_token', window.CSRF_TOKEN || document.querySelector('#collectPaymentForm [name="_token"]')?.value || '');
    body.append('amount', document.getElementById('collectAmount').value || '0');
    body.append('payment_mode', document.getElementById('collectMode').value || 'Cash');
    body.append('payment_date', document.getElementById('collectDate').value || '');
    body.append('remarks', document.getElementById('collectRemarks').value || '');

    fetch(collectBase + encodeURIComponent(itemId) + '/collect', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
      },
      body: body
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (res && res.success === false) {
        toastr.error(res.message || 'Collection failed.');
        return;
      }
      toastr.success((res && res.message) || 'Payment collected.');
      modal && modal.hide();
      const redirectTo = (res.data && res.data.redirect) || paymentsUrl;
      setTimeout(function () { window.location.href = redirectTo; }, 250);
    }).catch(function () {
      toastr.error('Collection failed.');
    }).finally(function () {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.original || 'Collect';
      }
    });
  });
})();
</script>
<?php endif; ?>
