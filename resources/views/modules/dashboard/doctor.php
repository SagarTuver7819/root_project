<?php
$actions = '<a href="' . app_url('queue') . '" class="btn btn-light"><i class="bi bi-people me-1"></i>Full Queue</a>'
    . '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar3 me-1"></i>Calendar</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 mb-1">My Patients — <?= e(format_date($today ?? date('Y-m-d'))) ?></h3>
                <div class="text-muted small">Front desk e patients moklya — name par click kari ne details add karo</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Code</th>
                        <th>Mobile</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctorQueue)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No patients assigned to you right now.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($doctorQueue ?? []) as $row):
                        $apptId = (int) ($row['id'] ?? 0);
                        $visitId = (int) ($row['visit_id'] ?? 0);
                        $openUrl = $visitId > 0
                            ? app_url('visits/' . $visitId . '/edit')
                            : app_url('visits/open/' . $apptId);
                        $entryLabel = ($row['entry_type'] ?? '') === 'walk_in' ? 'Walk-in' : 'Appointment';
                    ?>
                        <tr>
                            <td><strong><?= e(format_time($row['start_time'] ?? null)) ?></strong></td>
                            <td>
                                <a href="<?= e($openUrl) ?>" class="fw-semibold text-decoration-none">
                                    <?= e($row['patient_name'] ?? '') ?>
                                </a>
                                <?php if (!empty($row['visit_reason'])): ?>
                                    <div class="text-muted small"><?= e($row['visit_reason']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['patient_code'] ?? $row['appointment_code'] ?? '') ?></td>
                            <td><?= e($row['mobile'] ?? '') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($entryLabel) ?></span></td>
                            <td><?= status_badge($row['status'] ?? '') ?></td>
                            <td>
                                <?php if ($visitId > 0): ?>
                                    <a href="<?= e(app_url('visits/' . $visitId . '/edit')) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil me-1"></i>Add Details
                                    </a>
                                <?php else: ?>
                                    <form method="post" action="<?= app_url('visits/start/' . $apptId) ?>" class="ajax-form" data-reload="1">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-primary"><i class="bi bi-play-fill me-1"></i>Open &amp; Add Details</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
