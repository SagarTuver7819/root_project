<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card mb-3">
    <div class="card-body py-3">
        <div class="fw-bold text-primary"><?= e(branding('hospital_name')) ?></div>
        <div class="small text-muted"><?= e(branding('hospital_tagline')) ?></div>
        <div class="small text-muted mt-1">
            <?= e(branding('hospital_address')) ?>
            <?php if (branding('hospital_phone')): ?> · Phone: <?= e(branding('hospital_phone')) ?><?php endif; ?>
            <?php if (branding('hospital_email')): ?> · Email: <?= e(branding('hospital_email')) ?><?php endif; ?>
        </div>
    </div>
</div>
<div class="card content-card">
    <div class="card-body">
        <form id="reportForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-control" name="type">
                    <?php foreach (($types ?? []) as $type): ?>
                        <option value="<?= e($type) ?>"><?= e(ucwords($type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input class="form-control" type="date" name="date_from" value="<?= e(date('Y-m-01')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input class="form-control" type="date" name="date_to" value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Run Report</button>
            </div>
        </form>
        <hr>
        <div id="reportResult" class="table-responsive text-muted">Choose filters and run a report.</div>
    </div>
</div>
<script>
document.getElementById('reportForm').addEventListener('submit', function (e) {
    e.preventDefault();
    fetch('<?= app_url('reports/data') ?>?' + new URLSearchParams(new FormData(this)))
        .then(r => r.json())
        .then(r => {
            let rows = r.data || [];
            if (!rows.length) {
                reportResult.innerHTML = 'No records found.';
                return;
            }
            let keys = Object.keys(rows[0]);
            reportResult.innerHTML = '<table class="table table-hover"><thead><tr>' +
                keys.map(k => '<th>' + k + '</th>').join('') +
                '</tr></thead><tbody>' +
                rows.map(row => '<tr>' + keys.map(k => '<td>' + (row[k] ?? '') + '</td>').join('') + '</tr>').join('') +
                '</tbody></table>';
        });
});
</script>
