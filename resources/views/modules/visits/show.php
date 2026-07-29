<?php
$actions = '';
if (can('visits.view')) {
    $actions .= '<a href="' . app_url('visits/' . ($visit['id'] ?? '') . '/print') . '" class="btn btn-primary me-2" target="_blank"><i class="bi bi-printer me-1"></i>Print Report</a>';
}
if (can('billing.add')) {
    $billQs = http_build_query(array_filter([
        'patient_id' => $visit['patient_id'] ?? null,
        'doctor_id' => $visit['doctor_id'] ?? null,
        'treatment_master_id' => $visit['treatment_master_id'] ?? null,
    ]));
    $actions .= '<a href="' . app_url('billing/create?' . $billQs) . '" class="btn btn-success me-2"><i class="bi bi-receipt me-1"></i>Collect Payment / Create Bill</a>';
}
if (can('visits.edit')) {
    $actions .= '<a href="' . app_url('visits/' . ($visit['id'] ?? '') . '/edit') . '" class="btn btn-light me-2"><i class="bi bi-pencil me-1"></i>Edit Notes</a>';
}
if (($visit['status'] ?? '') !== 'completed' && can('visits.edit')) {
    $actions .= '<form method="post" action="' . app_url('visits/' . ($visit['id'] ?? '') . '/complete') . '" class="d-inline ajax-form" data-reload="1">' . csrf_field() . '<button class="btn btn-outline-success"><i class="bi bi-check2-circle me-1"></i>Complete Visit</button></form>';
}
require __DIR__ . '/../../components/page-header.php';
?>

<style>
.clinical-report-card {
    background: #ffffff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    padding: 2rem;
}
.report-header-banner {
    border-bottom: 2px solid var(--primary-color, #00AEEF);
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
}
.report-hospital-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--primary-color, #00AEEF);
    letter-spacing: -0.02em;
}
.report-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    border-left: 4px solid var(--primary-color, #00AEEF);
    padding-left: 0.6rem;
    margin-bottom: 1rem;
    margin-top: 1.5rem;
}
.meta-box {
    background: #f8fafc;
    border-radius: 0.75rem;
    padding: 1rem;
    border: 1px solid #f1f5f9;
}
.table-clinical th {
    background: #f1f5f9;
    font-weight: 600;
    color: #334155;
}
.signature-box {
    text-align: right;
    margin-top: 3rem;
    padding-top: 1rem;
}
.signature-line {
    display: inline-block;
    border-top: 2px solid #cbd5e1;
    width: 220px;
    padding-top: 0.5rem;
    font-weight: 700;
    color: #334155;
}
</style>

