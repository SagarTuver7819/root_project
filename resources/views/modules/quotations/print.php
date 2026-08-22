<?php
/** @var array $quotation */
/** @var array $items */
$autoPrint = !empty($autoPrint);
$hospitalName = branding('hospital_name') ?: 'Roots Dentistry';
$tagline = branding('hospital_tagline') ?: 'Oral Surgeon · Implants · Surgery · Smile Design';
$address = branding('hospital_address') ?: '';
$phone = branding('hospital_phone') ?: '';
$email = branding('hospital_email') ?: '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation <?= e($quotation['quotation_number'] ?? '') ?> | <?= e($hospitalName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand:#00AEEF; --ink:#0f172a; --muted:#64748b; --line:#cbd5e1; --soft:#f8fafc; }
        * { box-sizing:border-box; }
        body { margin:0; padding:24px; background:#e2e8f0; color:var(--ink); font-family:"Segoe UI",Arial,sans-serif; font-size:13px; line-height:1.45; }
        .toolbar { max-width:820px; margin:0 auto 16px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; }
        .toolbar .btn { border:0; border-radius:8px; padding:10px 16px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:14px; }
        .btn-view { background:#fff; color:var(--ink); border:1px solid var(--line)!important; }
        .btn-print { background:var(--brand); color:#fff; }
        .doc { max-width:820px; margin:0 auto; background:#fff; border:1px solid var(--line); border-radius:12px; padding:32px 36px; box-shadow:0 8px 24px rgba(15,23,42,.08); }
        .header { display:flex; justify-content:space-between; gap:20px; border-bottom:3px solid var(--brand); padding-bottom:16px; margin-bottom:20px; }
        .brand-name { margin:0; font-size:24px; color:var(--brand); font-weight:800; }
        .brand-meta { color:var(--muted); font-size:12px; margin-top:4px; }
        .badge { text-align:right; }
        .badge .label { display:inline-block; background:var(--brand); color:#fff; font-weight:700; letter-spacing:.04em; padding:4px 12px; border-radius:4px; font-size:12px; }
        .badge .number { margin-top:8px; font-size:20px; font-weight:800; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
        .box { background:var(--soft); border:1px solid #e2e8f0; border-radius:8px; padding:14px; }
        .box h4 { margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
        table { width:100%; border-collapse:collapse; margin-bottom:16px; }
        th, td { border:1px solid var(--line); padding:10px 12px; text-align:left; vertical-align:top; }
        th { background:#f1f5f9; font-weight:700; }
        td.num, th.num { text-align:right; white-space:nowrap; }
        .totals { width:280px; margin-left:auto; margin-bottom:18px; }
        .totals td { border:none; padding:6px 0; }
        .totals .grand td { border-top:2px solid var(--ink); padding-top:10px; font-size:16px; font-weight:800; }
        .notes { background:var(--soft); border-left:4px solid var(--brand); padding:10px 12px; margin-bottom:18px; }
        .footer { display:flex; justify-content:space-between; gap:24px; margin-top:28px; padding-top:16px; border-top:1px dashed var(--line); color:var(--muted); font-size:12px; }
        .sign { text-align:center; min-width:180px; }
        .sign .line { border-top:1px solid var(--ink); margin-top:48px; padding-top:6px; color:var(--ink); font-weight:600; }
        @media print { body { background:#fff; padding:0; } .toolbar, .no-print { display:none!important; } .doc { box-shadow:none; border:none; border-radius:0; max-width:none; padding:0; } }
    </style>
    <?php if ($autoPrint): ?><script>window.addEventListener('load', function () { window.print(); });</script><?php endif; ?>
</head>
<body>
<div class="toolbar no-print">
    <a class="btn btn-view" href="<?= e(app_url('quotations/' . ($quotation['id'] ?? '') . '/edit')) ?>"><i class="bi bi-arrow-left"></i> Back</a>
    <button type="button" class="btn btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="doc">
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
        <div class="badge">
            <div class="label">TREATMENT QUOTATION</div>
            <div class="number"><?= e($quotation['quotation_number'] ?? '') ?></div>
            <div class="date">Date: <?= e(format_date($quotation['quotation_date'] ?? null)) ?></div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <h4>Patient</h4>
            <div class="name" style="font-size:16px;font-weight:700;"><?= e($quotation['patient_name'] ?? '') ?></div>
            <div><?= e($quotation['patient_code'] ?? '') ?> · <?= e($quotation['mobile'] ?? '') ?></div>
            <?php if (!empty($quotation['address'])): ?><div><?= e($quotation['address']) ?></div><?php endif; ?>
        </div>
        <div class="box">
            <h4>Consulting Doctor</h4>
            <div class="name" style="font-size:16px;font-weight:700;"><?= e(doctor_label($quotation['doctor_name'] ?? '—')) ?></div>
            <?php if (!empty($quotation['qualification'])): ?><div><?= e($quotation['qualification']) ?></div><?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Treatment / Procedure</th>
                <th>Teeth</th>
                <th>Doctor</th>
                <th class="num">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= (int) ($i + 1) ?></td>
                    <td><?= e($item['description'] ?? '') ?></td>
                    <td><?= e($item['teeth'] ?? '—') ?></td>
                    <td><?= e(doctor_label($item['doctor_name'] ?? '—')) ?></td>
                    <td class="num"><?= e(number_format((float) ($item['amount'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Gross Total</td><td class="num">₹<?= e(number_format((float) ($quotation['gross_amount'] ?? 0), 2)) ?></td></tr>
        <?php if ((float) ($quotation['discount'] ?? 0) > 0): ?>
            <tr><td>Discount</td><td class="num">- ₹<?= e(number_format((float) $quotation['discount'], 2)) ?></td></tr>
        <?php endif; ?>
        <tr class="grand"><td>Net Quotation</td><td class="num">₹<?= e(number_format((float) ($quotation['net_amount'] ?? 0), 2)) ?></td></tr>
    </table>

    <?php if (!empty($quotation['notes'])): ?>
        <div class="notes"><strong>Notes:</strong> <?= nl2br(e($quotation['notes'])) ?></div>
    <?php endif; ?>

    <div class="footer">
        <div>This quotation is an estimate only. Final billing may vary based on clinical findings.</div>
        <div class="sign"><div class="line">Authorized Signatory</div></div>
    </div>
</div>
</body>
</html>
