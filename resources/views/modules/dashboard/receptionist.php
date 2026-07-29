<?php
$actions = '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Book Appointment</a>'
    . '<a href="' . app_url('queue') . '" class="btn btn-light"><i class="bi bi-people me-1"></i>Queue</a>';
require __DIR__ . '/../../components/page-header.php';

$queueMeta = [
    'scheduled' => ['color' => '#3B82F6', 'icon' => 'bi-calendar2'],
    'confirmed' => ['color' => '#0EA5E9', 'icon' => 'bi-check2-circle'],
    'waiting' => ['color' => '#F59E0B', 'icon' => 'bi-hourglass-split'],
    'checked_in' => ['color' => '#8B5CF6', 'icon' => 'bi-door-open'],
    'with_doctor' => ['color' => '#6366F1', 'icon' => 'bi-person-video2'],
    'completed' => ['color' => '#22C55E', 'icon' => 'bi-check-lg'],
    'cancelled' => ['color' => '#EF4444', 'icon' => 'bi-x-circle'],
    'no_show' => ['color' => '#94A3B8', 'icon' => 'bi-person-x'],
];
?>
<div class="row g-3 mb-4">
<?php foreach (($queue ?? []) as $status => $count):
    $meta = $queueMeta[$status] ?? ['color' => '#94A3B8', 'icon' => 'bi-circle'];
?>
    <div class="col-sm-6 col-lg-3">
        <div class="dash-queue-card" style="--q-color: <?= e($meta['color']) ?>">
            <div class="dash-queue-top">
                <span class="dash-queue-icon"><i class="bi <?= e($meta['icon']) ?>"></i></span>
                <strong class="dash-queue-count"><?= e((string) $count) ?></strong>
            </div>
            <div class="dash-queue-label"><?= e(ucwords(str_replace('_', ' ', $status))) ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 mb-1">Today Calendar</h3>
                <div class="text-muted small">Status colors + doctor remarks</div>
            </div>
            <a href="<?= app_url('calendar') ?>" class="btn btn-sm btn-outline-primary">Open Calendar</a>
        </div>
        <div class="calendar-legend mb-3 justify-content-start">
            <span><i style="background:#3B82F6"></i>Scheduled</span>
            <span><i style="background:#F59E0B"></i>Waiting</span>
            <span><i style="background:#6366F1"></i>With Doctor</span>
            <span><i style="background:#DC2626"></i>Doctor Remark</span>
        </div>
        <div id="dashboardCalendar" class="dashboard-calendar"></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!window.FullCalendar) return;
  const cal = new FullCalendar.Calendar(document.getElementById('dashboardCalendar'), {
    initialView: 'timeGridDay',
    headerToolbar: { left: 'prev,next', center: 'title', right: '' },
    height: 480,
    slotMinTime: '08:00:00',
    slotMaxTime: '21:00:00',
    allDaySlot: false,
    nowIndicator: true,
    slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
    eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
    events: function (info, success, failure) {
      const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
      fetch('<?= app_url('calendar/events') ?>?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).then(r => r.json()).then(success).catch(failure);
    }
  });
  cal.render();
});
</script>
