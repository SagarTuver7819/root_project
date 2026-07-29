<?php
/** @var array $bill */
/** @var array $payments */
$autoPrint = !empty($autoPrint);
$status = strtolower((string) ($bill['status'] ?? 'pending'));
$particulars = trim((string) ($bill['notes'] ?? ''));
if ($particulars === '') {
    $particulars = trim((string) ($bill['treatment_name'] ?? ''));
}
if ($particulars === '') {
    $particulars = 'Dental consultation / treatment services';
}
$hospitalName = branding('hospital_name') ?: 'Roots Dentistry';
$tagline = branding('hospital_tagline') ?: 'Oral Surgeon · Implants · Surgery · Smile Design';
$address = branding('hospital_address') ?: '208 Jasal Complex, Nanavati Circle, 150 Feet Ring Rd, Rajkot, Gujarat 360007';
$phone = branding('hospital_phone') ?: '083477 60330';
$email = branding('hospital_email') ?: '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice <?= e($bill['bill_number'] ?? '') ?> | <?= e($hospitalName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #00AEEF;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #e2e8f0;
            color: var(--ink);
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }
        .toolbar {
            max-width: 820px;
            margin: 0 auto 16px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .toolbar .btn {
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        .btn-view { background: #fff; color: var(--ink); border: 1px solid var(--line) !important; }
        .btn-print { background: var(--brand); color: #fff; }
        .btn-pdf { background: #0f172a; color: #fff; }
        .invoice {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 32px 36px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 3px solid var(--brand);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand-name {
            margin: 0;
            font-size: 24px;
            color: var(--brand);
            font-weight: 800;
        }
        .brand-meta { color: var(--muted); font-size: 12px; margin-top: 4px; }
        .invoice-badge {
            text-align: right;
        }
        .invoice-badge .label {
            display: inline-block;
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
        }
        .invoice-badge .number {
            margin-top: 8px;
            font-size: 20px;
            font-weight: 800;
        }
        .invoice-badge .date { color: var(--muted); }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .box {
            background: var(--soft);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
        }
        .box h4 {
            margin: 0 0 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
        }
        .box .name { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid var(--line);
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #f1f5f9; font-weight: 700; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .totals {
            width: 280px;
            margin-left: auto;
            margin-bottom: 18px;
        }
        .totals td { border: none; padding: 6px 0; }
        .totals .grand td {
            border-top: 2px solid var(--ink);
            padding-top: 10px;
            font-size: 16px;
            font-weight: 800;
        }
        .status-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-pending { background: #e0f2fe; color: #075985; }
        .status-cancelled { background: #f1f5f9; color: #475569; }
        .notes {
            background: var(--soft);
            border-left: 4px solid var(--brand);
            padding: 10px 12px;
            margin-bottom: 18px;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px dashed var(--line);
            color: var(--muted);
            font-size: 12px;
        }
        .sign {
            text-align: center;
            min-width: 180px;
        }
        .sign .line {
            border-top: 1px solid var(--ink);
            margin-top: 48px;
            padding-top: 6px;
            color: var(--ink);
            font-weight: 600;
        }
        .hint {
            max-width: 820px;
            margin: 12px auto 0;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar, .hint, .no-print { display: none !important; }
            .invoice {
                box-shadow: none;
                border: none;
                border-radius: 0;
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a class="btn btn-view" href="<?= e(app_url('billing/' . ($bill['id'] ?? ''))) ?>">
            <i class="bi bi-arrow-left"></i> Back to Bill
        </a>
        <button type="button" class="btn btn-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Invoice
        </button>
        <button type="button" class="btn btn-pdf" onclick="window.print()">
            <i class="bi bi-file-earmark-pdf"></i> Save as PDF
        </button>
    </div>
    <p class="hint no-print">Tip: Click <strong>Save as PDF</strong> → choose printer <em>Microsoft Print to PDF</em> / <em>Save as PDF</em>.</p>

    <div class="invoice">
        <div class="header">
            <div>
                <?php if (logo_url('logo_main')): ?>
                    <img src="<?= e(logo_url('logo_main')) ?>" alt="Logo" style="max-height:58px;margin-bottom:8px;display:block;">
                <?php endif; ?>
                <h1 class="brand-name"><?= e($hospitalName) ?></h1>
                <div class="brand-meta">
                    <?= e($tagline) ?><br>
                    <?php if ($address): ?><?= e($address) ?><br><?php endif; ?>
                    <?php if ($phone): ?>Phone: <?= e($phone) ?><?php endif; ?>
                    <?php if ($phone && $email): ?> · <?php endif; ?>
                    <?php if ($email): ?>Email: <?= e($email) ?><?php endif; ?>
                </div>
            </div>
            <div class="invoice-badge">
                <div class="label">TAX / HOSPITAL INVOICE</div>
                <div class="number"><?= e($bill['bill_number'] ?? '') ?></div>
                <div class="date">Date: <?= e(format_date($bill['billing_date'] ?? null)) ?></div>
                <div style="margin-top:8px;">
                    <span class="status-pill status-<?= e(str_replace('_', '-', $status)) ?>"><?= e(ucfirst($status)) ?></span>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="box">
                <h4>Bill To (Patient)</h4>
                <div class="name"><?= e($bill['patient_name'] ?? '-') ?></div>
                <div>ID: <?= e($bill['patient_code'] ?? '-') ?></div>
                <?php if (!empty($bill['age']) || !empty($bill['gender'])): ?>
                    <div><?= e(($bill['age'] ?? '-') . ' yrs / ' . ucfirst((string) ($bill['gender'] ?? '-'))) ?></div>
                <?php endif; ?>
                <?php if (!empty($bill['mobile'])): ?><div>Mobile: <?= e($bill['mobile']) ?></div><?php endif; ?>
                <?php if (!empty($bill['email'])): ?><div>Email: <?= e($bill['email']) ?></div><?php endif; ?>
                <?php if (!empty($bill['address'])): ?><div><?= e($bill['address']) ?></div><?php endif; ?>
            </div>
            <div class="box">
                <h4>Attending Doctor</h4>
                <div class="name"><?= e(doctor_label($bill['doctor_name'] ?? null) ?: '—') ?></div>
                <?php if (!empty($bill['qualification'])): ?><div><?= e($bill['qualification']) ?></div><?php endif; ?>
                <?php if (!empty($bill['registration_number'])): ?><div>Reg. No: <?= e($bill['registration_number']) ?></div><?php endif; ?>
                <?php if (!empty($bill['plan_code'])): ?><div>Plan: <?= e($bill['plan_code']) ?></div><?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>Particulars / Description</th>
                    <th class="num" style="width:140px;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $bookingAmt = (float) ($bill['booking_amount'] ?? 0);
                $treatmentAmt = max(0, (float) ($bill['gross_amount'] ?? 0) - $bookingAmt);
                $lineNo = 1;
                ?>
                <tr>
                    <td><?= $lineNo++ ?></td>
                    <td>
                        <strong><?= e($particulars) ?></strong>
                        <?php if (!empty($bill['treatment_name']) && strcasecmp($particulars, (string) $bill['treatment_name']) !== 0): ?>
                            <div style="color:var(--muted);margin-top:4px;">Linked treatment: <?= e($bill['treatment_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= e(format_money($treatmentAmt)) ?></td>
                </tr>
                <?php if ($bookingAmt > 0): ?>
                <tr>
                    <td><?= $lineNo++ ?></td>
                    <td>
                        <strong>Booking / Case Fee</strong>
                        <div style="color:var(--muted);margin-top:4px;">First visit / new case — valid <?= e((string) branding('booking_validity_months', 3)) ?> months</div>
                    </td>
                    <td class="num"><?= e(format_money($bookingAmt)) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Gross Amount</td>
                <td class="num"><?= e(format_money($bill['gross_amount'] ?? 0)) ?></td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="num">- <?= e(format_money($bill['discount'] ?? 0)) ?></td>
            </tr>
            <tr class="grand">
                <td>Net Payable</td>
                <td class="num"><?= e(format_money($bill['net_amount'] ?? 0)) ?></td>
            </tr>
            <tr>
                <td>Paid</td>
                <td class="num"><?= e(format_money($bill['paid_amount'] ?? 0)) ?></td>
            </tr>
            <tr>
                <td>Balance Due</td>
                <td class="num"><strong><?= e(format_money($bill['pending_amount'] ?? 0)) ?></strong></td>
            </tr>
        </table>

        <?php if (!empty($payments)): ?>
            <h4 style="margin:0 0 8px;font-size:13px;">Payment Receipts</h4>
            <table>
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th class="num">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= e($p['receipt_number'] ?? '') ?></td>
                            <td><?= e(format_date($p['payment_date'] ?? null)) ?></td>
                            <td><?= e($p['payment_mode'] ?? '') ?></td>
                            <td class="num"><?= e(format_money($p['amount'] ?? 0)) ?></td>
                            <td><?= e(ucfirst((string) ($p['status'] ?? ''))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($bill['notes']) && strcasecmp(trim((string) $bill['notes']), $particulars) !== 0): ?>
            <div class="notes"><strong>Notes:</strong> <?= nl2br(e($bill['notes'])) ?></div>
        <?php endif; ?>

        <div class="footer">
            <div>
                This is a computer-generated hospital invoice for the patient.<br>
                Please retain for your records. For queries contact the front desk.
            </div>
            <div class="sign">
                <div class="line">Authorized Signatory</div>
            </div>
        </div>
    </div>

    <?php if ($autoPrint): ?>
        <script>window.addEventListener('load', function () { window.print(); });</script>
    <?php endif; ?>
</body>
</html>
