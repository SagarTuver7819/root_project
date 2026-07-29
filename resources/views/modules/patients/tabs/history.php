<?php
$rows = $rows ?? [];
$typeColors = [
    'registration' => '#64748b',
    'appointment' => '#0EA5E9',
    'visit' => '#8B5CF6',
    'treatment' => '#00AEEF',
    'prescription' => '#F59E0B',
    'payment' => '#22C55E',
    'follow_up' => '#6366F1',
];
?>
<div class="patient-history-timeline">
    <?php if (empty($rows)): ?>
        <div class="text-center text-muted py-4">No history found for this patient.</div>
    <?php endif; ?>
    <?php foreach ($rows as $row):
        $type = strtolower((string) ($row['event_type'] ?? 'event'));
        $color = $typeColors[$type] ?? '#00AEEF';
        $module = (string) ($row['module_key'] ?? '');
        $refId = (string) ($row['ref_id'] ?? '');
        $link = null;
        if ($module === 'visits' && $refId !== '') {
            $link = app_url('visits/' . $refId);
        } elseif ($module === 'treatment-plans' && $refId !== '') {
            $link = app_url('treatment-plans/' . $refId);
        } elseif ($module === 'prescriptions' && $refId !== '') {
            $link = app_url('prescriptions/' . $refId);
        } elseif ($module === 'patients' && $refId !== '') {
            $link = app_url('patients/' . $refId);
        } elseif ($module === 'appointments') {
            $link = app_url('appointments');
        } elseif ($module === 'follow-ups') {
            $link = app_url('follow-ups');
        } elseif ($module === 'billing') {
            $link = app_url('billing');
        }
    ?>
        <div class="history-item" style="--h-color: <?= e($color) ?>">
            <div class="history-dot"></div>
            <div class="history-card">
                <div class="d-flex justify-content-between gap-2 flex-wrap">
                    <span class="history-type"><?= e(ucwords(str_replace('_', ' ', $type))) ?></span>
                    <span class="history-date"><?= e(format_date($row['event_date'] ?? null)) ?></span>
                </div>
                <div class="history-title"><?= e((string) ($row['title'] ?? '')) ?></div>
                <?php if (!empty($row['doctor_name'])): ?>
                    <div class="history-meta"><i class="bi bi-person-badge me-1"></i><?= e(doctor_label($row['doctor_name'])) ?></div>
                <?php endif; ?>
                <?php if ($link): ?>
                    <a class="history-link" href="<?= e($link) ?>">Open <i class="bi bi-arrow-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
