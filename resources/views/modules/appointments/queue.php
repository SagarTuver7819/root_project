<?php
$actions = '<button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#walkinAddPatientModal"><i class="bi bi-person-plus me-1"></i>Add Patient</button>'
    . '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar3 me-1"></i>Calendar</a>'
    . '<a href="' . app_url('payments') . '" class="btn btn-light me-2"><i class="bi bi-wallet2 me-1"></i>Payments</a>';
require __DIR__ . '/../../components/page-header.php';

$queueMeta = [
    'scheduled' => ['name' => 'Scheduled', 'color' => '#3B82F6', 'icon' => 'bi-calendar2', 'hint' => 'Booked slots'],
    'confirmed' => ['name' => 'Confirmed', 'color' => '#0EA5E9', 'icon' => 'bi-check2-circle', 'hint' => 'Confirmed patients'],
    'waiting' => ['name' => 'Waiting', 'color' => '#F59E0B', 'icon' => 'bi-hourglass-split', 'hint' => 'In waiting area'],
    'checked_in' => ['name' => 'Checked In', 'color' => '#8B5CF6', 'icon' => 'bi-door-open', 'hint' => 'Ready for doctor'],
    'with_doctor' => ['name' => 'With Doctor', 'color' => '#6366F1', 'icon' => 'bi-person-video2', 'hint' => 'Consultation ongoing'],
    'completed' => ['name' => 'Completed', 'color' => '#22C55E', 'icon' => 'bi-check-all', 'hint' => 'Finished today'],
    'cancelled' => ['name' => 'Cancelled', 'color' => '#94A3B8', 'icon' => 'bi-x-circle', 'hint' => 'Cancelled slots'],
];

// Check DB appointment_statuses master for dynamic status meta
$dbStatuses = appointment_statuses_list();
if (!empty($dbStatuses)) {
    foreach ($dbStatuses as $st) {
        $slug = $st['slug'];
        if (!isset($queueMeta[$slug])) {
            $queueMeta[$slug] = [
                'name' => $st['name'],
                'color' => $st['color'] ?: '#00AEEF',
                'icon' => 'bi-tag',
                'hint' => $st['name'] . ' queue',
            ];
        } else {
            $queueMeta[$slug]['name'] = $st['name'];
            if (!empty($st['color'])) {
                $queueMeta[$slug]['color'] = $st['color'];
            }
        }
    }
}

$groups = [];
foreach (($queue ?? []) as $row) {
    $groups[$row['status'] ?? 'scheduled'][] = $row;
}
$totalInQueue = count($queue ?? []);
$doctorName = static function (?string $name): string {
    return doctor_label($name);
};

$highlightId = $_GET['id'] ?? '';
$highlightCode = $_GET['highlight'] ?? $_GET['code'] ?? '';
$queueView = in_array(($queueView ?? 'sheet'), ['board', 'sheet'], true) ? $queueView : 'sheet';

