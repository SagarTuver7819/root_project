<?php
$rows = $rows ?? [];
?>
<div class="patient-completed-tab">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="h5 mb-1 text-success">
            <i class="bi bi-check2-circle me-1"></i>Treatment Completed
        </h3>
        <p class="text-muted small mb-0">Completed treatments — view only (edit nathi). Payment collect thay gayu hoy to <strong>Payment Done</strong> j dekhase — Pending nahi.</p>
    </div>
</div>

<div class="table-responsive patient-tab-table-wrap patient-completed-table-wrap">
    <table class="table table-hover align-middle patient-tab-table patient-completed-table text-center w-100">
        <thead>
            <tr>
                <th>#</th>
                <th>Treatment</th>
                <th>Teeth</th>
                <th>Doctor</th>
                <th>Amount (₹)</th>
                <th>Payment</th>
                <th>Consent Book</th>
                <th>Next Appt</th>
                <th>Completed</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="10" class="text-muted py-4">No completed treatments yet. Plan tab ma Treatment Complete kari ne ahiya aavse.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row):
                $amt = (float) ($row['amount'] ?? 0);
                $paid = (float) ($row['paid_amount'] ?? 0);
                // Prefer amounts — collected payment should never show as Pending
                if ($amt <= 0) {
                    $ps = 'none';
                } elseif ($paid + 0.001 >= $amt) {
                    $ps = 'paid';
                } elseif ($paid > 0) {
                    $ps = 'partial';
                } else {
                    $ps = 'pending';
                }
                $nd = $row['next_appointment_date'] ?? null;
                $nt = $row['next_appointment_time'] ?? null;
                $nextAppt = '—';
                if ($nd) {
                    $nextAppt = format_date($nd);
                    if ($nt) {
                        $nextAppt .= ' ' . format_time($nt);
                    }
                }
                $completedOn = !empty($row['completed_at'])
                    ? format_date(substr((string) $row['completed_at'], 0, 10))
                    : '—';
                $rm = trim((string) ($row['remarks'] ?? ''));
                $ins = trim((string) ($row['patient_instruction'] ?? ''));
                $payLabel = $ps === 'none' ? '—' : ($ps === 'paid' ? 'Payment Done' : ($ps === 'partial' ? 'Partial' : 'Pending'));
            ?>
                <tr
                    class="completed-treatment-row"
                    role="button"
                    tabindex="0"
                    style="cursor:pointer"
                    title="View only — edit nathi"
                    data-treatment="<?= e((string) ($row['description'] ?? '')) ?>"
                    data-teeth="<?= e((string) ($row['teeth'] ?? '')) ?>"
                    data-doctor="<?= e(doctor_label($row['doctor_name'] ?? null)) ?>"
                    data-amount="<?= e(number_format($amt, 2)) ?>"
                    data-paid="<?= e(number_format($paid, 2)) ?>"
                    data-payment="<?= e($payLabel) ?>"
                    data-consent="<?= e((string) ($row['consent_book_number'] ?? '')) ?>"
                    data-next-appt="<?= e($nextAppt) ?>"
                    data-completed="<?= e($completedOn) ?>"
                    data-remarks="<?= e($rm) ?>"
                    data-instruction="<?= e($ins) ?>"
                >
                    <td><?= (int) ($i + 1) ?></td>
                    <td><?= e($row['description'] ?? '') ?></td>
                    <td><?= e($row['teeth'] ?? '—') ?></td>
                    <td><?= e(doctor_label($row['doctor_name'] ?? null)) ?></td>
                    <td><?= e(number_format($amt, 2)) ?></td>
                    <td>
                        <?php if ($ps === 'none'): ?>
                            —
                        <?php elseif ($ps === 'paid'): ?>
                            <span class="badge text-bg-success">Payment Done</span>
                        <?php elseif ($ps === 'partial'): ?>
                            <span class="badge text-bg-info">Partial ₹<?= e(number_format($paid, 2)) ?></span>
                        <?php else: ?>
                            <span class="badge text-bg-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($row['consent_book_number'] ?? '—') ?></td>
                    <td><?= e($nextAppt) ?></td>
                    <td><?= e($completedOn) ?></td>
                    <td class="text-start" style="max-width:220px">
                        <?php
                        $bits = array_filter([$rm, $ins !== '' ? 'Instruction: ' . $ins : '']);
                        echo e($bits ? implode(' · ', $bits) : '—');
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<div class="modal fade" id="completedTreatmentViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-check me-2 text-success"></i>Treatment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="text-muted small">Treatment</div>
                        <div class="fw-semibold fs-5" id="ctvTreatment">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Teeth</div>
                        <div class="fw-semibold" id="ctvTeeth">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Doctor</div>
                        <div class="fw-semibold" id="ctvDoctor">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Completed On</div>
                        <div class="fw-semibold" id="ctvCompleted">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Consent Book Number</div>
                        <div class="fw-semibold" id="ctvConsent">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Amount (₹)</div>
                        <div class="fw-semibold" id="ctvAmount">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Paid (₹)</div>
                        <div class="fw-semibold" id="ctvPaid">—</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Payment Status</div>
                        <div class="fw-semibold" id="ctvPayment">—</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Next Appointment</div>
                        <div class="fw-semibold" id="ctvNextAppt">—</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Remarks</div>
                        <div class="border rounded p-2 bg-light" id="ctvRemarks">—</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Specific Instruction for Patient</div>
                        <div class="border rounded p-2 bg-light" id="ctvInstruction">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
  const modalEl = document.getElementById('completedTreatmentViewModal');
  const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

  function showDetail(row) {
    const val = function (key, fallback) {
      const v = (row.getAttribute('data-' + key) || '').trim();
      return v !== '' ? v : (fallback || '—');
    };
    document.getElementById('ctvTreatment').textContent = val('treatment');
    document.getElementById('ctvTeeth').textContent = val('teeth');
    document.getElementById('ctvDoctor').textContent = val('doctor');
    document.getElementById('ctvCompleted').textContent = val('completed');
    document.getElementById('ctvConsent').textContent = val('consent');
    document.getElementById('ctvAmount').textContent = val('amount', '0.00');
    document.getElementById('ctvPaid').textContent = val('paid', '0.00');
    const payEl = document.getElementById('ctvPayment');
    const payText = val('payment');
    payEl.textContent = payText;
    payEl.className = 'fw-semibold';
    if (payText === 'Payment Done') {
      payEl.classList.add('text-success');
    } else if (payText === 'Pending') {
      payEl.classList.add('text-warning');
    }
    document.getElementById('ctvNextAppt').textContent = val('next-appt');
    document.getElementById('ctvRemarks').textContent = val('remarks');
    document.getElementById('ctvInstruction').textContent = val('instruction');
    modal && modal.show();
  }

  document.querySelectorAll('.completed-treatment-row').forEach(function (row) {
    row.addEventListener('click', function () { showDetail(row); });
    row.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        showDetail(row);
      }
    });
  });
})();
</script>