<div class="container-fluid p-0">
    <div class="clinical-report-card">
        <!-- Hospital Header Banner -->
        <div class="report-header-banner d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <?php if (logo_url('logo_main')): ?>
                    <img src="<?= e(logo_url('logo_main')) ?>" alt="Hospital Logo" style="max-height: 65px; width: auto; object-fit: contain;">
                <?php else: ?>
                    <div>
                        <div class="report-hospital-title"><?= e(branding('hospital_name', 'Roots Dentistry')) ?></div>
                        <div class="text-muted small"><?= e(branding('hospital_tagline', 'Oral Surgeon · Implants · Surgery · Smile Design')) ?></div>
                        <div class="text-muted small mt-1">
                            <?= e(branding('hospital_address')) ?>
                            <?php if (branding('hospital_phone')): ?> · Tel: <?= e(branding('hospital_phone')) ?><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-md-end">
                <span class="badge bg-primary fs-6 px-3 py-2">Clinical Visit Report</span>
                <div class="fw-bold fs-5 mt-1"><?= e($visit['visit_code'] ?? '') ?></div>
                <div class="text-muted small">Date: <strong><?= e(format_date($visit['visit_date'] ?? null)) ?> <?= e(substr((string)($visit['visit_time'] ?? ''), 0, 5)) ?></strong></div>
                <div class="mt-1"><?= status_badge($visit['status'] ?? 'in_progress') ?></div>
            </div>
        </div>

        <!-- Patient & Doctor Info Grid -->
        <div class="row g-3 mb-4">
            <div class="col-md-7">
                <div class="meta-box h-100">
                    <div class="text-primary fw-bold mb-2 border-bottom pb-1"><i class="bi bi-person-vcard me-1"></i> Patient Demographics</div>
                    <div class="row g-2">
                        <div class="col-sm-6"><strong>Patient Name:</strong> <?= e($visit['patient_name'] ?? '-') ?></div>
                        <div class="col-sm-6"><strong>Patient ID:</strong> <?= e($visit['patient_code'] ?? '-') ?></div>
                        <div class="col-sm-6"><strong>Age / Gender:</strong> <?= e(($visit['age'] ?? '-') . ' yrs / ' . ucfirst($visit['gender'] ?? '-')) ?></div>
                        <div class="col-sm-6"><strong>Mobile:</strong> <?= e($visit['patient_mobile'] ?? '-') ?></div>
                        <div class="col-sm-6"><strong>Blood Group:</strong> <?= e($visit['blood_group'] ?? 'Not specified') ?></div>
                        <div class="col-sm-6"><strong>Email:</strong> <?= e($visit['patient_email'] ?? '-') ?></div>
                        <?php if (!empty($visit['allergies'])): ?>
                            <div class="col-12 text-danger"><strong>Known Allergies:</strong> <?= e($visit['allergies']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($visit['medical_history'])): ?>
                            <div class="col-12"><strong>Medical History:</strong> <?= e($visit['medical_history']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="meta-box h-100">
                    <div class="text-primary fw-bold mb-2 border-bottom pb-1"><i class="bi bi-person-badge me-1"></i> Attending Doctor</div>
                    <div><strong>Doctor Name:</strong> <?= e(doctor_label($visit['doctor_name'] ?? '')) ?></div>
                    <div><strong>Specialization:</strong> <?= e($visit['specialization'] ?? 'Dental Surgeon') ?></div>
                    <div><strong>Qualification:</strong> <?= e($visit['qualification'] ?? 'BDS, MDS') ?></div>
                    <?php if (!empty($visit['registration_number'])): ?>
                        <div><strong>Reg. No:</strong> <?= e($visit['registration_number']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Clinical Diagnosis & Case Notes -->
        <div class="report-section-title"><i class="bi bi-clipboard-pulse me-1"></i> Clinical Evaluation</div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100">
                    <strong class="text-secondary d-block mb-1">Chief Complaint</strong>
                    <div><?= nl2br(e($visit['chief_complaint'] ?: 'No complaint recorded.')) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100">
                    <strong class="text-secondary d-block mb-1">Symptoms</strong>
                    <div><?= nl2br(e($visit['symptoms'] ?: 'None reported.')) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100">
                    <strong class="text-secondary d-block mb-1">Clinical Examination Findings</strong>
                    <div><?= nl2br(e($visit['clinical_examination'] ?: 'No specific findings logged.')) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-primary">
                    <strong class="text-primary d-block mb-1">Diagnosis</strong>
                    <div class="fw-bold"><?= nl2br(e($visit['diagnosis'] ?: 'Under evaluation.')) ?></div>
                </div>
            </div>
        </div>

        <!-- Dental Tooth Examination Findings (If Any) -->
        <?php if (!empty($examinations)): ?>
            <div class="report-section-title"><i class="bi bi-diagram-3 me-1"></i> Dental Examination Chart</div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle table-clinical">
                    <thead>
                        <tr>
                            <th>Tooth #</th>
                            <th>Tooth Condition</th>
                            <th>Complaint</th>
                            <th>Clinical Findings</th>
                            <th>Diagnosis</th>
                            <th>Recommended Treatment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examinations as $exam): ?>
                            <tr>
                                <td><span class="badge bg-dark fs-6"><?= e($exam['tooth_number'] ?? '-') ?></span></td>
                                <td><?= e(ucfirst($exam['tooth_condition'] ?? '-')) ?></td>
                                <td><?= e($exam['complaint'] ?? '-') ?></td>
                                <td><?= e($exam['clinical_findings'] ?? '-') ?></td>
                                <td><?= e($exam['diagnosis'] ?? '-') ?></td>
                                <td><?= e($exam['recommended_treatment'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Prescriptions Table (If Any) -->
        <?php if (!empty($prescriptions)): ?>
            <div class="report-section-title"><i class="bi bi-capsule me-1"></i> Prescribed Medications</div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle table-clinical">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medicine Name</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Food Timing</th>
                            <th>Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $pNo = 1; foreach ($prescriptions as $p): ?>
                            <tr>
                                <td><?= $pNo++ ?></td>
                                <td class="fw-bold"><?= e($p['medicine_name'] ?? '-') ?></td>
                                <td><?= e($p['dosage'] ?? '-') ?></td>
                                <td><?= e($p['frequency'] ?? '-') ?></td>
                                <td><?= e($p['duration'] ?? '-') ?></td>
                                <td><span class="badge bg-info text-dark"><?= e(ucwords(str_replace('_', ' ', $p['before_after_food'] ?? '-'))) ?></span></td>
                                <td><?= e($p['instructions'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Recommended Treatment Plan -->
        <div class="report-section-title"><i class="bi bi-journal-medical me-1"></i> Recommended Treatment & Doctor Notes</div>
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <div class="p-3 bg-light rounded-3">
                    <strong class="text-secondary d-block mb-1">Recommended Treatment Plan</strong>
                    <div><?= nl2br(e($visit['recommended_treatment'] ?: 'Standard care recommended.')) ?></div>
                    <?php if (!empty($visit['doctor_notes'])): ?>
                        <hr>
                        <strong class="text-secondary d-block mb-1">Doctor Advice / Notes</strong>
                        <div><?= nl2br(e($visit['doctor_notes'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 text-center">
                    <strong class="text-secondary d-block mb-2">Follow-up Required?</strong>
                    <?php if (!empty($visit['follow_up_required'])): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 mb-2"><i class="bi bi-calendar-event me-1"></i>YES</span>
                        <div class="fw-bold">Follow-up Date: <?= e(format_date($visit['follow_up_date'] ?? null)) ?></div>
                    <?php else: ?>
                        <span class="badge bg-secondary fs-6 px-3 py-2">NO FOLLOW-UP</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Bar for Payment Collection -->
        <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 border">
            <div>
                <strong class="d-block"><i class="bi bi-credit-card me-1"></i> Billing & Payment Action</strong>
                <span class="text-muted small">Proceed to generate invoice or collect payment for this patient visit.</span>
            </div>
            <a href="<?= app_url('billing/create?' . http_build_query(array_filter([
                'patient_id' => $visit['patient_id'] ?? null,
                'doctor_id' => $visit['doctor_id'] ?? null,
                'treatment_master_id' => $visit['treatment_master_id'] ?? null,
            ]))) ?>" class="btn btn-success">
                <i class="bi bi-receipt me-1"></i> Collect Payment / Generate Bill
            </a>
        </div>

        <!-- Doctor Signature Footer -->
        <div class="signature-box">
            <div class="signature-line">
                <?= e(doctor_label($visit['doctor_name'] ?? '')) ?><br>
                <small class="text-muted fw-normal">Authorized Dental Surgeon</small>
            </div>
        </div>
    </div>
</div>
