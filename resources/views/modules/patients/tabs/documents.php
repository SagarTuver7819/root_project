<?php if (can('patients.edit')): ?>
<form method="post" action="<?= app_url('patients/' . ($id ?? '') . '/documents') ?>" enctype="multipart/form-data" class="card border-0 bg-light mb-3 ajax-form" data-reload="1">
    <div class="card-body row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="document_type" class="form-select" required>
                <option value="xray">X-Ray</option>
                <option value="consent">Consent</option>
                <option value="report">Report</option>
                <option value="photo">Photo</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">File</label>
            <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Optional note">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Upload</button>
        </div>
    </div>
</form>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Type</th>
                <th>Description</th>
                <th>Uploaded</th>
                <th>File</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (($rows ?? []) as $row): ?>
            <tr>
                <td><?= e(ucwords(str_replace('_', ' ', $row['document_type'] ?? ''))) ?></td>
                <td><?= e($row['description'] ?? '-') ?></td>
                <td><?= e(format_date($row['created_at'] ?? null, 'd M Y H:i')) ?></td>
                <td><a href="<?= e(upload_url((string) ($row['file_path'] ?? ''))) ?>" target="_blank" rel="noopener">Open</a></td>
                <td class="text-end">
                    <?php if (can('patients.edit')): ?>
                        <button type="button" class="btn btn-sm btn-light text-danger btn-delete"
                            data-url="<?= app_url('patients/' . ($id ?? $row['patient_id']) . '/documents/' . $row['id'] . '/delete') ?>"
                            data-message="Delete this document?">
                            <i class="bi bi-trash"></i>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
