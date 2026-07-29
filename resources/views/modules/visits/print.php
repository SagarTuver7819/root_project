<?php
/** @var array $visit */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dental Clinical Report - <?= e($visit['visit_code'] ?? '') ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            background: #fff;
            font-size: 13px;
            line-height: 1.5;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            padding: 30px;
            border-radius: 8px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #00AEEF;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #00AEEF;
            margin: 0;
        }
        .tagline {
            font-size: 12px;
            color: #64748b;
        }
        .report-title {
            text-align: right;
        }
        .report-badge {
            background: #00AEEF;
            color: #fff;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }
        .visit-code {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            border-left: 4px solid #00AEEF;
            padding-left: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
            background: #f8fafc;
            padding-top: 4px;
            padding-bottom: 4px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 6px;
        }
        .info-box strong {
            color: #334155;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            font-weight: bold;
            color: #334155;
        }
        .footer-sign {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-line {
            text-align: center;
            border-top: 2px solid #94a3b8;
            width: 200px;
            padding-top: 5px;
            font-weight: bold;
        }
        @media print {
            body { padding: 0; }
            .print-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="print-container">
    <!-- Header -->
    <div class="header">
        <div>
            <div class="hospital-name"><?= e(branding('hospital_name', 'Roots Dentistry')) ?></div>
            <div class="tagline"><?= e(branding('hospital_tagline', 'Oral Surgeon · Implants · Surgery · Smile Design')) ?></div>
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                <?= e(branding('hospital_address')) ?>
                <?php if (branding('hospital_phone')): ?> | Tel: <?= e(branding('hospital_phone')) ?><?php endif; ?>
                <?php if (branding('hospital_email')): ?> | <?= e(branding('hospital_email')) ?><?php endif; ?>
            </div>
        </div>
        <div class="report-title">
            <div class="report-badge">CLINICAL VISIT REPORT</div>
            <div class="visit-code"><?= e($visit['visit_code'] ?? '') ?></div>
            <div style="font-size: 11px; color: #64748b;">
                Date: <?= e(format_date($visit['visit_date'] ?? null)) ?> <?= e(substr((string)($visit['visit_time'] ?? ''), 0, 5)) ?>
            </div>
        </div>
    </div>

    <!-- Patient & Doctor Info -->
    <div class="info-grid">
        <div class="info-box">
            <div style="font-weight: bold; color: #00AEEF; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px;">PATIENT DETAILS</div>
            <div><strong>Name:</strong> <?= e($visit['patient_name'] ?? '-') ?></div>
            <div><strong>Patient ID:</strong> <?= e($visit['patient_code'] ?? '-') ?></div>
            <div><strong>Age / Gender:</strong> <?= e(($visit['age'] ?? '-') . ' yrs / ' . ucfirst($visit['gender'] ?? '-')) ?></div>
            <div><strong>Contact:</strong> <?= e($visit['patient_mobile'] ?? '-') ?></div>
            <div><strong>Blood Group:</strong> <?= e($visit['blood_group'] ?? 'Not specified') ?></div>
            <?php if (!empty($visit['allergies'])): ?>
                <div style="color: #dc2626;"><strong>Allergies:</strong> <?= e($visit['allergies']) ?></div>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <div style="font-weight: bold; color: #00AEEF; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px;">DOCTOR DETAILS</div>
            <div><strong>Doctor:</strong> <?= e(doctor_label($visit['doctor_name'] ?? '')) ?></div>
            <div><strong>Specialization:</strong> <?= e($visit['specialization'] ?? 'Dental Surgeon') ?></div>
            <div><strong>Qualification:</strong> <?= e($visit['qualification'] ?? 'BDS, MDS') ?></div>
            <?php if (!empty($visit['registration_number'])): ?>
                <div><strong>Reg. No:</strong> <?= e($visit['registration_number']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Clinical Case Details -->
    <div class="section-title">CLINICAL EVALUATION & DIAGNOSIS</div>
    <table>
        <tr>
            <th style="width: 25%;">Chief Complaint</th>
            <td><?= nl2br(e($visit['chief_complaint'] ?: 'No complaint recorded.')) ?></td>
        </tr>
        <tr>
            <th>Symptoms</th>
            <td><?= nl2br(e($visit['symptoms'] ?: 'None reported.')) ?></td>
        </tr>
        <tr>
            <th>Clinical Examination</th>
            <td><?= nl2br(e($visit['clinical_examination'] ?: 'No specific findings logged.')) ?></td>
        </tr>
        <tr>
            <th>Diagnosis</th>
            <td><strong><?= nl2br(e($visit['diagnosis'] ?: 'Under evaluation.')) ?></strong></td>
        </tr>
    </table>

    <!-- Tooth Chart (If Any) -->
    <?php if (!empty($examinations)): ?>
        <div class="section-title">DENTAL TOOTH EXAMINATION FINDINGS</div>
        <table>
            <thead>
                <tr>
                    <th>Tooth #</th>
                    <th>Condition</th>
                    <th>Complaint</th>
                    <th>Clinical Findings</th>
                    <th>Diagnosis</th>
                    <th>Recommended Treatment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($examinations as $exam): ?>
                    <tr>
                        <td style="font-weight: bold; text-align: center;"><?= e($exam['tooth_number'] ?? '-') ?></td>
                        <td><?= e(ucfirst($exam['tooth_condition'] ?? '-')) ?></td>
                        <td><?= e($exam['complaint'] ?? '-') ?></td>
                        <td><?= e($exam['clinical_findings'] ?? '-') ?></td>
                        <td><?= e($exam['diagnosis'] ?? '-') ?></td>
                        <td><?= e($exam['recommended_treatment'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Prescriptions (If Any) -->
    <?php if (!empty($prescriptions)): ?>
        <div class="section-title">PRESCRIBED MEDICATIONS</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Timing</th>
                </tr>
            </thead>
            <tbody>
                <?php $pNo = 1; foreach ($prescriptions as $p): ?>
                    <tr>
                        <td><?= $pNo++ ?></td>
                        <td><strong><?= e($p['medicine_name'] ?? '-') ?></strong></td>
                        <td><?= e($p['dosage'] ?? '-') ?></td>
                        <td><?= e($p['frequency'] ?? '-') ?></td>
                        <td><?= e($p['duration'] ?? '-') ?></td>
                        <td><?= e(ucwords(str_replace('_', ' ', $p['before_after_food'] ?? '-'))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Treatment & Notes -->
    <div class="section-title">RECOMMENDED TREATMENT & ADVICE</div>
    <table>
        <tr>
            <th style="width: 25%;">Recommended Treatment</th>
            <td><?= nl2br(e($visit['recommended_treatment'] ?: 'Standard care.')) ?></td>
        </tr>
        <?php if (!empty($visit['doctor_notes'])): ?>
            <tr>
                <th>Doctor Notes</th>
                <td><?= nl2br(e($visit['doctor_notes'])) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <th>Follow-up Date</th>
            <td><?= !empty($visit['follow_up_required']) ? 'Required on ' . e(format_date($visit['follow_up_date'] ?? null)) : 'Not required' ?></td>
        </tr>
    </table>

    <!-- Signature -->
    <div class="footer-sign">
        <div class="signature-line">
            <?= e(doctor_label($visit['doctor_name'] ?? '')) ?><br>
            <span style="font-size: 11px; font-weight: normal; color: #64748b;">Dental Surgeon Signature</span>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        window.print();
    };
</script>
</body>
</html>
