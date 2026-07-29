<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use RuntimeException;

class AppointmentService
{
    public function book(array $data): int
    {
        $entryType = ($data['entry_type'] ?? 'appointment') === 'doctor_remark' ? 'doctor_remark' : 'appointment';

        try {
            Database::beginTransaction();

            if ($entryType === 'appointment' && empty($data['patient_id'])) {
                throw new RuntimeException('Patient is required for appointments.');
            }

            if (!$this->isSlotAvailable(
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

            $this->insertHistory($id, null, $data['status'] ?? 'scheduled', $data['remarks'] ?? ($entryType === 'doctor_remark' ? 'Doctor remark added' : 'Appointment booked'));
            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
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