$sheetRows = [];
$serial = 1;
foreach (($queue ?? []) as $row) {
    $age = null;
    if (!empty($row['age'])) {
        $age = (int) $row['age'];
    } elseif (!empty($row['dob'])) {
        try {
            $age = (new DateTimeImmutable((string) $row['dob']))->diff(new DateTimeImmutable('today'))->y;
        } catch (Throwable $e) {
            $age = null;
        }
    }
    $genderRaw = strtolower(trim((string) ($row['gender'] ?? '')));
    $mf = $genderRaw === 'male' ? 'm' : ($genderRaw === 'female' ? 'f' : ($genderRaw !== '' ? substr($genderRaw, 0, 1) : ''));
    $notes = trim((string) ($row['visit_reason'] ?? ''));
    if ($notes === '' && !empty($row['treatment_name'])) {
        $notes = (string) $row['treatment_name'];
    }
    if ($notes === '' && !empty($row['notes'])) {
        $notes = (string) $row['notes'];
    }
    $docLabel = doctor_label((string) ($row['doctor_name'] ?? ''));
    if ($docLabel !== '' && $notes !== '' && stripos($notes, $docLabel) === false) {
        $notes .= ' · ' . $docLabel;
    } elseif ($notes === '' && $docLabel !== '') {
        $notes = $docLabel;
    }
    $notesLower = strtolower($notes . ' ' . (string) ($row['notes'] ?? ''));
    $hasOpg = str_contains($notesLower, 'opg');
    $status = (string) ($row['status'] ?? 'scheduled');
    $statusColor = $queueMeta[$status]['color'] ?? '#94A3B8';
    $doctorColor = trim((string) ($row['doctor_color'] ?? ''));
    if ($doctorColor === '') {
        $doctorColor = doctor_calendar_color((int) ($row['doctor_id'] ?? 0));
    }
    $sheetRows[] = [
        'serial' => $serial++,
        'row' => $row,
        'age' => $age,
        'mf' => $mf,
        'notes' => $notes,
        'has_opg' => $hasOpg,
        'status' => $status,
        'status_color' => $statusColor,
        'doctor_color' => $doctorColor,
        'ref' => trim((string) ($row['reference_doctor_name'] ?? '')),
        'is_match' => ($highlightId && (string) $row['id'] === (string) $highlightId)
            || ($highlightCode && (string) $row['appointment_code'] === (string) $highlightCode),
    ];
}

$filterQs = http_build_query(array_filter([
    'date' => $date ?? date('Y-m-d'),
    'doctor_id' => $doctorId ?? ($lockedDoctorId ?? null),
]));
?>

<style>
.kanban-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1.5rem;
    align-items: flex-start;
}
.kanban-column {
    flex: 0 0 310px;
    min-width: 310px;
    background: #f8fafc;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 220px);
}
.kanban-column-head {
    padding: 1rem 1.1rem;
    border-bottom: 2px solid var(--q-color);
    background: #ffffff;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.kanban-column-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text-primary);
}
.kanban-column-hint {
    font-size: 0.75rem;
    color: var(--text-secondary);
}
.kanban-count-badge {
    background: var(--q-color);
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
}
.kanban-column-body {
    padding: 0.8rem;
    overflow-y: auto;
    flex: 1;
    min-height: 250px;
    transition: background-color 0.2s ease, border-color 0.2s ease;
}
.kanban-column-body.drag-over {
    background-color: rgba(0, 174, 239, 0.08) !important;
    border: 2px dashed #00AEEF !important;
    border-radius: 0.75rem;
}
.kanban-card {
    background: #ffffff;
    border-radius: 0.9rem;
    border: 1px solid #e2e8f0;
    border-left: 4px solid var(--q-color);
    padding: 1rem;
    margin-bottom: 0.85rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
    cursor: grab;
    transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.15s ease, border-color 0.18s ease;
    user-select: none;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.1);
}
.kanban-card:active {
    cursor: grabbing;
}
.kanban-card.dragging {
    opacity: 0.4;
    transform: scale(0.96);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.kanban-card.highlighted-card {
    border: 2px solid #00AEEF !important;
    border-left: 5px solid #00AEEF !important;
    box-shadow: 0 0 15px rgba(0, 174, 239, 0.4) !important;
    animation: pulseGlow 1.8s infinite alternate;
}
@keyframes pulseGlow {
    0% { box-shadow: 0 0 5px rgba(0, 174, 239, 0.3); }
    100% { box-shadow: 0 0 20px rgba(0, 174, 239, 0.7); }
}
.kanban-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .5rem;
    margin-bottom: 0.55rem;
}
.kanban-patient-name {
    font-weight: 800;
    font-size: 1rem;
    color: #0f766e;
    letter-spacing: .01em;
    line-height: 1.25;
}
.kanban-time {
    font-size: 0.74rem;
    font-weight: 700;
    background: #eef8fc;
    border: 1px solid #cfeef8;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    color: #0369a1;
    white-space: nowrap;
}
.kanban-meta {
    font-size: 0.78rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem 0.55rem;
}
.kanban-treatment {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .76rem;
    font-weight: 700;
    color: #9a3412;
    background: #fff7ed;
    border: 1px solid #fdba74;
    border-radius: 999px;
    padding: .2rem .6rem;
}
.kanban-doctor {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: 0.84rem;
    font-weight: 700;
    color: #1d4ed8;
    margin-bottom: 0.5rem;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: .55rem;
    padding: .35rem .55rem;
}
.kanban-doctor i {
    color: #2563eb;
    margin-right: 0 !important;
}
.kanban-patient-line {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .65rem;
    font-size: .78rem;
    color: #64748b;
    margin-bottom: .45rem;
}
.kanban-meta i,
.kanban-patient-line i,
.kanban-doctor i,
.kanban-treatment i,
.kanban-allergy i {
    margin-right: .4rem;
}
.kanban-meta > span,
.kanban-patient-line > span {
    display: inline-flex;
    align-items: center;
}
.kanban-case {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .7rem;
    font-weight: 700;
    border-radius: 999px;
    padding: .15rem .5rem;
    margin-bottom: .45rem;
}
.kanban-case-new {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fdba74;
}
.kanban-case-active {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #6ee7b7;
}
.kanban-allergy {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .72rem;
    font-weight: 700;
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: .15rem .45rem;
    margin-bottom: .45rem;
}
.kanban-tags {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
    margin-bottom: .45rem;
}
.kanban-tag {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    border-radius: 999px;
    padding: .12rem .45rem;
}
.kanban-badge-type {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    border-radius: 999px;
    padding: .15rem .5rem;
    white-space: nowrap;
}
.kanban-badge-appointed {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.kanban-badge-walkin {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}
.kanban-badge-nature {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    border-radius: 999px;
    padding: .15rem .5rem;
    white-space: nowrap;
}
.kanban-badge-nature-new {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
}
.kanban-badge-nature-old {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.kanban-time-block {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .2rem;
}
.kanban-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #94a3b8;
}
.kanban-empty i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.4rem;
}

