<?php
$actions = '<a class="btn btn-light" href="' . app_url('billing') . '"><i class="bi bi-arrow-left me-1"></i>Back to Bills</a>';
require __DIR__ . '/../../components/page-header.php';

$bill = $bill ?? [];
$isEdit = !empty($bill['id']);
$bookingStatus = $bookingStatus ?? ['due' => true, 'amount' => 300, 'message' => '', 'label' => 'New Case'];
$bookingFee = (float) ($bookingFee ?? 300);
$bookingValidityMonths = (int) ($bookingValidityMonths ?? 3);

$selectedPatient = old('patient_id', $bill['patient_id'] ?? $_GET['patient_id'] ?? '');
$selectedDoctor = old('doctor_id', $bill['doctor_id'] ?? $_GET['doctor_id'] ?? '');
$selectedTreatment = old('treatment_master_id', $_GET['treatment_master_id'] ?? '');
$selectedPlan = old('treatment_plan_id', $bill['treatment_plan_id'] ?? $_GET['treatment_plan_id'] ?? '');

$billingDate = old('billing_date', $bill['billing_date'] ?? date('Y-m-d'));
$treatmentAmount = old('treatment_amount', $treatmentAmount ?? ($prefillAmount ?? ''));
$discount = old('discount', $bill['discount'] ?? 0);
$bookingAmount = old('booking_amount', $isEdit ? ($bill['booking_amount'] ?? 0) : ($bookingStatus['due'] ? $bookingStatus['amount'] : 0));
?>

