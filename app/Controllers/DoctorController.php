<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class DoctorController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/doctors/index', ['title' => 'Doctors', 'pageTitle' => 'Doctors']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'doctors d',
            'joins' => ['LEFT JOIN users u ON u.id = d.user_id'],
            'columns' => ['d.id', 'd.doctor_code', 'd.name', 'd.mobile', 'd.specialization', 'd.consultation_fee', 'd.slot_duration', 'u.name AS user_name', 'd.is_active'],
            'searchable' => ['d.doctor_code', 'd.name', 'd.mobile', 'd.email', 'd.specialization', 'd.registration_number'],
            'orderable' => [0 => 'd.id', 1 => 'd.doctor_code', 2 => 'd.name', 5 => 'd.consultation_fee'],
            'defaultOrder' => ['d.id', 'DESC'],
            'where' => ['d.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('doctors', 'doctors', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/doctors/form', [
            'title' => 'Add Doctor',
            'pageTitle' => 'Add Doctor',
            'doctor' => null,
            'users' => Database::fetchAll('SELECT id, name FROM users WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name'),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $payload['doctor_code'] = $this->nextCode('doctors', 'doctor_code', 'DOC');
        $id = $this->insertWithTimestamps('doctors', $payload);
        $this->audit('doctors', 'create', $id, null, $payload);
        $this->finish($request, 'Doctor created successfully.', 'doctors', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/doctors/form', [
            'title' => 'Edit Doctor',
            'pageTitle' => 'Edit Doctor',
            'doctor' => $this->requireRow('doctors', $id, 'Doctor'),
            'users' => Database::fetchAll('SELECT id, name FROM users WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name'),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('doctors', $id, 'Doctor');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('doctors', $payload, (int) $id);
        $this->audit('doctors', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Doctor updated successfully.', 'doctors');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'doctors', $id, 'doctors');
    }

    public function schedules(Request $request, string $id): void
    {
        $this->view('modules/doctors/schedules', [
            'title' => 'Doctor Schedule',
            'pageTitle' => 'Doctor Schedule',
            'doctor' => $this->requireRow('doctors', $id, 'Doctor'),
            'schedules' => Database::fetchAll('SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week', [$id]),
        ]);
    }

    public function saveSchedules(Request $request, string $id): void
    {
        $doctor = $this->requireRow('doctors', $id, 'Doctor');
        $days = (array) $request->input('schedules', []);
        foreach ($days as $day => $row) {
            $payload = [
                'doctor_id' => (int) $id,
                'day_of_week' => (int) $day,
                'start_time' => $row['start_time'] ?: '09:00:00',
                'end_time' => $row['end_time'] ?: '18:00:00',
                'break_start' => $row['break_start'] ?: null,
                'break_end' => $row['break_end'] ?: null,
                'slot_duration' => (int) ($row['slot_duration'] ?: $doctor['slot_duration']),
                'is_off' => !empty($row['is_off']) ? 1 : 0,
                'updated_at' => $this->now(),
            ];
            $existing = Database::fetch('SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?', [$id, $day]);
            if ($existing) {
                Database::update('doctor_schedules', $payload, 'id = :_id', ['_id' => (int) $existing['id']]);
            } else {
                $payload['created_at'] = $this->now();
                Database::insert('doctor_schedules', $payload);
            }
        }
        $this->audit('doctors', 'schedule', (int) $id, null, $days);
        $this->finish($request, 'Doctor schedule saved successfully.', 'doctors/' . $id . '/schedules');
    }

    public function leaves(Request $request, string $id): void
    {
        $this->view('modules/doctors/leaves', [
            'title' => 'Doctor Leaves',
            'pageTitle' => 'Doctor Leaves',
            'doctor' => $this->requireRow('doctors', $id, 'Doctor'),
            'leaves' => Database::fetchAll('SELECT * FROM doctor_leaves WHERE doctor_id = ? ORDER BY leave_date DESC', [$id]),
        ]);
    }

    public function storeLeave(Request $request, string $id): void
    {
        $this->requireRow('doctors', $id, 'Doctor');
        $data = $this->validate($request, ['leave_date' => 'required', 'leave_type' => 'required|in:full_day,partial,blocked']);
        $payload = [
            'doctor_id' => (int) $id,
            'leave_date' => $data['leave_date'],
            'start_time' => $request->input('start_time') ?: null,
            'end_time' => $request->input('end_time') ?: null,
            'reason' => $request->input('reason'),
            'leave_type' => $data['leave_type'],
        ];
        $leaveId = $this->insertWithTimestamps('doctor_leaves', $payload);
        $this->audit('doctors', 'leave_create', $leaveId, null, $payload);
        $this->finish($request, 'Doctor leave saved successfully.', 'doctors/' . $id . '/leaves', ['id' => $leaveId]);
    }

    public function updateLeave(Request $request, string $id, string $leaveId): void
    {
        $old = Database::fetch('SELECT * FROM doctor_leaves WHERE id = ? AND doctor_id = ?', [$leaveId, $id]);
        if (!$old) {
            $this->jsonError('Doctor leave not found.', null, 404);
        }
        $data = $this->validate($request, ['leave_date' => 'required', 'leave_type' => 'required|in:full_day,partial,blocked']);
        $payload = [
            'leave_date' => $data['leave_date'],
            'start_time' => $request->input('start_time') ?: null,
            'end_time' => $request->input('end_time') ?: null,
            'reason' => $request->input('reason'),
            'leave_type' => $data['leave_type'],
        ];
        $this->updateWithTimestamp('doctor_leaves', $payload, (int) $leaveId);
        $this->audit('doctors', 'leave_update', (int) $leaveId, $old, $payload);
        $this->finish($request, 'Doctor leave updated successfully.', 'doctors/' . $id . '/leaves');
    }

    public function destroyLeave(Request $request, string $id, string $leaveId): void
    {
        Database::query('DELETE FROM doctor_leaves WHERE id = ? AND doctor_id = ?', [$leaveId, $id]);
        $this->audit('doctors', 'leave_delete', (int) $leaveId);
        $this->jsonSuccess('Doctor leave deleted successfully.');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'user_id' => $request->input('user_id') ?: null,
            'name' => $data['name'],
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'qualification' => $request->input('qualification'),
            'specialization' => $request->input('specialization'),
            'registration_number' => $request->input('registration_number'),
            'consultation_fee' => $this->money($request->input('consultation_fee')),
            'slot_duration' => (int) ($request->input('slot_duration') ?: 30),
            'is_active' => $this->activeValue($request),
        ];
    }
}