/* Spreadsheet / BOOK-style list */
.queue-view-toggle .btn.active {
    background: #0f766e;
    border-color: #0f766e;
    color: #fff;
}
.queue-sheet-card {
    border: 1px solid #c5cdd8;
    border-radius: 0.35rem;
    overflow: hidden;
    background: #fff;
}
.queue-sheet-wrap {
    overflow-x: auto;
    max-height: calc(100vh - 250px);
}
.queue-sheet-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    font-family: "Segoe UI", Arial, sans-serif;
    font-size: 13px;
    min-width: 980px;
    table-layout: auto;
}
.queue-sheet-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #e8eaed;
    color: #202124;
    font-weight: 700;
    text-align: center;
    padding: 8px 10px;
    border: 1px solid #b0b7c3;
    white-space: nowrap;
}
.queue-sheet-table tbody td {
    padding: 6px 10px;
    border: 1px solid #d0d5dd;
    vertical-align: middle;
    background: #fff;
    text-align: center;
}
.queue-sheet-table tbody tr:nth-child(even) td:not(.qs-notes):not(.qs-opg) {
    background: #fafbfc;
}
.queue-sheet-table tbody tr.qs-highlight td {
    outline: 2px solid #00AEEF;
    outline-offset: -2px;
}
.queue-sheet-table .qs-num,
.queue-sheet-table .qs-mf,
.queue-sheet-table .qs-age {
    text-align: center;
    white-space: nowrap;
}
.queue-sheet-table .qs-notes {
    font-weight: 600;
    min-width: 220px;
    max-width: none;
    text-align: center;
}
.queue-sheet-table .qs-opg {
    text-align: center;
    font-weight: 700;
    text-transform: lowercase;
}
.queue-sheet-table .qs-opg.has-opg {
    background: #f6ad55 !important;
    color: #7b341e;
}
.queue-sheet-table a {
    color: inherit;
    text-decoration: none;
}
.queue-sheet-table a:hover {
    text-decoration: underline;
    color: #0f766e;
}
.queue-sheet-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #94a3b8;
}
.queue-sheet-hint {
    font-size: 0.8rem;
    color: #64748b;
}
</style>

