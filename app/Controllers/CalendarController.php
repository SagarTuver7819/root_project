<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class CalendarController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $lockedDoctorId = current_doctor_id();
        $doctors = $this->options('doctors', 'name', 'is_active = 1');
        if ($lockedDoctorId) {
            $doctors = array_values(array_filter($doctors, static fn ($d) => (int) $d['id'] === $lockedDoctorId));
        }

        $this->view('modules/calendar/index', [
            'title' => 'Calendar',
            'pageTitle' => 'Appointment Calendar',
            'doctors' => $doctors,
            'lockedDoctorId' => $lockedDoctorId,
            'patients' => Database::fetchAll(
                'SELECT id, patient_code, name, mobile FROM patients WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC LIMIT 100'
            ),
            'treatments' => $this->options('treatment_masters', 'name', 'is_active = 1'),
        ]);
    }

    public function events(Request $request): void
    {
        $where = ['a.deleted_at IS NULL'];
        $params = [];
        if ($request->query('start')) {
            $where[] = 'a.appointment_date >= ?';
            $params[] = substr((string) $request->query('start'), 0, 10);
        }
        if ($request->query('end')) {
            $where[] = 'a.appointment_date <= ?';
            $params[] = substr((string) $request->query('end'), 0, 10);
        }

        $scopedDoctorId = current_doctor_id();
        if ($scopedDoctorId) {
            $where[] = 'a.doctor_id = ?';
            $params[] = $scopedDoctorId;
        } elseif ($request->query('doctor_id')) {
            $where[] = 'a.doctor_id = ?';
            $params[] = $request->query('doctor_id');
        }
        if ($request->query('status')) {
            $where[] = 'a.status = ?';
            $params[] = $request->query('status');
        }
        if ($request->query('entry_type')) {
            $where[] = 'a.entry_type = ?';
            $params[] = $request->query('entry_type');
        }

        $rows = Database::fetchAll(
            'SELECT a.*, p.name AS patient_name, p.mobile, d.name AS doctor_name, tm.name AS treatment_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             INNER JOIN doctors d ON d.id = a.doctor_id
             LEFT JOIN treatment_masters tm ON tm.id = a.treatment_master_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY a.appointment_date, a.start_time',
            $params
        );

        $events = array_map(function (array $row) {
            $isRemark = ($row['entry_type'] ?? 'appointment') === 'doctor_remark';
            $remarkText = trim((string) ($row['notes'] ?: $row['visit_reason'] ?: 'Doctor Remark'));
            $treatment = trim((string) ($row['treatment_name'] ?? ''));
            $patient = trim((string) ($row['patient_name'] ?? ''));

            if ($isRemark) {
                $title = $remarkText;
                $color = '#DC2626';
                $className = 'fc-entry-remark';
            } else {
                $parts = array_filter([$patient, $treatment ?: null]);
                $title = implode(' · ', $parts) ?: doctor_label($row['doctor_name'] ?? '');
                $color = appointment_status_color((string) ($row['status'] ?? 'scheduled'));
                $className = 'fc-status-' . str_replace('_', '-', (string) $row['status']);
            }

            return [
                'id' => $row['id'],
                'title' => $title,
                'start' => $row['appointment_date'] . 'T' . $row['start_time'],
                'end' => $row['appointment_date'] . 'T' . $row['end_time'],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'className' => $className,
                'extendedProps' => [
                    'code' => $row['appointment_code'] ?? '',
                    'status' => $row['status'],
                    'entry_type' => $row['entry_type'] ?? 'appointment',
                    'patient_name' => $patient,
                    'doctor_name' => doctor_label($row['doctor_name'] ?? ''),
                    'treatment_name' => $treatment,
                    'notes' => $row['notes'],
                    'mobile' => $row['mobile'] ?? null,
                ],
            ];
        }, $rows);

        Response::json($events);
    }

    public function slots(Request $request): void
    {
        $scopedDoctorId = current_doctor_id();
        $doctorId = $scopedDoctorId ?: (int) $request->query('doctor_id');
        $date = (string) $request->query('date', date('Y-m-d'));
        if (!$doctorId) {
            $this->jsonError('Doctor is required.');
        }

        $day = (int) date('w', strtotime($date));
        $schedule = Database::fetch('SELECT * FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?', [$doctorId, $day]);
        if (!$schedule || (int) $schedule['is_off']) {
            Response::json([]);
        }

        $leaves = Database::fetchAll('SELECT * FROM doctor_leaves WHERE doctor_id = ? AND leave_date = ?', [$doctorId, $date]);
        foreach ($leaves as $leave) {
            if ($leave['leave_type'] === 'full_day') {
                Response::json([]);
            }
        }

        $appointments = Database::fetchAll(
            "SELECT start_time, end_time FROM appointments
             WHERE doctor_id = ? AND appointment_date = ? AND deleted_at IS NULL AND status NOT IN ('cancelled', 'no_show')",
            [$doctorId, $date]
        );

        $slots = [];
        $duration = (int) ($schedule['slot_duration'] ?: 30);
        $cursor = strtotime($date . ' ' . $schedule['start_time']);
        $end = strtotime($date . ' ' . $schedule['end_time']);
        while ($cursor + ($duration * 60) <= $end) {
            $slotStart = date('H:i:s', $cursor);
            $slotEnd = date('H:i:s', $cursor + ($duration * 60));
            $available = !$this->blocked($slotStart, $slotEnd, $appointments)
                && !$this->inBreak($slotStart, $slotEnd, $schedule)
                && !$this->blocked($slotStart, $slotEnd, $leaves);
            $slots[] = ['start_time' => $slotStart, 'end_time' => $slotEnd, 'available' => $available];
            $cursor += $duration * 60;
        }

        Response::json($slots);
    }

    private function blocked(string $start, string $end, array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (empty($block['start_time']) || empty($block['end_time'])) {
                continue;
            }
            if ($start < $block['end_time'] && $end > $block['start_time']) {
                return true;
            }
        }
        return false;
    }

    private function inBreak(string $start, string $end, array $schedule): bool
    {
        if (empty($schedule['break_start']) || empty($schedule['break_end'])) {
            return false;
        }
        return $start < $schedule['break_end'] && $end > $schedule['break_start'];
    }
}
