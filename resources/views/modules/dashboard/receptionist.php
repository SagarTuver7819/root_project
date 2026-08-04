<?php
$actions = '<a href="' . app_url('patients/create') . '" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add Patient</a>'
    . '<a href="' . app_url('queue') . '" class="btn btn-light"><i class="bi bi-people me-1"></i>Queue</a>'
    . '<a href="' . app_url('calendar') . '" class="btn btn-light"><i class="bi bi-calendar3 me-1"></i>Full Calendar</a>';
require __DIR__ . '/../../components/page-header.php';

$queueMeta = [
    'waiting' => ['color' => '#F59E0B', 'icon' => 'bi-hourglass-split'],
    'checked_in' => ['color' => '#8B5CF6', 'icon' => 'bi-door-open'],
    'with_doctor' => ['color' => '#6366F1', 'icon' => 'bi-person-video2'],
    'scheduled' => ['color' => '#3B82F6', 'icon' => 'bi-calendar2'],
    'completed' => ['color' => '#22C55E', 'icon' => 'bi-check-lg'],
];

$waitingNow = (int) (($queue['waiting'] ?? 0) + ($queue['checked_in'] ?? 0));
$withDoctor = (int) ($queue['with_doctor'] ?? 0);
$doneToday = (int) ($queue['completed'] ?? 0);
$doctors = $doctors ?? [];
?>

<div class="fd-dash">
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="fd-hero card content-card h-100">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3">
                    <div>
                        <div class="fd-hero-eyebrow">Front Desk</div>
                        <h2 class="fd-hero-title mb-1">This week’s appointments</h2>
                        <p class="text-muted mb-0 small">Week calendar below · patient aave tyare Add Patient thi doctor pase moklo.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= app_url('patients/create') ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-1"></i>Add Patient
                        </a>
                        <a href="<?= app_url('queue') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-people me-1"></i>Queue
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row g-2 h-100">
                <div class="col-4 col-lg-12">
                    <div class="fd-kpi fd-kpi-amber h-100">
                        <span class="fd-kpi-label">Waiting</span>
                        <strong class="fd-kpi-value"><?= e((string) $waitingNow) ?></strong>
                    </div>
                </div>
                <div class="col-4 col-lg-12">
                    <div class="fd-kpi h-100">
                        <span class="fd-kpi-label">With Doctor</span>
                        <strong class="fd-kpi-value"><?= e((string) $withDoctor) ?></strong>
                    </div>
                </div>
                <div class="col-4 col-lg-12">
                    <div class="fd-kpi fd-kpi-green h-100">
                        <span class="fd-kpi-label">Completed</span>
                        <strong class="fd-kpi-value"><?= e((string) $doneToday) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card content-card fd-calendar-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h3 class="h5 mb-1">Week Calendar</h3>
                    <div class="text-muted small">Current week view · doctor color thi appointments</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= app_url('patients/create') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus me-1"></i>Add Patient
                    </a>
                    <a href="<?= app_url('calendar') ?>" class="btn btn-sm btn-outline-primary">Open Full Calendar</a>
                </div>
            </div>

            <?php if (!empty($doctors)): ?>
            <div class="calendar-legend mb-3 justify-content-start">
                <?php foreach ($doctors as $doc):
                    $color = doctor_calendar_color((int) $doc['id'], $doc);
                ?>
                    <span class="legend-pill">
                        <i style="background:<?= e($color) ?>"></i><?= e(doctor_label($doc['name'] ?? '')) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div id="dashboardCalendar" class="dashboard-calendar fd-week-calendar"></div>
            <div id="dashboardCalendarEmpty" class="text-muted small mt-2 d-none">Loading week calendar…</div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($queueMeta as $status => $meta): ?>
            <div class="col-6 col-md-4 col-xl">
                <a href="<?= e(app_url('queue')) ?>" class="fd-status-card text-decoration-none" style="--q-color: <?= e($meta['color']) ?>">
                    <div class="fd-status-top">
                        <span class="fd-status-icon"><i class="bi <?= e($meta['icon']) ?>"></i></span>
                        <strong class="fd-status-count"><?= e((string) ($queue[$status] ?? 0)) ?></strong>
                    </div>
                    <div class="fd-status-label"><?= e(ucwords(str_replace('_', ' ', $status))) ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function bootFrontDeskCalendar(attempt) {
    const el = document.getElementById('dashboardCalendar');
    const empty = document.getElementById('dashboardCalendarEmpty');
    if (!el) return;

    if (!window.FullCalendar) {
      if ((attempt || 0) < 20) {
        if (empty) {
          empty.classList.remove('d-none');
          empty.textContent = 'Loading calendar…';
        }
        setTimeout(function () { bootFrontDeskCalendar((attempt || 0) + 1); }, 150);
      } else if (empty) {
        empty.classList.remove('d-none');
        empty.textContent = 'Calendar could not load. Open Full Calendar instead.';
      }
      return;
    }

    if (empty) empty.classList.add('d-none');

    const cal = new FullCalendar.Calendar(el, {
      initialView: 'timeGridWeek',
      firstDay: 1,
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay' },
      buttonText: { today: 'Today', week: 'Week', day: 'Day' },
      height: 620,
      expandRows: true,
      slotMinTime: '07:00:00',
      slotMaxTime: '22:00:00',
      slotDuration: '00:30:00',
      slotLabelInterval: '01:00:00',
      allDaySlot: false,
      nowIndicator: true,
      eventDisplay: 'block',
      dayMaxEvents: true,
      stickyHeaderDates: true,
      slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
      eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true },
      dayHeaderContent: function (arg) {
        const d = arg.date;
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return {
          html: '<div class="fc-day-head"><span class="fc-day-name">' + weekdays[d.getDay()] +
            '</span><span class="fc-day-date">' + dd + '-' + mm + '-' + yyyy + '</span></div>'
        };
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
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin'
        }).then(function (r) {
          if (!r.ok) throw new Error('Failed to load events');
          return r.json();
        }).then(success).catch(failure);
      },
      eventClick: function (info) {
        const id = info.event.id;
        if (id) {
          window.location.href = '<?= app_url('queue') ?>?id=' + encodeURIComponent(id);
        }
      }
    });
    cal.render();
  }

  bootFrontDeskCalendar(0);
});
</script>