<form method="get" action="<?= app_url('queue') ?>" class="card content-card mb-4 queue-filter-card">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Queue Date</label>
                <input class="form-control" type="date" name="date" value="<?= e($date ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Doctor Filter</label>
                <?php $lockedDoctorId = $lockedDoctorId ?? null; ?>
                <select class="form-select" name="doctor_id" id="queueDoctor" <?= $lockedDoctorId ? 'disabled' : '' ?>>
                    <?php if (!$lockedDoctorId): ?>
                        <option value="">All Doctors</option>
                    <?php endif; ?>
                    <?php foreach (($doctors ?? []) as $d): ?>
                        <option value="<?= e((string) $d['id']) ?>" <?= (string) ($doctorId ?? '') === (string) $d['id'] || (string) $lockedDoctorId === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($lockedDoctorId): ?>
                    <input type="hidden" name="doctor_id" value="<?= e((string) $lockedDoctorId) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label">View</label>
                <input type="hidden" name="view" value="<?= e($queueView) ?>">
                <div class="btn-group w-100 queue-view-toggle" role="group">
                    <a class="btn btn-outline-secondary <?= $queueView === 'sheet' ? 'active' : '' ?>"
                       href="<?= app_url('queue?' . http_build_query(array_filter(['date' => $date ?? date('Y-m-d'), 'doctor_id' => $doctorId ?? ($lockedDoctorId ?? null), 'view' => 'sheet']))) ?>">
                        <i class="bi bi-table me-1"></i>Sheet
                    </a>
                    <a class="btn btn-outline-secondary <?= $queueView === 'board' ? 'active' : '' ?>"
                       href="<?= app_url('queue?' . http_build_query(array_filter(['date' => $date ?? date('Y-m-d'), 'doctor_id' => $doctorId ?? ($lockedDoctorId ?? null), 'view' => 'board']))) ?>">
                        <i class="bi bi-kanban me-1"></i>Board
                    </a>
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Filter</button>
            </div>
            <div class="col-md-2 d-flex justify-content-md-end align-items-end">
                <div class="queue-total-chip" title="Total patients today">
                    <div class="queue-total-meta">
                        <span class="queue-total-label">Today's Patients</span>
                        <span class="queue-total-hint">Walk-in &amp; Appointed</span>
                    </div>
                    <span class="queue-total-count"><?= e((string) $totalInQueue) ?></span>
                </div>
            </div>
        </div>
        <?php if ($queueView === 'sheet'): ?>
            <div class="queue-sheet-hint mt-2">Sheet view = BOOK list style (date, OPD, name, m/f, age, treatment, OPG, mobile, ref). Board toggle for old kanban.</div>
        <?php endif; ?>
    </div>
</form>