<div class="card content-card">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="card-title mb-0"><?= e($isEdit ? 'Edit Invoice / Bill' : 'Create New Hospital Invoice & Bill') ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? app_url('billing/' . $bill['id']) : app_url('billing') ?>" class="ajax-form"<?= $isEdit ? ' data-redirect="' . e(app_url('billing')) . '"' : '' ?>>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Patient <span class="text-danger">*</span></label>
                    <select class="form-select" name="patient_id" id="patientSelect" required>
                        <option value="">Select Patient</option>
                        <?php foreach (($patients ?? []) as $p): ?>
                            <option value="<?= e($p['id']) ?>" <?= (string) $selectedPatient === (string) $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Attending Doctor <span class="text-danger">*</span></label>
                    <select class="form-select" name="doctor_id" id="doctorSelect" required>
                        <option value="">Select Doctor</option>
                        <?php foreach (($doctors ?? []) as $d): ?>
                            <option value="<?= e($d['id']) ?>" <?= (string) $selectedDoctor === (string) $d['id'] ? 'selected' : '' ?>>
                                <?= e(doctor_label($d['name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Billing Date <span class="text-danger">*</span></label>
                    <input class="form-control" type="date" name="billing_date" id="billingDate" value="<?= e($billingDate) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Select Dental Treatment / Procedure</label>
                    <select class="form-select" id="treatmentSelect">
                        <option value="" data-cost="0">Select Procedure to Auto-Fill Amount (Optional)</option>
                        <?php foreach (($treatments ?? []) as $tm):
                            $tmPrice = (float) ($tm['default_price'] ?? $tm['cost'] ?? 0);
                        ?>
                            <option value="<?= e($tm['id']) ?>" data-cost="<?= e((string) $tmPrice) ?>" <?= (string)$selectedTreatment === (string)$tm['id'] ? 'selected' : '' ?>>
                                <?= e($tm['name']) ?> — ₹<?= e(number_format($tmPrice, 2)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Selecting a procedure fills Treatment Amount. Booking fee is added after that when due.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label font-weight-bold">Treatment Plan Link (Optional)</label>
                    <select class="form-select" name="treatment_plan_id" id="treatmentPlanSelect">
                        <option value="">None / Direct Billing</option>
                        <?php foreach (($treatmentPlans ?? []) as $tp): ?>
                            <?php $planCost = (float) ($tp['estimated_cost'] ?? $tp['net_amount'] ?? $tp['default_price'] ?? 0); ?>
                            <option value="<?= e($tp['id']) ?>" data-cost="<?= e((string) $planCost) ?>" <?= (string)$selectedPlan === (string)$tp['id'] ? 'selected' : '' ?>>
                                <?= e($tp['plan_code'] ?? ('Plan #' . $tp['id'])) ?> — <?= e($tp['treatment_name'] ?? 'Dental Plan') ?> (₹<?= e(number_format($planCost, 2)) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <div id="bookingCaseBanner" class="alert <?= !empty($bookingStatus['due']) ? 'alert-warning' : 'alert-success' ?> mb-0 py-2">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <strong id="bookingCaseLabel"><?= e($bookingStatus['label'] ?? '') ?></strong>
                                <div class="small mb-0" id="bookingCaseMessage"><?= e($bookingStatus['message'] ?? '') ?></div>
                            </div>
                            <span class="badge bg-dark" id="bookingValidityBadge">Valid <?= e((string) $bookingValidityMonths) ?> months · Fee ₹<?= e(number_format($bookingFee, 0)) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="text-primary fw-bold mb-3"><i class="bi bi-calculator me-1"></i> Bill Financial Details</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold">Treatment Amount (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input class="form-control fw-bold" type="number" step="0.01" name="treatment_amount" id="treatmentAmount" value="<?= e((string) $treatmentAmount) ?>" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold">Booking Amount (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input class="form-control fw-bold text-warning" type="number" step="0.01" name="booking_amount" id="bookingAmount" value="<?= e((string) $bookingAmount) ?>" readonly>
                                </div>
                                <div class="form-text">Added after treatment for new / renewed cases.</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label font-weight-bold">Discount (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input class="form-control" type="number" step="0.01" name="discount" id="discountAmount" value="<?= e((string) $discount) ?>" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Gross / Net Payable (₹)</label>
                                <div class="input-group mb-1">
                                    <span class="input-group-text">Gross</span>
                                    <input class="form-control fw-bold" type="text" id="grossAmountDisplay" value="0.00" readonly>
                                    <input type="hidden" name="gross_amount" id="grossAmount" value="0">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white">Net</span>
                                    <input class="form-control fw-bold fs-5 text-success bg-white" type="text" id="netAmountDisplay" value="0.00" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <label class="form-label font-weight-bold">Billing Notes & Particulars</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Enter treatment details, particulars, or payment terms..."><?= e(old('notes', $bill['notes'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex gap-2 justify-content-end">
                <a class="btn btn-light" href="<?= app_url('billing') ?>">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i><?= e($isEdit ? 'Update Bill' : 'Generate & Create Bill') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const treatmentSelect = document.getElementById('treatmentSelect');
    const treatmentPlanSelect = document.getElementById('treatmentPlanSelect');
    const patientSelect = document.getElementById('patientSelect');
    const billingDate = document.getElementById('billingDate');
    const treatmentInput = document.getElementById('treatmentAmount');
    const bookingInput = document.getElementById('bookingAmount');
    const discountInput = document.getElementById('discountAmount');
    const grossHidden = document.getElementById('grossAmount');
    const grossDisplay = document.getElementById('grossAmountDisplay');
    const netDisplay = document.getElementById('netAmountDisplay');
    const banner = document.getElementById('bookingCaseBanner');
    const caseLabel = document.getElementById('bookingCaseLabel');
    const caseMessage = document.getElementById('bookingCaseMessage');
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    const statusUrl = <?= json_encode(app_url('billing/booking-status')) ?>;

    function calculateTotals() {
        const treatment = parseFloat(treatmentInput.value) || 0;
        const booking = parseFloat(bookingInput.value) || 0;
        const disc = parseFloat(discountInput.value) || 0;
        const gross = Math.max(0, treatment + booking);
        const net = Math.max(0, gross - disc);
        grossHidden.value = gross.toFixed(2);
        grossDisplay.value = gross.toFixed(2);
        netDisplay.value = net.toFixed(2);
    }

    function applyBookingStatus(status) {
        const due = !!(status && status.due);
        const amount = due ? (parseFloat(status.amount) || 0) : 0;
        if (!isEdit || parseFloat(bookingInput.value || 0) === 0) {
            bookingInput.value = amount.toFixed(2);
        }
        if (caseLabel) caseLabel.textContent = status.label || '';
        if (caseMessage) caseMessage.textContent = status.message || '';
        if (banner) {
            banner.classList.remove('alert-warning', 'alert-success');
            banner.classList.add(due ? 'alert-warning' : 'alert-success');
        }
        calculateTotals();
    }

    function refreshBookingStatus() {
        const patientId = patientSelect.value;
        if (!patientId) {
            applyBookingStatus({
                due: true,
                amount: <?= json_encode($bookingFee) ?>,
                label: 'New Case · Booking ₹<?= (int) $bookingFee ?>',
                message: 'Select patient to confirm booking case status'
            });
            return;
        }
        $.getJSON(statusUrl, {
            patient_id: patientId,
            billing_date: billingDate.value || ''
        }).done(function (res) {
            const status = (res && res.data && res.data.status) ? res.data.status : null;
            if (status) {
                applyBookingStatus(status);
            }
        });
    }

    function setTreatmentFromOption(selectEl) {
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        const cost = parseFloat(selectedOption?.dataset?.cost) || 0;
        if (cost > 0) {
            treatmentInput.value = cost.toFixed(2);
            calculateTotals();
        }
    }

    if (treatmentSelect) {
        treatmentSelect.addEventListener('change', function () {
            setTreatmentFromOption(this);
        });
        if (treatmentSelect.value) {
            const cost = parseFloat(treatmentSelect.options[treatmentSelect.selectedIndex]?.dataset?.cost) || 0;
            if (cost > 0 && (!treatmentInput.value || parseFloat(treatmentInput.value) === 0)) {
                treatmentInput.value = cost.toFixed(2);
            }
        }
    }

    if (treatmentPlanSelect) {
        treatmentPlanSelect.addEventListener('change', function () {
            setTreatmentFromOption(this);
        });
    }

    patientSelect.addEventListener('change', refreshBookingStatus);
    billingDate.addEventListener('change', refreshBookingStatus);
    treatmentInput.addEventListener('input', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    calculateTotals();
    if (!isEdit && patientSelect.value) {
        refreshBookingStatus();
    }
});
</script>
