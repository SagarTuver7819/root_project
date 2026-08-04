<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use RuntimeException;

class AppointmentService
{
    public function book(array $data): int
    {
        $entryType = $data['entry_type'] ?? 'appointment';
        if (!in_array($entryType, ['appointment', 'doctor_remark', 'walk_in'], true)) {
            $entryType = 'appointment';
        }

        try {
            Database::beginTransaction();

            if (in_array($entryType, ['appointment', 'walk_in'], true) && empty($data['patient_id'])) {
                throw new RuntimeException('Patient is required for appointments.');
            }

            $skipSlotCheck = !empty($data['skip_slot_check']) || $entryType === 'walk_in';
            if (!$skipSlotCheck && !$this->isSlotAvailable(
                (int) $data['doctor_id'],
                (string) $data['appointment_date'],
                (string) $data['start_time'],
                (string) $data['end_time'],
                null,
                true
            )) {
                throw new RuntimeException('Selected slot is already booked.');
            }

            if ($entryType === 'doctor_remark' && empty($data['notes']) && empty($data['visit_reason'])) {
                throw new RuntimeException('Remark text is required.');
            }

            $code = $data['appointment_code'] ?? null;
            $id = null;
            $attempts = 0;
            while ($attempts < 5) {
                $attempts++;
                $appointmentCode = $code ?: $this->nextCode('appointments', 'appointment_code', 'APT');
                try {
                    $id = Database::insert('appointments', [
                        'appointment_code' => $appointmentCode,
                        'patient_id' => !empty($data['patient_id']) ? (int) $data['patient_id'] : null,
                        'doctor_id' => (int) $data['doctor_id'],
                        'appointment_date' => $data['appointment_date'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'visit_reason' => $data['visit_reason'] ?? null,
                        'treatment_master_id' => $data['treatment_master_id'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'entry_type' => $entryType,
                        'status' => $data['status'] ?? 'scheduled',
                        'created_by' => Auth::id(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    break;
                } catch (\Throwable $insertError) {
                    // Race / stale max: regenerate code and retry once for duplicate key only.
                    if ($code || !str_contains($insertError->getMessage(), 'Duplicate entry') || $attempts >= 5) {
                        throw $insertError;
                    }
                }
            }
            if (!$id) {
                throw new RuntimeException('Unable to create appointment code. Please try again.');
            }

            $defaultRemark = $entryType === 'doctor_remark'
                ? 'Doctor remark added'
                : ($entryType === 'walk_in' ? 'Walk-in sent to doctor' : 'Appointment booked');
            $this->insertHistory($id, null, $data['status'] ?? 'scheduled', $data['remarks'] ?? $defaultRemark);
            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Create a same-day walk-in consultation for a patient assigned to a doctor.
     */
    public function assignWalkIn(int $patientId, int $doctorId, ?string $reason = null): int
    {
        $today = date('Y-m-d');
        $doctor = Database::fetch(
            'SELECT id, slot_duration FROM doctors WHERE id = ? AND deleted_at IS NULL AND is_active = 1',
            [$doctorId]
        );
        if (!$doctor) {
            throw new RuntimeException('Selected doctor is not available.');
        }

        $duration = max(15, (int) ($doctor['slot_duration'] ?: 30));
        $startTs = time();
        $start = date('H:i:00', $startTs);
        $end = date('H:i:00', $startTs + ($duration * 60));

        // Unique start_time per doctor/day — nudge by 1 minute if needed.
        for ($i = 0; $i < 60; $i++) {
            $probeStart = date('H:i:00', $startTs + ($i * 60));
            $probeEnd = date('H:i:00', $startTs + ($i * 60) + ($duration * 60));
            $exists = Database::fetch(
                "SELECT id FROM appointments
                 WHERE doctor_id = ? AND appointment_date = ? AND start_time = ?
                   AND deleted_at IS NULL LIMIT 1",
                [$doctorId, $today, $probeStart]
            );
            if (!$exists) {
                $start = $probeStart;
                $end = $probeEnd;
                break;
            }
        }

        return $this->book([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $today,
            'start_time' => $start,
            'end_time' => $end,
            'visit_reason' => $reason ?: 'Walk-in consultation',
            'entry_type' => 'walk_in',
            'status' => 'waiting',
            'skip_slot_check' => true,
            'remarks' => 'Front desk sent patient to doctor',
        ]);
    }

    public function isSlotAvailable(
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeAppointmentId = null,
        bool $lock = false
    ): bool {
        $sql = "SELECT id FROM appointments
                WHERE doctor_id = ?
                  AND appointment_date = ?
                  AND deleted_at IS NULL
                  AND status NOT IN ('cancelled', 'no_show')
                  AND start_time < ?
                  AND end_time > ?";
        $params = [$doctorId, $date, $endTime, $startTime];

        if ($excludeAppointmentId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeAppointmentId;
        }

        $sql .= ' LIMIT 1';
        if ($lock) {
            $sql .= ' FOR UPDATE';
        }

        return Database::fetch($sql, $params) === null;
    }

    public function changeStatus(int $appointmentId, string $status, ?string $remarks = null): void
    {
        $appointment = Database::fetch('SELECT * FROM appointments WHERE id = ? AND deleted_at IS NULL', [$appointmentId]);
        if (!$appointment) {
            throw new RuntimeException('Appointment not found.');
        }

        Database::update('appointments', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => $appointmentId]);

        $this->insertHistory($appointmentId, $appointment['status'] ?? null, $status, $remarks);
        AuditService::log('appointments', 'status', $appointmentId, ['status' => $appointment['status']], ['status' => $status]);
    }

    /**
     * Sync calendar appointment(s) from Clinical Chart next-appt fields.
     *
     * @return array{main:?int,implant:?int}
     */
    public function syncFromClinicalChart(int $patientId, array $chart, ?int $existingAppointmentId = null, ?int $existingImplantAppointmentId = null): array
    {
        $slots = [];

        $mainDate = trim((string) ($chart['next_appt_date'] ?? ''));
        $mainTime = trim((string) ($chart['next_appt_time'] ?? ''));
        $mainDoctor = (int) ($chart['next_appt_doctor_id'] ?? 0);
        $mainTest = trim((string) ($chart['next_appt_test'] ?? ''));
        if ($mainDate !== '' && $mainTime !== '' && $mainDoctor > 0) {
            $slots[] = [
                'date' => $mainDate,
                'time' => $mainTime,
                'doctor_id' => $mainDoctor,
                'test' => $mainTest !== '' ? $mainTest : 'Treatment appointment',
                'source' => 'next_appt',
                'existing_id' => $existingAppointmentId,
            ];
        }

        $implant = [];
        if (!empty($chart['implant_work'])) {
            if (is_string($chart['implant_work'])) {
                $decoded = json_decode($chart['implant_work'], true);
                $implant = is_array($decoded) ? $decoded : [];
            } elseif (is_array($chart['implant_work'])) {
                $implant = $chart['implant_work'];
            }
        }
        $impDate = trim((string) ($implant['next_date'] ?? ''));
        $impTime = trim((string) ($implant['next_time'] ?? ''));
        $impWork = trim((string) ($implant['work_to_be_done'] ?? ''));
        $impDoctor = $mainDoctor > 0 ? $mainDoctor : (int) ($chart['allotted_doctor_id'] ?? 0);

        if ($impDate !== '' && $impTime !== '' && $impDoctor > 0) {
            $sameAsMain = $mainDate === $impDate
                && $this->normalizeTime($mainTime ?: '') === $this->normalizeTime($impTime)
                && $mainDoctor === $impDoctor;
            if (!$sameAsMain) {
                $slots[] = [
                    'date' => $impDate,
                    'time' => $impTime,
                    'doctor_id' => $impDoctor,
                    'test' => $impWork !== '' ? $impWork : 'Implant treatment',
                    'source' => 'implant',
                    'existing_id' => $existingImplantAppointmentId,
                ];
            }
        }

        if ($slots === []) {
            if ($existingAppointmentId) {
                $this->softCancel($existingAppointmentId, 'Next appointment cleared from clinical chart');
            }
            if ($existingImplantAppointmentId) {
                $this->softCancel($existingImplantAppointmentId, 'Implant next appointment cleared from clinical chart');
            }
            return ['main' => null, 'implant' => null];
        }

        $mainId = null;
        $implantId = null;
        foreach ($slots as $slot) {
            $id = $this->upsertTreatmentSlot($patientId, $slot);
            if ($slot['source'] === 'next_appt') {
                $mainId = $id;
            } else {
                $implantId = $id;
            }
        }

        return ['main' => $mainId, 'implant' => $implantId];
    }

    /**
     * Create/update a treatment calendar appointment for clinical-chart booking.
     */
    private function upsertTreatmentSlot(int $patientId, array $slot): int
    {
        $date = $slot['date'];
        $time = $this->normalizeTime($slot['time']);
        $doctorId = (int) $slot['doctor_id'];
        $test = (string) $slot['test'];
        $existingAppointmentId = !empty($slot['existing_id']) ? (int) $slot['existing_id'] : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new RuntimeException('Next appointment date is invalid.');
        }
        if ($time === null) {
            throw new RuntimeException('Next appointment time is invalid.');
        }

        $doctor = Database::fetch(
            'SELECT id, slot_duration, name FROM doctors WHERE id = ? AND deleted_at IS NULL AND is_active = 1',
            [$doctorId]
        );
        if (!$doctor) {
            throw new RuntimeException('Next appointment doctor is not available.');
        }

        $duration = max(15, (int) ($doctor['slot_duration'] ?: 30));
        $startTs = strtotime($date . ' ' . $time);
        if ($startTs === false) {
            throw new RuntimeException('Next appointment date/time is invalid.');
        }
        $startTime = date('H:i:s', $startTs);
        $endTime = date('H:i:s', $startTs + ($duration * 60));
        if ($endTime <= $startTime) {
            $endTime = '23:59:00';
        }

        // Prefer linked appointment; else same patient+doctor+date treatment appt; else exact start match for same patient.
        $existing = null;
        if ($existingAppointmentId) {
            $existing = Database::fetch(
                'SELECT * FROM appointments WHERE id = ? AND deleted_at IS NULL',
                [$existingAppointmentId]
            );
        }
        if (!$existing || ($existing['status'] ?? '') === 'cancelled') {
            $existing = Database::fetch(
                "SELECT * FROM appointments
                 WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ?
                   AND deleted_at IS NULL AND status NOT IN ('cancelled','no_show')
                   AND IFNULL(entry_type,'appointment') IN ('appointment','treatment')
                 ORDER BY id DESC LIMIT 1",
                [$patientId, $doctorId, $date]
            );
        }
        if (!$existing) {
            $existing = Database::fetch(
                "SELECT * FROM appointments
                 WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND start_time = ?
                   AND deleted_at IS NULL
                 ORDER BY id DESC LIMIT 1",
                [$patientId, $doctorId, $date, $startTime]
            );
        }

        $excludeId = $existing ? (int) $existing['id'] : null;
        $blocker = $this->findSlotConflict($doctorId, $date, $startTime, $endTime, $excludeId);
        if ($blocker) {
            // Same patient occupies slot → update that row instead of failing.
            if ((int) ($blocker['patient_id'] ?? 0) === $patientId) {
                $existing = $blocker;
                $excludeId = (int) $blocker['id'];
            } else {
                $who = trim((string) ($blocker['patient_name'] ?? 'another patient'));
                $bt = substr((string) $blocker['start_time'], 0, 5) . '–' . substr((string) $blocker['end_time'], 0, 5);
                throw new RuntimeException(
                    'Selected slot overlaps existing booking for ' . doctor_label($doctor['name'] ?? '') .
                    ' on ' . date('d-m-Y', strtotime($date)) . ' (' . $bt . ', ' . $who . '). Please choose another time.'
                );
            }
        }

        $reason = $test;
        $notes = 'Treatment appointment (clinical chart)';

        if ($existing && ($existing['status'] ?? '') !== 'cancelled') {
            Database::update('appointments', [
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'visit_reason' => $reason,
                'notes' => $notes,
                'entry_type' => 'appointment',
                'status' => in_array(($existing['status'] ?? ''), ['completed', 'no_show'], true) ? 'scheduled' : ($existing['status'] ?: 'scheduled'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = :_id', ['_id' => (int) $existing['id']]);

            $this->insertHistory((int) $existing['id'], $existing['status'] ?? null, 'scheduled', 'Updated treatment appointment from clinical chart');
            return (int) $existing['id'];
        }

        // New treatment appointment — skip generic overlap after we already validated above.
        return $this->book([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'visit_reason' => $reason,
            'notes' => $notes,
            'entry_type' => 'appointment',
            'status' => 'scheduled',
            'skip_slot_check' => true,
            'remarks' => 'Treatment booked from clinical chart',
        ]);
    }

    private function normalizeTime(string $time): ?string
    {
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        return null;
    }

    private function findSlotConflict(
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeAppointmentId = null
    ): ?array {
        $sql = "SELECT a.id, a.patient_id, a.start_time, a.end_time, a.status, a.entry_type, p.name AS patient_name
                FROM appointments a
                LEFT JOIN patients p ON p.id = a.patient_id
                WHERE a.doctor_id = ?
                  AND a.appointment_date = ?
                  AND a.deleted_at IS NULL
                  AND a.status NOT IN ('cancelled', 'no_show')
                  AND a.start_time < ?
                  AND a.end_time > ?";
        $params = [$doctorId, $date, $endTime, $startTime];
        if ($excludeAppointmentId) {
            $sql .= ' AND a.id != ?';
            $params[] = $excludeAppointmentId;
        }
        $sql .= ' ORDER BY a.start_time ASC LIMIT 1';
        return Database::fetch($sql, $params);
    }

    public function softCancel(int $appointmentId, string $remarks = 'Cancelled'): void
    {
        $appointment = Database::fetch('SELECT * FROM appointments WHERE id = ? AND deleted_at IS NULL', [$appointmentId]);
        if (!$appointment || ($appointment['status'] ?? '') === 'cancelled') {
            return;
        }
        $this->changeStatus($appointmentId, 'cancelled', $remarks);
    }

    private function insertHistory(int $appointmentId, ?string $from, string $to, ?string $remarks = null): void
    {
        Database::insert('appointment_status_histories', [
            'appointment_id' => $appointmentId,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => Auth::id(),
            'remarks' => $remarks,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nextCode(string $table, string $column, string $prefix, int $pad = 5): string
    {
        $rows = Database::fetchAll(
            "SELECT {$column} AS code FROM {$table} WHERE {$column} LIKE ?",
            [$prefix . '%']
        );
        $max = 0;
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)$/';
        foreach ($rows as $row) {
            $code = (string) ($row['code'] ?? '');
            if (preg_match($pattern, $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $prefix . str_pad((string) ($max + 1), $pad, '0', STR_PAD_LEFT);
    }
}
