<?php
$actions = '<a href="' . app_url('patients/create') . '" class="btn btn-light"><i class="bi bi-person-plus me-1"></i>Add Patient</a>'
    . '<a href="' . app_url('calendar') . '" class="btn btn-primary"><i class="bi bi-calendar3 me-1"></i>Week Calendar</a>'
    . '<a href="' . app_url('billing') . '" class="btn btn-light"><i class="bi bi-receipt me-1"></i>Billing</a>';
require __DIR__ . '/../../components/page-header.php';

$statCards = [
    ['Patients', 'patients', 'bi-people', 'cyan', app_url('patients')],
    ['Doctors', 'doctors', 'bi-heart-pulse', 'teal', app_url('doctors')],
    ['Appointments Today', 'appointments_today', 'bi-calendar-check', 'blue', app_url('calendar')],
    ['Pending Payments', 'pending_payments', 'bi-exclamation-circle', 'amber', app_url('outstanding'), true],
    ['Revenue Today', 'revenue_today', 'bi-cash-coin', 'green', app_url('payments'), true],
    ['Active Treatments', 'treatments_active', 'bi-activity', 'sky', app_url('treatment-plans')],
];

$queueMeta = [
    'waiting' => ['color' => '#F59E0B', 'icon' => 'bi-hourglass-split'],
    'with_doctor' => ['color' => '#6366F1', 'icon' => 'bi-person-video2'],
    'scheduled' => ['color' => '#3B82F6', 'icon' => 'bi-calendar2'],
    'completed' => ['color' => '#22C55E', 'icon' => 'bi-check-lg'],
];
$doctors = $doctors ?? [];
?>
<div class="row g-3 mb-4">
<?php foreach ($statCards as $card):
    [$label, $key, $icon, $tone, $href] = $card;
    $isMoney = !empty($card[5]);
    $value = $isMoney ? format_money($stats[$key] ?? 0) : (string) ($stats[$key] ?? 0);
?>
    <div class="col-sm-6 col-xl-4">
        <a href="<?= e($href) ?>" class="dash-stat dash-stat-<?= e($tone) ?> text-decoration-none">
            <div class="dash-stat-icon"><i class="bi <?= e($icon) ?>"></i></div>
            <div class="dash-stat-body">
                <div class="dash-stat-label"><?= e($label) ?></div>
                <div class="dash-stat-value"><?= e($value) ?></div>
            </div>
        </a>
    </div>
<?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h3 class="h5 mb-1">Doctor-wise Week Calendar</h3>
                        <div class="text-muted small">Recent week · colors = doctors (Google Calendar style)</div>
                    </div>
                    <a href="<?= app_url('calendar') ?>" class="btn btn-sm btn-outline-primary">Full Calendar</a>
                </div>
                <div class="calendar-legend mb-3 justify-content-start">
                    <?php foreach ($doctors as $doc): ?>
                        <span class="legend-pill">
                            <i style="background:<?= e(doctor_calendar_color((int) $doc['id'], $doc)) ?>"></i><?= e(doctor_label($doc['name'] ?? '')) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div id="dashboardCalendar" class="dashboard-calendar"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card content-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Today Queue</h3>
                    <a href="<?= app_url('queue') ?>" class="small">Open Queue</a>
                </div>
                <div class="row g-2">
                    <?php foreach ($queueMeta as $status => $meta): ?>
                        <div class="col-6">
                            <div class="dash-queue-card" style="--q-color: <?= e($meta['color']) ?>">
                                <div class="dash-queue-top">
                                    <span class="dash-queue-icon"><i class="bi <?= e($meta['icon']) ?>"></i></span>
                                    <strong class="dash-queue-count"><?= e((string) ($queue[$status] ?? 0)) ?></strong>
                                </div>
                                <div class="dash-queue-label"><?= e(ucwords(str_replace('_', ' ', $status))) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card content-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Payments Snapshot</h3>
                    <a href="<?= app_url('payments') ?>" class="small">View all</a>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="dash-pay-box dash-pay-pending">
                            <div class="small">Pending</div>
                            <div class="fw-bold"><?= e(format_money($stats['pending_payments'] ?? 0)) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-pay-box dash-pay-revenue">
                            <div class="small">Revenue Today</div>
                            <div class="fw-bold"><?= e(format_money($stats['revenue_today'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($recentPayments)): ?>
                    <?php foreach ($recentPayments as $pay): ?>
                        <div class="dash-appt-item">
                            <div class="dash-appt-dot" style="background:#22C55E"></div>
                            <div>
                                <div class="fw-semibold"><?= e($pay['patient_name'] ?? '') ?></div>
                                <div class="small text-muted"><?= e(format_date($pay['payment_date'] ?? null)) ?> · <?= e(format_money($pay['amount'] ?? 0)) ?> · <?= e($pay['payment_mode'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">No payments recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!window.FullCalendar) return;
  const cal = new FullCalendar.Calendar(document.getElementById('dashboardCalendar'), {
    initialView: 'timeGridWeek',
    firstDay: 1,
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridDay,timeGridWeek' },
    buttonText: { today: 'Today', day: 'Day', week: 'Week' },
    height: 560,
    expandRows: true,
    slotMinTime: '07:00:00',
    slotMaxTime: '22:00:00',
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
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
      }).then(r => r.json()).then(success).catch(failure);
    },
    eventClick: function (info) {
      const props = info.event.extendedProps || {};
      const patientId = props.patient_id || '';
      const id = info.event.id || '';
      if ((props.entry_type || '') === 'walk_in' && id) {
        window.location.href = '<?= app_url('visits/open') ?>/' + encodeURIComponent(id);
        return;
      }
      if (patientId) {
        window.location.href = '<?= app_url('patients') ?>/' + encodeURIComponent(patientId) + '?tab=treatments';
        return;
      }
      window.location.href = '<?= app_url('calendar') ?>';
    }
  });
  cal.render();
});
</script>
