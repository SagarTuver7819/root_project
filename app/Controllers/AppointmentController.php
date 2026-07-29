<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;
use App\Services\AppointmentService;
use App\Services\BookingService;

class AppointmentController extends \App\Core\Controller
{
    use CrudControllerSupport;

    private AppointmentService $appointments;

    public function __construct()
    {
        $this->appointments = new AppointmentService();
    }

    public function index(Request $request): void
    {
        $this->view('modules/appointments/index', [
            'title' => 'Appointments',
            'pageTitle' => 'Appointments',
            'doctors' => $this->options('doctors', 'name', 'is_active = 1'),
            'patients' => $this->options('patients', 'name', 'is_active = 1'),
            'treatments' => $this->options('treatment_masters', 'name', 'is_active = 1'),
        ]);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'appointments a',
            'joins' => [
                'LEFT JOIN patients p ON p.id = a.patient_id',
                'INNER JOIN doctors d ON d.id = a.doctor_id',
                'LEFT JOIN treatment_masters tm ON tm.id = a.treatment_master_id',
            ],
            'columns' => ['a.id', 'a.appointment_code', 'a.appointment_date', 'a.start_time', 'a.end_time', 'a.patient_id', 'p.name AS patient_name', 'p.mobile', 'd.name AS doctor_name', 'tm.name AS treatment_name', 'a.status', 'a.entry_type', 'a.notes'],
            'searchable' => ['a.appointment_code', 'p.name', 'p.mobile', 'd.name', 'tm.name', 'a.status', 'a.notes'],
            'orderable' => [0 => 'a.id', 1 => 'a.appointment_code', 2 => 'a.appointment_date', 3 => 'a.start_time'],
            'defaultOrder' => ['a.appointment_date', 'DESC'],
            'where' => ['a.deleted_at IS NULL'],
            'filters' => function (Request $req, array &$where, array &$bindings) {
                $scopedDoctorId = current_doctor_id();
                if ($scopedDoctorId) {
                    $where[] = 'a.doctor_id = ?';
                    $bindings[] = $scopedDoctorId;
                } elseif ($req->input('doctor_id') !== null && $req->input('doctor_id') !== '') {
                    $where[] = 'a.doctor_id = ?';
                    $bindings[] = $req->input('doctor_id');
                }
                if ($req->input('patient_id') !== null && $req->input('patient_id') !== '') {
                    $where[] = 'a.patient_id = ?';
                    $bindings[] = $req->input('patient_id');
                }
                if ($req->input('status') !== null && $req->input('status') !== '') {
                    $where[] = 'a.status = ?';
                    $bindings[] = $req->input('status');
                }
                if ($req->input('appointment_id') !== null && $req->input('appointment_id') !== '') {
                    $where[] = 'a.id = ?';
                    $bindings[] = (int) $req->input('appointment_id');
                }
                if ($req->input('code') !== null && $req->input('code') !== '') {
                    $where[] = 'a.appointment_code = ?';
                    $bindings[] = $req->input('code');
                }
                if ($req->input('date_from')) {
                    $where[] = 'a.appointment_date >= ?';
                    $bindings[] = $req->input('date_from');
                }
                if ($req->input('date_to')) {
                    $where[] = 'a.appointment_date <= ?';
                    $bindings[] = $req->input('date_to');
                }
            },
            'rowFormatter' => function (array $row) {
                $row['appointment_date'] = format_date($row['appointment_date'] ?? null);
                $row['start_time'] = format_time($row['start_time'] ?? null);
                $row['end_time'] = format_time($row['end_time'] ?? null);
                $doctorLabel = doctor_label($row['doctor_name'] ?? null);
                $treatmentLabel = trim((string) ($row['treatment_name'] ?? ''));
                if (($row['entry_type'] ?? '') === 'doctor_remark') {
                    $row['patient_name'] = '<span class="badge bg-danger">Doctor Remark</span> ' . e((string) ($row['notes'] ?? ''));
                    $row['treatment_name'] = $treatmentLabel !== ''
                        ? '<span class="grid-hl grid-hl-treatment">' . e($treatmentLabel) . '</span>'
                        : '—';
                    $row['mobile'] = $row['mobile'] ?: '—';
                } else {
                    $patientLabel = e((string) ($row['patient_name'] ?? '—'));
                    // From appointments: open Appointment edit (not Patient edit / profile)
                    $row['patient_name'] = '<a class="grid-hl grid-hl-patient text-decoration-none" href="'
                        . app_url('appointments/' . $row['id'] . '/edit') . '">'
                        . $patientLabel . '</a>';
                    $row['treatment_name'] = $treatmentLabel !== ''
                        ? '<span class="grid-hl grid-hl-treatment">' . e($treatmentLabel) . '</span>'
                        : '<span class="text-muted">—</span>';
                }
                $row['doctor_name'] = '<span class="grid-hl grid-hl-doctor">' . e($doctorLabel) . '</span>';
                $status = (string) ($row['status'] ?? 'scheduled');
                if ((can('appointments.status_change') || can('appointments.edit')) && ($row['entry_type'] ?? 'appointment') !== 'doctor_remark') {
                    $options = [];
                    foreach (appointment_statuses_list() as $st) {
                        $options[$st['slug']] = $st['name'];
                    }
                    if (!$options) {
                        $options = [
                            'scheduled' => 'Scheduled',
                            'confirmed' => 'Confirmed',
                            'waiting' => 'Waiting',
                            'checked_in' => 'Checked In',
                            'with_doctor' => 'With Doctor',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            'no_show' => 'No Show',
                        ];
                    }
                    $statusClass = 'status-' . str_replace('_', '-', $status);
                    $html = '<select class="form-select form-select-sm appointment-status-select ' . e($statusClass) . '" data-id="' . e((string) $row['id']) . '" data-current="' . e($status) . '">';
                    foreach ($options as $value => $label) {
                        $selected = $status === $value ? ' selected' : '';
                        $html .= '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
                    }
                    $html .= '</select>';
                    $row['status_badge'] = $html;
                } else {
                    $row['status_badge'] = ($row['entry_type'] ?? '') === 'doctor_remark'
                        ? '<span class="badge appointment-status-pill status-remark">Remark</span>'
                        : status_badge($status);
                }
                $row['actions'] = $this->actions('appointments', 'appointments', $row['id']);
                return $row;
            },
        ]);
    }

    public function store(Request $request): void
    {
        $entryType = $request->input('entry_type') === 'doctor_remark' ? 'doctor_remark' : 'appointment';
        $scopedDoctorId = current_doctor_id();
        $rules = [
            'appointment_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ];
        if (!$scopedDoctorId) {
            $rules['doctor_id'] = 'required';
        }
        if ($entryType === 'appointment') {
            $rules['patient_id'] = 'required';
        }
        $data = $this->validate($request, $rules);
        if ($scopedDoctorId) {
            $data['doctor_id'] = $scopedDoctorId;
        }

        try {
            $id = $this->appointments->book(array_merge($data, [
                'entry_type' => $entryType,
                'visit_reason' => $request->input('visit_reason'),
                'treatment_master_id' => $request->input('treatment_master_id') ?: null,
                'notes' => $request->input('notes') ?: $request->input('visit_reason'),
                'status' => $request->input('status') ?: 'scheduled',
            ]));
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }

        $this->audit('appointments', 'create', $id, null, $data);
        $msg = $entryType === 'doctor_remark' ? 'Doctor remark added successfully.' : 'Appointment booked successfully.';
        $this->finish($request, $msg, 'appointments', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $appointment = $this->requireOwnAppointment($id);
        $lockedDoctorId = current_doctor_id();
        $doctors = $this->options('doctors', 'name', 'is_active = 1');
        if ($lockedDoctorId) {
            $doctors = array_values(array_filter($doctors, static fn ($d) => (int) $d['id'] === $lockedDoctorId));
        }
        $this->view('modules/appointments/form', [
            'title' => 'Edit Appointment',
            'pageTitle' => 'Edit Appointment',
            'appointment' => $appointment,
            'doctors' => $doctors,
            'patients' => $this->options('patients', 'name', 'is_active = 1'),
            'treatments' => $this->options('treatment_masters', 'name', 'is_active = 1'),
            'lockedDoctorId' => $lockedDoctorId,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireOwnAppointment($id);
        $scopedDoctorId = current_doctor_id();
        $entryType = $request->input('entry_type') === 'doctor_remark' ? 'doctor_remark' : (($old['entry_type'] ?? 'appointment') === 'doctor_remark' ? 'doctor_remark' : 'appointment');
        $rules = ['appointment_date' => 'required', 'start_time' => 'required', 'end_time' => 'required'];
        if (!$scopedDoctorId) {
            $rules['doctor_id'] = 'required';
        }
        if ($entryType === 'appointment') {
            $rules['patient_id'] = 'required';
        }
        $data = $this->validate($request, $rules);
        if ($scopedDoctorId) {
            $data['doctor_id'] = $scopedDoctorId;
        }

        try {
            Database::beginTransaction();
            if (!$this->appointments->isSlotAvailable((int) $data['doctor_id'], $data['appointment_date'], $data['start_time'], $data['end_time'], (int) $id, true)) {
                throw new \RuntimeException('Selected slot is already booked.');
            }
            $payload = [
                'patient_id' => !empty($data['patient_id']) ? (int) $data['patient_id'] : null,
                'doctor_id' => (int) $data['doctor_id'],
                'appointment_date' => $data['appointment_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'visit_reason' => $request->input('visit_reason') ?: $request->input('notes'),
                'treatment_master_id' => $request->input('treatment_master_id') ?: null,
                'notes' => $request->input('notes'),
                'entry_type' => $entryType,
                'status' => $request->input('status') ?: $old['status'],
                'updated_at' => $this->now(),
            ];
            if ($payload['entry_type'] === 'appointment' && empty($payload['patient_id'])) {
                throw new \RuntimeException('Patient is required for appointments.');
            }
            Database::update('appointments', $payload, 'id = :_id', ['_id' => (int) $id]);
            if ($payload['status'] !== $old['status']) {
                Database::insert('appointment_status_histories', [
                    'appointment_id' => (int) $id,
                    'from_status' => $old['status'],
                    'to_status' => $payload['status'],
                    'changed_by' => $this->currentUserId(),
                    'remarks' => $request->input('remarks'),
                    'created_at' => $this->now(),
                ]);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            $this->jsonError($e->getMessage());
        }

        $this->audit('appointments', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Appointment updated successfully.', 'appointments');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->requireOwnAppointment($id);
        $this->softDelete($request, 'appointments', $id, 'appointments');
    }

    public function changeStatus(Request $request, string $id): void
    {
        $this->requireOwnAppointment($id);
        $data = $this->validate($request, ['status' => 'required']);
        try {
            $this->appointments->changeStatus((int) $id, $data['status'], $request->input('remarks'));
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
        $this->finish($request, 'Appointment status updated successfully.', 'appointments');
    }

    public function queue(Request $request): void
    {
        $date = $request->query('date', date('Y-m-d'));
        $scopedDoctorId = current_doctor_id();
        $doctorId = $scopedDoctorId ?: $request->query('doctor_id');
        // Show all patient appointments for the day (appointment + walk_in), including
        // completed/cancelled columns. Only doctor remarks stay off the queue board.
        $where = [
            'a.appointment_date = ?',
            'a.deleted_at IS NULL',
            "IFNULL(a.entry_type, 'appointment') <> 'doctor_remark'",
            "a.status <> 'no_show'",
        ];
        $params = [$date];
        if ($doctorId !== null && $doctorId !== '') {
            $where[] = 'a.doctor_id = ?';
            $params[] = $doctorId;
        }

        $doctors = $this->options('doctors', 'name', 'is_active = 1');
        if ($scopedDoctorId) {
            $doctors = array_values(array_filter($doctors, static fn ($d) => (int) $d['id'] === $scopedDoctorId));
        }

        $this->view('modules/appointments/queue', [
            'title' => 'Appointment Queue',
            'pageTitle' => 'Appointment Queue',
            'date' => $date,
            'doctorId' => $doctorId,
            'lockedDoctorId' => $scopedDoctorId,
            'doctors' => $doctors,
            'queue' => Database::fetchAll(
                'SELECT a.*, p.name AS patient_name, p.mobile, p.patient_code, p.age, p.dob, p.gender,
                        p.allergies, p.blood_group, d.name AS doctor_name,
                        tm.name AS treatment_name, tm.default_price AS treatment_price
                 FROM appointments a
                 INNER JOIN patients p ON p.id = a.patient_id
                 INNER JOIN doctors d ON d.id = a.doctor_id
                 LEFT JOIN treatment_masters tm ON tm.id = a.treatment_master_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY a.start_time',
                $params
            ),
            'bookingFee' => BookingService::amount(),
        ]);
    }

    private function requireOwnAppointment(string $id): array
    {
        $appointment = $this->requireRow('appointments', $id, 'Appointment');
        $scopedDoctorId = current_doctor_id();
        if ($scopedDoctorId && (int) ($appointment['doctor_id'] ?? 0) !== $scopedDoctorId) {
            $this->jsonError('You can only access your own appointments.', null, 403);
        }
        return $appointment;
    }
}
