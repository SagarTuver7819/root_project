<?php
$actions = '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar3 me-1"></i>Calendar</a>'
    . '<a href="' . app_url('appointments') . '" class="btn btn-light me-2"><i class="bi bi-list-ul me-1"></i>Appointments</a>';
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
.kanban-tag-walkin {
    background: #ecfeff;
    color: #0e7490;
    border: 1px solid #a5f3fc;
}
.kanban-tag-reason {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    text-transform: none;
    letter-spacing: 0;
    font-weight: 600;
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
</style>

<form method="get" action="<?= app_url('queue') ?>" class="card content-card mb-4 queue-filter-card">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Queue Date</label>
                <input class="form-control" type="date" name="date" value="<?= e($date ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-4">
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
                <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Filter</button>
            </div>
            <div class="col-md-3 d-flex justify-content-md-end align-items-end">
                <div class="queue-total-chip" title="Appointments currently in queue">
                    <div class="queue-total-meta">
                        <span class="queue-total-label">Total in Queue</span>
                        <span class="queue-total-hint">Active appointments today</span>
                    </div>
                    <span class="queue-total-count"><?= e((string) $totalInQueue) ?></span>
                </div>
            </div>
        </div>
    </div>
</form>

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
                $slotEnd = format_time($row['end_time'] ?? null);
                $visitReason = trim((string) ($row['visit_reason'] ?? ''));
                $bookingInfo = \App\Services\BookingService::statusForPatient(
                    !empty($row['patient_id']) ? (int) $row['patient_id'] : null,
                    $date ?? date('Y-m-d')
                );
            ?>
                <div class="kanban-card <?= $isMatch ? 'highlighted-card' : '' ?>"
                     draggable="true"
                     data-id="<?= e((string) $row['id']) ?>"
                     data-code="<?= e((string) $row['appointment_code']) ?>"
                     data-status="<?= e((string) $status) ?>"
                     id="card-apt-<?= e((string) $row['id']) ?>">
                    
                    <div class="kanban-card-top">
                        <a class="kanban-patient-name text-decoration-none" href="<?= app_url('appointments/' . $row['id'] . '/edit') ?>">
                            <?= e($row['patient_name'] ?? 'Walk-in Patient') ?>
                        </a>
                        <span class="kanban-time">
                            <?= e(format_time($row['start_time'] ?? null)) ?>
                            <?php if ($slotEnd && $slotEnd !== '-'): ?>
                                – <?= e($slotEnd) ?>
                            <?php endif; ?>
                        </span>
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

                    <?php if ($isWalkIn || $visitReason !== ''): ?>
                        <div class="kanban-tags">
                            <?php if ($isWalkIn): ?>
                                <span class="kanban-tag kanban-tag-walkin">Walk-in</span>
                            <?php endif; ?>
                            <?php if ($visitReason !== ''): ?>
                                <span class="kanban-tag kanban-tag-reason"><?= e($visitReason) ?></span>
                            <?php endif; ?>
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

    // Attach drag events to cards
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

    // Attach dropzone events to column bodies
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

            // Perform AJAX Status Update
            RootsApp.post('<?= app_url('appointments') ?>/' + appointmentId + '/status', { status: targetStatus })
                .done(function (res) {
                    toastr.success(res.message || 'Status updated to ' + targetStatus.replace(/_/g, ' '));
                    
                    // Update card status attribute
                    cardEl.dataset.status = targetStatus;
                    
                    // Remove empty placeholder if present
                    const emptyMsg = targetZone.querySelector('.kanban-empty');
                    if (emptyMsg) {
                        emptyMsg.remove();
                    }

                    // Append card to target column body
                    targetZone.appendChild(cardEl);

                    // Update column counters
                    updateCounters();
                })
                .fail(function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update status.');
                });
        });
    });

    function updateCounters() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const status = col.dataset.status;
            const count = col.querySelectorAll('.kanban-card').length;
            const badge = col.querySelector('.kanban-count-badge');
            if (badge) badge.textContent = count;
        });
    }

    // Scroll to highlighted card if passed in URL
    const highlightedCard = document.querySelector('.highlighted-card');
    if (highlightedCard) {
        setTimeout(function() {
            highlightedCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }, 300);
    }
});
</script>
