<?php
$actions = '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Book Appointment</a>'
    . '<a href="' . app_url('queue') . '" class="btn btn-light"><i class="bi bi-people me-1"></i>Queue</a>'
    . '<a href="' . app_url('patients/create') . '" class="btn btn-light"><i class="bi bi-person-plus me-1"></i>Add Patient</a>';
require __DIR__ . '/../../components/page-header.php';

$queueMeta = [
    'scheduled' => ['color' => '#3B82F6', 'icon' => 'bi-calendar2', 'tone' => 'blue'],
    'confirmed' => ['color' => '#0EA5E9', 'icon' => 'bi-check2-circle', 'tone' => 'cyan'],
    'waiting' => ['color' => '#F59E0B', 'icon' => 'bi-hourglass-split', 'tone' => 'amber'],
    'checked_in' => ['color' => '#8B5CF6', 'icon' => 'bi-door-open', 'tone' => 'violet'],
    'with_doctor' => ['color' => '#6366F1', 'icon' => 'bi-person-video2', 'tone' => 'indigo'],
    'completed' => ['color' => '#22C55E', 'icon' => 'bi-check-lg', 'tone' => 'green'],
    'cancelled' => ['color' => '#EF4444', 'icon' => 'bi-x-circle', 'tone' => 'rose'],
    'no_show' => ['color' => '#94A3B8', 'icon' => 'bi-person-x', 'tone' => 'slate'],
];

$activeQueue = (int) (($queue['waiting'] ?? 0) + ($queue['checked_in'] ?? 0) + ($queue['with_doctor'] ?? 0) + ($queue['confirmed'] ?? 0) + ($queue['scheduled'] ?? 0));
$doneToday = (int) ($queue['completed'] ?? 0);
?>

<div class="fd-dash">
    <div class="fd-hero card content-card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-lg-6">
                    <div class="fd-hero-eyebrow">Front Desk</div>
                    <h2 class="fd-hero-title mb-1">Today’s desk overview</h2>
                    <p class="text-muted mb-0 small">Queue status, walk-ins, and this week’s appointments in one place.</p>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="fd-kpi">
                                <span class="fd-kpi-label">In flow</span>
                                <strong class="fd-kpi-value"><?= e((string) $activeQueue) ?></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="fd-kpi fd-kpi-green">
                                <span class="fd-kpi-label">Completed</span>
                                <strong class="fd-kpi-value"><?= e((string) $doneToday) ?></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="fd-kpi fd-kpi-amber">
                                <span class="fd-kpi-label">Waiting</span>
                                <strong class="fd-kpi-value"><?= e((string) ($queue['waiting'] ?? 0)) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
    <?php foreach (($queue ?? []) as $status => $count):
        $meta = $queueMeta[$status] ?? ['color' => '#94A3B8', 'icon' => 'bi-circle'];
        $href = app_url('queue');
    ?>
        <div class="col-6 col-md-4 col-xl-3">
            <a href="<?= e($href) ?>" class="fd-status-card text-decoration-none" style="--q-color: <?= e($meta['color']) ?>">
                <div class="fd-status-top">
                    <span class="fd-status-icon"><i class="bi <?= e($meta['icon']) ?>"></i></span>
                    <strong class="fd-status-count"><?= e((string) $count) ?></strong>
                </div>
                <div class="fd-status-label"><?= e(ucwords(str_replace('_', ' ', $status))) ?></div>
            </a>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="card content-card fd-calendar-card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h3 class="h5 mb-1">Week Calendar</h3>
                    <div class="text-muted small">Default week view · switch to day anytime</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= app_url('queue') ?>" class="btn btn-sm btn-light"><i class="bi bi-people me-1"></i>Queue</a>
                    <a href="<?= app_url('calendar') ?>" class="btn btn-sm btn-outline-primary">Full Calendar</a>
                </div>
            </div>
            <div class="calendar-legend mb-3 justify-content-start">
                <span class="legend-pill"><i style="background:#3B82F6"></i>Scheduled</span>
                <span class="legend-pill"><i style="background:#0EA5E9"></i>Confirmed</span>
                <span class="legend-pill"><i style="background:#F59E0B"></i>Waiting</span>
                <span class="legend-pill"><i style="background:#8B5CF6"></i>Checked In</span>
                <span class="legend-pill"><i style="background:#6366F1"></i>With Doctor</span>
                <span class="legend-pill"><i style="background:#22C55E"></i>Completed</span>
                <span class="legend-pill legend-remark"><i style="background:#DC2626"></i>Doctor Remark</span>
            </div>
            <div id="dashboardCalendar" class="dashboard-calendar"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!window.FullCalendar) return;
  const cal = new FullCalendar.Calendar(document.getElementById('dashboardCalendar'), {
    initialView: 'timeGridWeek',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay' },
    buttonText: { today: 'Today', week: 'Week', day: 'Day' },
    height: 560,
    expandRows: true,
    slotMinTime: '08:00:00',
    slotMaxTime: '21:00:00',
    slotDuration: '00:30:00',
    slotLabelInterval: '01:00:00',
    allDaySlot: false,
    nowIndicator: true,
    eventDisplay: 'block',
    dayMaxEvents: true,
    slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
    eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
    dayHeaderContent: function (arg) {
      const d = arg.date;
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const yyyy = d.getFullYear();
      const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      return { html: '<div class="fc-day-head"><span class="fc-day-name">' + weekdays[d.getDay()] + '</span><span class="fc-day-date">' + dd + '-' + mm + '-' + yyyy + '</span></div>' };
    },
    datesSet: function (info) {
      const titleEl = document.querySelector('#dashboardCalendar .fc-toolbar-title');
      if (!titleEl) return;
      const d = info.start;
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const yyyy = d.getFullYear();
      if (info.view.type === 'timeGridDay') {
        titleEl.textContent = dd + '-' + mm + '-' + yyyy;
      } else {
        const end = new Date(info.end.getTime() - 1);
        const ed = String(end.getDate()).padStart(2, '0');
        const em = String(end.getMonth() + 1).padStart(2, '0');
        titleEl.textContent = dd + '-' + mm + '-' + yyyy + ' – ' + ed + '-' + em + '-' + end.getFullYear();
      }
    },
    events: function (info, success, failure) {
      const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
      fetch('<?= app_url('calendar/events') ?>?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).then(r => r.json()).then(success).catch(failure);
    },
    eventClick: function (info) {
      const id = info.event.id;
      if (id) {
        window.location.href = '<?= app_url('queue') ?>?id=' + encodeURIComponent(id);
      }
    }
  });
  cal.render();
});
</script>
