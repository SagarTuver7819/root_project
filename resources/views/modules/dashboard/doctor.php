<?php
$actions = '<a href="' . app_url('queue') . '" class="btn btn-light"><i class="bi bi-people me-1"></i>Queue</a>'
    . '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar3 me-1"></i>Calendar</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 mb-1">My Queue — <?= e(format_date($today ?? date('Y-m-d'))) ?></h3>
                <div class="text-muted small">Today's patients assigned to you</div>
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
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctorQueue)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No appointments in your queue today.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($doctorQueue ?? []) as $row): ?>
                        <tr>
                            <td><strong><?= e(format_time($row['start_time'] ?? null)) ?></strong></td>
                            <td><?= e($row['patient_name'] ?? '') ?></td>
                            <td><?= e($row['patient_code'] ?? $row['appointment_code'] ?? '') ?></td>
                            <td><?= e($row['mobile'] ?? '') ?></td>
                            <td><?= status_badge($row['status'] ?? '') ?></td>
                            <td>
                                <form method="post" action="<?= app_url('visits/start/' . ($row['id'] ?? '')) ?>" class="ajax-form" data-reload="1">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-play-fill me-1"></i>Start Visit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
