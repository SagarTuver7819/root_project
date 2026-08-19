<?php
$actions = '<a href="' . app_url('patients/create') . '" class="btn btn-light"><i class="bi bi-person-plus me-1"></i>Add Patient</a>'
    . '<a href="' . app_url('queue') . '" class="btn btn-light"><i class="bi bi-people me-1"></i>Queue</a>'
    . '<button type="button" class="btn btn-primary btn-book-slot" id="btnOpenBook"><i class="bi bi-calendar-plus me-1"></i>Book Treatment Slot</button>';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="calendar-shell">
    <div class="calendar-toolbar card content-card mb-3">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Doctor-wise filter</label>
                <?php $lockedDoctorId = $lockedDoctorId ?? null; ?>
                <select id="doctorFilter" class="form-select" <?= $lockedDoctorId ? 'disabled' : '' ?>>
                    <?php if (!$lockedDoctorId): ?>
                        <option value="">All Doctors</option>
                    <?php endif; ?>
                    <?php foreach (($doctors ?? []) as $d): ?>
                        <option value="<?= e($d['id']) ?>" <?= (string) $lockedDoctorId === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($lockedDoctorId): ?>
                    <input type="hidden" id="lockedDoctorId" value="<?= e((string) $lockedDoctorId) ?>">
                <?php endif; ?>
            </div>
                <div class="col-md-8">
                    <label class="form-label d-block">Doctor colors (like Google Calendar)</label>
                    <div class="calendar-legend">
                        <?php foreach (($doctors ?? []) as $d): ?>
                            <span class="legend-pill">
                                <i style="background:<?= e(doctor_calendar_color((int) $d['id'], $d)) ?>"></i><?= e(doctor_label($d['name'] ?? '')) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="text-muted small mt-2">Recent week view · click appointment → open treatment / visit</div>
        </div>
    </div>

    <div class="card content-card calendar-board">
        <div class="card-body p-2 p-md-3">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content book-slot-modal ajax-form" method="post" action="<?= app_url('appointments') ?>" data-reload="1">
            <div class="modal-header book-slot-header">
                <div class="book-slot-header-text">
                    <span class="book-slot-eyebrow"><i class="bi bi-calendar2-check me-1"></i>Front Desk</span>
                    <h5 class="modal-title" id="appointmentModalTitle">Book Appointment</h5>
                    <p class="book-slot-sub mb-0">Select patient, doctor and time slot</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body book-slot-body">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="book-slot-section-label">Booking type</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Entry Type</label>
                        <select class="form-select" name="entry_type" id="entryType" required>
                            <option value="appointment">Patient Appointment</option>
                            <option value="doctor_remark">Doctor Remark</option>
                        </select>
                    </div>
                    <div class="col-md-6 appointment-only">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="scheduled">Scheduled</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="waiting">Waiting</option>
                        </select>
                    </div>

                    <div class="col-12 pt-1">
                        <div class="book-slot-section-label">Patient &amp; doctor</div>
                    </div>
                    <div class="col-md-8 appointment-only" id="patientField">
                        <label class="form-label">Patient</label>
                        <div id="patientSelectWrap">
                            <select id="patientSelect" name="patient_id" style="width:100%">
                                <option value=""></option>
                                <?php foreach (($patients ?? []) as $p): ?>
                                    <option value="<?= e((string) $p['id']) ?>"><?= e(($p['patient_code'] ?? '') . ' - ' . ($p['name'] ?? '') . ' (' . ($p['mobile'] ?? '') . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a class="book-slot-quick-link" data-bs-toggle="collapse" href="#quickPatient"><i class="bi bi-person-plus me-1"></i>Quick Add Patient</a>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Doctor</label>
                        <select class="form-select" name="doctor_id" id="modalDoctor" required <?= !empty($lockedDoctorId) ? 'disabled' : '' ?>>
                            <?php foreach (($doctors ?? []) as $d): ?>
                                <option value="<?= e($d['id']) ?>" <?= !empty($lockedDoctorId) && (string) $lockedDoctorId === (string) $d['id'] ? 'selected' : '' ?>><?= e(doctor_label($d['name'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($lockedDoctorId)): ?>
                            <input type="hidden" name="doctor_id" value="<?= e((string) $lockedDoctorId) ?>">
                        <?php endif; ?>
                    </div>
                    <div id="quickPatient" class="collapse col-12 appointment-only">
                        <div class="book-slot-quick-box">
                            <div class="fw-semibold mb-2"><i class="bi bi-person-plus me-1"></i>Quick Add Patient</div>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label">OPD Number <span class="required-star">*</span></label>
                                    <input class="form-control" id="quickOpd" value="<?= e($suggestedOpdNumber ?? '') ?>" placeholder="Auto generated">
                                    <div class="form-text">Auto generated — editable</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Name <span class="required-star">*</span></label>
                                    <input class="form-control" id="quickName" placeholder="Patient name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Mobile <span class="required-star">*</span></label>
                                    <input class="form-control" id="quickMobile" placeholder="Mobile">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="quickGender">
                                        <option value="">Select</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Registration Date</label>
                                    <input class="form-control" type="date" id="quickRegDate" value="<?= e(date('Y-m-d')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DOB</label>
                                    <input class="form-control" type="date" id="quickDob">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Age</label>
                                    <input class="form-control" type="number" id="quickAge" placeholder="Age" min="0" max="150">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reference Doctor</label>
                                    <select class="form-select" id="quickRefDoctor">
                                        <option value="">None</option>
                                        <?php foreach (($referenceDoctors ?? []) as $doc): ?>
                                            <option value="<?= e($doc['id']) ?>"><?= e($doc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button" class="btn btn-outline-primary px-4" id="btnQuickPatient">Add Patient</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 pt-1">
                        <div class="book-slot-section-label">Date &amp; time</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input id="appointmentDate" class="form-control" type="date" name="appointment_date" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Time</label>
                        <input class="form-control" type="time" name="start_time" id="startTime" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Time</label>
                        <input class="form-control" type="time" name="end_time" id="endTime" required>
                    </div>

                    <div class="col-12 pt-1 appointment-only">
                        <div class="book-slot-section-label">Clinical details</div>
                    </div>
                    <div class="col-md-6 appointment-only">
                        <label class="form-label">Treatment</label>
                        <select class="form-select" name="treatment_master_id">
                            <option value="">None</option>
                            <?php foreach (($treatments ?? []) as $t): ?>
                                <option value="<?= e($t['id']) ?>"><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" id="notesLabel">Visit Reason / Notes</label>
                        <textarea class="form-control" name="notes" id="notesField" rows="2" placeholder="Optional notes for doctor / reception"></textarea>
                        <input type="hidden" name="visit_reason" id="visitReasonField" value="">
                    </div>
                </div>
            </div>
            <div class="modal-footer book-slot-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" id="appointmentSubmitBtn"><i class="bi bi-check2-circle me-1"></i>Book Appointment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('appointmentModal');
  const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  const patientData = <?= json_encode(array_map(static function ($p) {
      return [
          'id' => (string) $p['id'],
          'text' => ($p['patient_code'] ?? '') . ' - ' . ($p['name'] ?? '') . ' (' . ($p['mobile'] ?? '') . ')',
      ];
  }, $patients ?? []), JSON_UNESCAPED_UNICODE) ?>;

  function destroyPatientSelect() {
    const $el = jQuery('#patientSelect');
    if (!$el.length) return;
    if ($el.hasClass('select2-hidden-accessible')) {
      $el.select2('destroy');
    }
    jQuery('#patientSelectWrap .select2-container').remove();
    $el.removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
    $el.next('.select2-container').remove();
  }

  function initPatientSelect(selectedId) {
    if (!window.jQuery || !jQuery.fn.select2) return;
    const $el = jQuery('#patientSelect');
    destroyPatientSelect();
    $el.empty().append(new Option('', '', false, false));
    patientData.forEach(function (p) {
      $el.append(new Option(p.text, p.id, false, false));
    });
    $el.select2({
      theme: 'bootstrap-5',
      dropdownParent: jQuery('#appointmentModal'),
      width: '100%',
      allowClear: true,
      placeholder: 'Search patient name / mobile / code'
    });
    if (selectedId) {
      $el.val(String(selectedId)).trigger('change');
    } else {
      $el.val(null).trigger('change');
    }
  }

  function syncEntryType() {
    const isRemark = document.getElementById('entryType').value === 'doctor_remark';
    document.querySelectorAll('.appointment-only').forEach(function (el) {
      el.classList.toggle('d-none', isRemark);
    });
    const patientSelect = document.getElementById('patientSelect');
    if (patientSelect) {
      patientSelect.required = !isRemark;
      if (isRemark) {
        jQuery(patientSelect).val(null).trigger('change');
      }
    }
    document.getElementById('appointmentModalTitle').textContent = isRemark ? 'Add Doctor Remark' : 'Book Appointment';
    document.getElementById('appointmentSubmitBtn').textContent = isRemark ? 'Save Remark' : 'Book Appointment';
    document.getElementById('notesLabel').textContent = isRemark ? 'Doctor Remark *' : 'Visit Reason / Notes';
    document.getElementById('notesField').required = isRemark;
    document.getElementById('notesField').placeholder = isRemark ? 'e.g. On leave / Call not available / Staff training' : 'Optional notes';
  }

  document.getElementById('entryType')?.addEventListener('change', syncEntryType);
  document.getElementById('notesField')?.addEventListener('input', function () {
    document.getElementById('visitReasonField').value = this.value;
  });

  const qs = new URLSearchParams(window.location.search);
  const prefillPatientId = qs.get('patient_id') || '';
  const prefillDoctorId = qs.get('doctor_id') || '';
  const prefillReason = qs.get('reason') || '';
  const prefillPatientText = qs.get('patient_text') || '';
  if (prefillPatientId && !patientData.some(function (p) { return String(p.id) === String(prefillPatientId); })) {
    patientData.unshift({ id: String(prefillPatientId), text: prefillPatientText || ('Patient #' + prefillPatientId) });
  }

  jQuery(function () {
    setTimeout(function () {
      initPatientSelect(prefillPatientId || null);
      syncEntryType();
      const ret = qs.get('return') || '';
      if (/^patients\/\d+/.test(ret)) {
        const bookForm = document.querySelector('.book-slot-modal');
        if (bookForm) {
          bookForm.removeAttribute('data-reload');
          bookForm.setAttribute('data-redirect', <?= json_encode(rtrim(app_url(), '/')) ?> + '/' + ret);
          jQuery(bookForm).removeData('reload').data('redirect', <?= json_encode(rtrim(app_url(), '/')) ?> + '/' + ret);
        }
      }
      if (qs.get('open_book') === '1') {
        openBookModal('<?= date('Y-m-d') ?>', '10:00', {
          patientId: prefillPatientId,
          doctorId: prefillDoctorId,
          reason: prefillReason
        });
      }
    }, 0);
  });

  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      jQuery('#patientSelect').val(null).trigger('change');
      document.getElementById('entryType').value = 'appointment';
      document.getElementById('notesField').value = '';
      syncEntryType();
    });
  }

  document.getElementById('btnQuickPatient')?.addEventListener('click', function () {
    const name = document.getElementById('quickName').value.trim();
    const mobile = document.getElementById('quickMobile').value.trim();
    const patientCode = document.getElementById('quickOpd').value.trim();
    if (!patientCode || !name || !mobile) {
      toastr.warning('OPD Number, Name and Mobile are required.');
      return;
    }
    RootsApp.post('<?= app_url('patients') ?>', {
      patient_code: patientCode,
      name: name,
      mobile: mobile,
      gender: document.getElementById('quickGender').value,
      registration_date: document.getElementById('quickRegDate').value || '<?= date('Y-m-d') ?>',
      dob: document.getElementById('quickDob').value || '',
      age: document.getElementById('quickAge').value || '',
      reference_doctor_id: document.getElementById('quickRefDoctor').value || ''
    }).done(function (res) {
      toastr.success(res.message || 'Patient created successfully.');
      const p = res.data || {};
      const id = String(p.id || '');
      const text = p.text || ((p.patient_code || '') + ' - ' + (p.name || '') + ' (' + (p.mobile || '') + ')');
      if (id) {
        patientData.unshift({ id: id, text: text });
        initPatientSelect(id);
      }
      document.getElementById('quickName').value = '';
      document.getElementById('quickMobile').value = '';
      document.getElementById('quickGender').value = '';
      document.getElementById('quickDob').value = '';
      document.getElementById('quickAge').value = '';
      document.getElementById('quickRefDoctor').value = '';
      document.getElementById('quickRegDate').value = '<?= date('Y-m-d') ?>';
      const used = (p.patient_code || patientCode || '').match(/^PAT(\d+)$/i);
      if (used) {
        document.getElementById('quickOpd').value = 'PAT' + String(parseInt(used[1], 10) + 1).padStart(5, '0');
      }
      const collapse = bootstrap.Collapse.getOrCreateInstance(document.getElementById('quickPatient'), { toggle: false });
      collapse.hide();
    }).fail(function (xhr) {
      toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to add patient.');
    });
  });

  document.getElementById('quickDob')?.addEventListener('change', function () {
    if (!this.value) return;
    const birth = new Date(this.value);
    if (Number.isNaN(birth.getTime())) return;
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age -= 1;
    if (age >= 0 && age <= 150) {
      document.getElementById('quickAge').value = String(age);
    }
  });

  function pad2(n) { return String(n).padStart(2, '0'); }
  function fmtDMY(d) {
    return pad2(d.getDate()) + '-' + pad2(d.getMonth() + 1) + '-' + d.getFullYear();
  }

  function openBookModal(dateStr, timeStr, prefill) {
    document.getElementById('appointmentDate').value = (dateStr || '<?= date('Y-m-d') ?>').substring(0, 10);
    if (timeStr) {
      document.getElementById('startTime').value = timeStr;
      const [h, m] = timeStr.split(':').map(Number);
      const end = new Date(2000, 0, 1, h, m + 30);
      document.getElementById('endTime').value = pad2(end.getHours()) + ':' + pad2(end.getMinutes());
    } else {
      document.getElementById('startTime').value = '10:00';
      document.getElementById('endTime').value = '10:30';
    }
    const doctorId = (prefill && prefill.doctorId) || document.getElementById('doctorFilter').value;
    if (doctorId && document.getElementById('modalDoctor')) {
      document.getElementById('modalDoctor').value = doctorId;
    }
    if (prefill && prefill.reason) {
      document.getElementById('notesField').value = prefill.reason;
      document.getElementById('visitReasonField').value = prefill.reason;
    }
    if (prefill && prefill.patientId) {
      initPatientSelect(prefill.patientId);
    }
    modal && modal.show();
  }

  document.getElementById('btnOpenBook')?.addEventListener('click', function () {
    openBookModal('<?= date('Y-m-d') ?>', '10:00');
  });

  if (window.FullCalendar) {
    const cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
      initialView: 'timeGridWeek',
      firstDay: 1,
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'timeGridWeek,timeGridDay,dayGridMonth'
      },
      buttonText: {
        today: 'Today',
        month: 'Month',
        week: 'Week',
        day: 'Day'
      },
      height: 'auto',
      expandRows: true,
      slotMinTime: '07:00:00',
      slotMaxTime: '22:00:00',
      slotDuration: '00:30:00',
      slotLabelInterval: '01:00:00',
      slotLabelFormat: {
        hour: 'numeric',
        minute: '2-digit',
        omitZeroMinute: false,
        meridiem: 'short',
        hour12: true
      },
      eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short',
        hour12: true
      },
      dayHeaderContent: function (arg) {
        const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return {
          html: '<div class="fc-day-head"><span class="fc-day-name">' + weekdays[arg.date.getDay()] + '</span><span class="fc-day-date">' + fmtDMY(arg.date) + '</span></div>'
        };
      },
      datesSet: function (info) {
        const titleEl = document.querySelector('#calendar .fc-toolbar-title');
        if (!titleEl) return;
        const start = info.start;
        const end = new Date(info.end.getTime() - 1);
        if (info.view.type === 'timeGridDay') {
          titleEl.textContent = fmtDMY(start);
        } else if (info.view.type === 'dayGridMonth') {
          const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
          titleEl.textContent = months[info.view.currentStart.getMonth()] + ' ' + info.view.currentStart.getFullYear();
        } else {
          titleEl.textContent = fmtDMY(start) + ' – ' + fmtDMY(end);
        }
      },
      allDaySlot: false,
      nowIndicator: true,
      selectable: true,
      selectMirror: true,
      eventDisplay: 'block',
      dayMaxEvents: true,
      stickyHeaderDates: true,
      events: function (info, success, failure) {
        const params = new URLSearchParams({
          start: info.startStr,
          end: info.endStr,
          doctor_id: (document.getElementById('lockedDoctorId')?.value || document.getElementById('doctorFilter').value || '')
        });
        fetch('<?= app_url('calendar/events') ?>?' + params.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
          .then(r => r.json())
          .then(success)
          .catch(failure);
      },
      dateClick: function (info) {
        let timeStr = '';
        if (info.dateStr.includes('T')) {
          timeStr = info.dateStr.substring(11, 16);
        }
        openBookModal(info.dateStr, timeStr);
      },
      eventClick: function (info) {
        const props = info.event.extendedProps || {};
        const entryType = props.entry_type || 'appointment';
        const patientId = props.patient_id || '';
        const doctorId = props.doctor_id || '';
        const id = info.event.id || '';

        if (entryType === 'doctor_remark') {
          return;
        }

        // Walk-in / waiting patients → open doctor visit/clinical
        if (entryType === 'walk_in' || ['waiting', 'checked_in', 'with_doctor'].includes(props.status || '')) {
          if (id) {
            window.location.href = '<?= app_url('visits/open') ?>/' + encodeURIComponent(id);
            return;
          }
        }

        // Treatment appointment → go to treatment plan (then history/billing from patient)
        if (patientId) {
          const qs = new URLSearchParams({
            patient_id: String(patientId),
            doctor_id: String(doctorId || ''),
            appointment_id: String(id || ''),
            from_calendar: '1'
          });
          <?php if (can('treatments.add')): ?>
          window.location.href = '<?= app_url('treatment-plans/create') ?>?' + qs.toString();
          <?php else: ?>
          window.location.href = '<?= app_url('patients') ?>/' + encodeURIComponent(patientId) + '?tab=plan';
          <?php endif; ?>
          return;
        }

        if (id) {
          window.location.href = '<?= app_url('queue') ?>?id=' + encodeURIComponent(id);
        }
      }
    });
    cal.render();
    window.rootsCalendar = cal;
    document.getElementById('doctorFilter').onchange = () => cal.refetchEvents();
  }
});
</script>