<?php if ($queueView === 'sheet'): ?>
<div class="card content-card queue-sheet-card mb-4">
    <div class="queue-sheet-wrap">
        <table class="queue-sheet-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>OPD</th>
                    <th>Name</th>
                    <th>M/F</th>
                    <th>Age</th>
                    <th>Treatment / Notes</th>
                    <th>OPG</th>
                    <th>Mo Number</th>
                    <th>Ref</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($sheetRows === []): ?>
                <tr>
                    <td colspan="12" class="queue-sheet-empty">
                        <i class="bi bi-inbox d-block mb-1" style="font-size:1.6rem"></i>
                        No patients for this date
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($sheetRows as $item):
                    $row = $item['row'];
                    $aptDate = !empty($row['appointment_date'])
                        ? date('j-n-Y', strtotime((string) $row['appointment_date']))
                        : '';
                ?>
                <tr class="<?= !empty($item['is_match']) ? 'qs-highlight' : '' ?>" id="sheet-apt-<?= e((string) $row['id']) ?>">
                    <td class="qs-num"><?= (int) $item['serial'] ?></td>
                    <td><?= e($aptDate) ?></td>
                    <td><?= e((string) ($row['patient_code'] ?? '')) ?></td>
                    <td>
                        <a href="<?= app_url('patients/' . ($row['patient_id'] ?? '')) ?>">
                            <?= e((string) ($row['patient_name'] ?? '')) ?>
                        </a>
                    </td>
                    <td class="qs-mf"><?= e((string) $item['mf']) ?></td>
                    <td class="qs-age"><?= $item['age'] !== null ? e((string) $item['age']) : '' ?></td>
                    <td class="qs-notes" style="background: <?= e($item['doctor_color']) ?>33;">
                        <?= e((string) $item['notes']) ?>
                    </td>
                    <td class="qs-opg <?= !empty($item['has_opg']) ? 'has-opg' : '' ?>">
                        <?= !empty($item['has_opg']) ? 'opg' : '' ?>
                    </td>
                    <td><?= e((string) ($row['mobile'] ?? '')) ?></td>
                    <td><?= e((string) $item['ref']) ?></td>
                    <td>
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= e($item['status_color']) ?>;margin-right:4px;"></span>
                        <?= e($queueMeta[$item['status']]['name'] ?? ucfirst(str_replace('_', ' ', $item['status']))) ?>
                    </td>
                    <td><?= e(format_time($row['start_time'] ?? null)) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const highlighted = document.querySelector('.qs-highlight');
    if (highlighted) {
        setTimeout(function () {
            highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 200);
    }
});
</script>
<?php else: ?>
<div class="kanban-board" id="kanbanBoard">
<?php
$columnKeys = ['scheduled', 'confirmed', 'waiting', 'checked_in', 'with_doctor', 'completed', 'cancelled'];
foreach ($columnKeys as $status):
    $meta = $queueMeta[$status] ?? ['name' => ucfirst($status), 'color' => '#00AEEF', 'icon' => 'bi-tag', 'hint' => 'Queue'];
    $items = $groups[$status] ?? [];
    $count = count($items);
?>
    <div class="kanban-column" style="--q-color: <?= e($meta['color']) ?>" data-status="<?= e($status) ?>">
        <div class="kanban-column-head">
            <div class="d-flex align-items-center gap-2">
                <i class="bi <?= e($meta['icon']) ?>" style="color: <?= e($meta['color']) ?>; font-size: 1.1rem;"></i>
                <div>
                    <div class="kanban-column-title"><?= e($meta['name']) ?></div>
                    <div class="kanban-column-hint"><?= e($meta['hint']) ?></div>
                </div>
            </div>
            <span class="kanban-count-badge" data-count-status="<?= e($status) ?>"><?= e((string) $count) ?></span>
        </div>
        <div class="kanban-column-body" data-status="<?= e($status) ?>">
            <?php if (empty($items)): ?>
                <div class="kanban-empty">
                    <i class="bi bi-inbox"></i>
                    <span>No appointments</span>
                </div>
            <?php endif; ?>
            <?php foreach ($items as $row):
                $isMatch = ($highlightId && (string)$row['id'] === (string)$highlightId) || ($highlightCode && (string)$row['appointment_code'] === (string)$highlightCode);
                $age = null;
                if (!empty($row['age'])) {
                    $age = (int) $row['age'];
                } elseif (!empty($row['dob'])) {
                    try {
                        $age = (new DateTimeImmutable((string) $row['dob']))->diff(new DateTimeImmutable('today'))->y;
                    } catch (Throwable $e) {
                        $age = null;
                    }
                }
                $gender = trim((string) ($row['gender'] ?? ''));
                $genderLabel = $gender !== '' ? ucfirst($gender) : '';
                $demographics = [];
                if ($age !== null) {
                    $demographics[] = $age . 'y';
                }
                if ($genderLabel !== '') {
                    $demographics[] = $genderLabel;
                }
                if (!empty($row['blood_group'])) {
                    $demographics[] = (string) $row['blood_group'];
                }
                $allergyText = trim((string) ($row['allergies'] ?? ''));
                $isWalkIn = ($row['entry_type'] ?? '') === 'walk_in';
                $isAppointed = !$isWalkIn;
                $todayDate = $date ?? date('Y-m-d');
                $patientRegDate = !empty($row['patient_reg_date']) ? substr((string) $row['patient_reg_date'], 0, 10) : '';
                $patientCreatedAt = !empty($row['patient_created_at']) ? substr((string) $row['patient_created_at'], 0, 10) : '';
                $bookingInfo = \App\Services\BookingService::statusForPatient(
                    !empty($row['patient_id']) ? (int) $row['patient_id'] : null,
                    $date ?? date('Y-m-d')
                );
                $isNewPatient = ($patientRegDate === $todayDate) || ($patientCreatedAt === $todayDate) || (!empty($bookingInfo['case_type']) && $bookingInfo['case_type'] === 'new');
                $slotEnd = format_time($row['end_time'] ?? null);
                $visitReason = trim((string) ($row['visit_reason'] ?? ''));
            ?>
                <div class="kanban-card <?= $isMatch ? 'highlighted-card' : '' ?>"
                     draggable="true"
                     data-id="<?= e((string) $row['id']) ?>"
                     data-code="<?= e((string) $row['appointment_code']) ?>"
                     data-status="<?= e((string) $status) ?>"
                     id="card-apt-<?= e((string) $row['id']) ?>">
                    
                    <div class="kanban-card-top">
                        <div>
                            <a class="kanban-patient-name text-decoration-none" href="<?= app_url('appointments/' . $row['id'] . '/edit') ?>">
                                <?= e($row['patient_name'] ?? 'Patient') ?>
                            </a>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                <?php if ($isAppointed): ?>
                                    <span class="kanban-badge-type kanban-badge-appointed" title="Booked Appointment slot">
                                        <i class="bi bi-calendar2-check"></i> Appointed
                                    </span>
                                <?php else: ?>
                                    <span class="kanban-badge-type kanban-badge-walkin" title="Direct Walk-in Patient">
                                        <i class="bi bi-person-walking"></i> Walk-in
                                    </span>
                                <?php endif; ?>

                                <?php if ($isNewPatient): ?>
                                    <span class="kanban-badge-nature kanban-badge-nature-new" title="New Patient registered today">
                                        <i class="bi bi-stars"></i> New
                                    </span>
                                <?php else: ?>
                                    <span class="kanban-badge-nature kanban-badge-nature-old" title="Existing Follow-up / Routine Patient">
                                        <i class="bi bi-person-check"></i> Existing
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="kanban-time-block">
                            <span class="kanban-time" title="Scheduled / Arrival time">
                                <i class="bi bi-clock me-1"></i><?= e(format_time($row['start_time'] ?? null)) ?>
                                <?php if ($slotEnd && $slotEnd !== '-'): ?>
                                    – <?= e($slotEnd) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="kanban-meta">
                        <span><i class="bi bi-hash"></i><?= e($row['appointment_code'] ?? '') ?></span>
                        <?php if (!empty($row['patient_code'])): ?>
                            <span><i class="bi bi-person-vcard"></i><?= e($row['patient_code']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($row['treatment_name'])): ?>
                            <span class="kanban-treatment"><i class="bi bi-tooth"></i><?= e($row['treatment_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="kanban-doctor">
                        <i class="bi bi-person-badge"></i>
                        <span><?= e($doctorName((string) ($row['doctor_name'] ?? ''))) ?></span>
                    </div>

                    <div class="kanban-patient-line">
                        <?php if (!empty($row['mobile'])): ?>
                            <span><i class="bi bi-telephone"></i><?= e($row['mobile']) ?></span>
                        <?php endif; ?>
                        <?php if ($demographics): ?>
                            <span><i class="bi bi-info-circle"></i><?= e(implode(' · ', $demographics)) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="kanban-case <?= !empty($bookingInfo['due']) ? 'kanban-case-new' : 'kanban-case-active' ?>" title="<?= e($bookingInfo['message'] ?? '') ?>">
                        <i class="bi <?= !empty($bookingInfo['due']) ? 'bi-bookmark-plus' : 'bi-bookmark-check' ?>"></i>
                        <?= e($bookingInfo['label'] ?? '') ?>
                    </div>

                    <?php if ($visitReason !== ''): ?>
                        <div class="kanban-tags">
                            <span class="kanban-tag kanban-tag-reason"><i class="bi bi-chat-left-text me-1"></i><?= e($visitReason) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($allergyText !== ''): ?>
                        <div class="kanban-allergy" title="<?= e($allergyText) ?>">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Allergy: <?= e(strlen($allergyText) > 42 ? substr($allergyText, 0, 42) . '…' : $allergyText) ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2 gap-2 flex-wrap">
                        <?php if (can('visits.add') && in_array($status, ['waiting', 'checked_in', 'confirmed', 'with_doctor'], true)): ?>
                            <form method="post" action="<?= app_url('visits/start/' . $row['id']) ?>" class="ajax-form d-inline" data-reload="1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-primary btn-sm"><i class="bi bi-play-fill me-1"></i>Start Visit</button>
                            </form>
                        <?php elseif (can('billing.add') && $status === 'completed'): ?>
                            <?php
                            $billQuery = http_build_query(array_filter([
                                'patient_id' => $row['patient_id'] ?? null,
                                'doctor_id' => $row['doctor_id'] ?? null,
                                'treatment_master_id' => $row['treatment_master_id'] ?? null,
                            ]));
                            ?>
                            <a href="<?= app_url('billing/create?' . $billQuery) ?>" class="btn btn-xs btn-success btn-sm">
                                <i class="bi bi-cash-coin me-1"></i>Create Bill / Pay
                            </a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <span class="text-muted small"><i class="bi bi-grip-vertical"></i> Drag to move</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let draggedCard = null;

    function initCardDrag(card) {
        card.addEventListener('dragstart', function (e) {
            draggedCard = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        });

        card.addEventListener('dragend', function () {
            this.classList.remove('dragging');
            document.querySelectorAll('.kanban-column-body').forEach(col => col.classList.remove('drag-over'));
            draggedCard = null;
        });
    }

    document.querySelectorAll('.kanban-card').forEach(initCardDrag);

    document.querySelectorAll('.kanban-column-body').forEach(zone => {
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drag-over');
        });

        zone.addEventListener('dragenter', function (e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function (e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over');
            }
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            if (!draggedCard) return;

            const appointmentId = draggedCard.dataset.id;
            const currentStatus = draggedCard.dataset.status;
            const targetStatus = this.dataset.status;

            if (currentStatus === targetStatus) {
                return;
            }

            const targetZone = this;
            const cardEl = draggedCard;

            RootsApp.post('<?= app_url('appointments') ?>/' + appointmentId + '/status', { status: targetStatus })
                .done(function (res) {
                    toastr.success(res.message || 'Status updated to ' + targetStatus.replace(/_/g, ' '));
                    cardEl.dataset.status = targetStatus;
                    const emptyMsg = targetZone.querySelector('.kanban-empty');
                    if (emptyMsg) {
                        emptyMsg.remove();
                    }
                    targetZone.appendChild(cardEl);
                    updateCounters();
                })
                .fail(function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update status.');
                });
        });
    });

    function updateCounters() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const count = col.querySelectorAll('.kanban-card').length;
            const badge = col.querySelector('.kanban-count-badge');
            if (badge) badge.textContent = count;
        });
    }

    const highlightedCard = document.querySelector('.highlighted-card');
    if (highlightedCard) {
        setTimeout(function() {
            highlightedCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }, 300);
    }
});
</script>
<?php endif; ?>
