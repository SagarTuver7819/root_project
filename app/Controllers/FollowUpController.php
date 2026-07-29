<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;
use App\Services\AppointmentService;

class FollowUpController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/follow-ups/index', ['title' => 'Follow Ups', 'pageTitle' => 'Follow Ups']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'follow_ups fu',
            'joins' => ['INNER JOIN patients p ON p.id = fu.patient_id', 'INNER JOIN doctors d ON d.id = fu.doctor_id'],
            'columns' => ['fu.id', 'fu.follow_up_date', 'p.name AS patient_name', 'd.name AS doctor_name', 'fu.reason', 'fu.status', 'fu.appointment_id'],
            'searchable' => ['p.name', 'd.name', 'fu.reason', 'fu.status'],
            'orderable' => [0 => 'fu.id', 1 => 'fu.follow_up_date'],
            'defaultOrder' => ['fu.follow_up_date', 'ASC'],
            'where' => ['fu.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['follow_up_date'] = format_date($row['follow_up_date'] ?? null);
                $row['doctor_name'] = doctor_label($row['doctor_name'] ?? null);
                $row['status_badge'] = status_badge($row['status'] ?? '');
                $row['actions'] = $this->actions('follow_ups', 'follow-ups', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/follow-ups/form', $this->formData(null, 'Add Follow Up'));
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['patient_id' => 'required', 'doctor_id' => 'required', 'follow_up_date' => 'required']);
        $payload = $this->payload($request, $data);
        $id = $this->insertWithTimestamps('follow_ups', $payload);
        $this->audit('follow_ups', 'create', $id, null, $payload);
        $this->finish($request, 'Follow up created successfully.', 'follow-ups', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/follow-ups/form', $this->formData($this->requireRow('follow_ups', $id, 'Follow up'), 'Edit Follow Up'));
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('follow_ups', $id, 'Follow up');
        $data = $this->validate($request, ['patient_id' => 'required', 'doctor_id' => 'required', 'follow_up_date' => 'required']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('follow_ups', $payload, (int) $id);
        $this->audit('follow_ups', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Follow up updated successfully.', 'follow-ups');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'follow_ups', $id, 'follow_ups');
    }

    public function convertToAppointment(Request $request, string $id): void
    {
        $followUp = $this->requireRow('follow_ups', $id, 'Follow up');
        $start = $request->input('start_time') ?: '09:00:00';
        $end = $request->input('end_time') ?: date('H:i:s', strtotime($followUp['follow_up_date'] . ' ' . $start) + 1800);
        try {
            $appointmentId = (new AppointmentService())->book([
                'patient_id' => $followUp['patient_id'],
                'doctor_id' => $followUp['doctor_id'],
                'appointment_date' => $request->input('appointment_date') ?: $followUp['follow_up_date'],
                'start_time' => $start,
                'end_time' => $end,
                'visit_reason' => $followUp['reason'] ?: 'Follow up',
                'notes' => $followUp['notes'],
                'status' => 'scheduled',
            ]);
            Database::update('follow_ups', ['appointment_id' => $appointmentId, 'status' => 'scheduled', 'updated_at' => $this->now()], 'id = :_id', ['_id' => (int) $id]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
        $this->finish($request, 'Follow up converted to appointment.', 'appointments', ['appointment_id' => $appointmentId]);
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'patient_id' => (int) $data['patient_id'],
            'doctor_id' => (int) $data['doctor_id'],
            'treatment_plan_id' => $request->input('treatment_plan_id') ?: null,
            'last_visit_id' => $request->input('last_visit_id') ?: null,
            'follow_up_date' => $data['follow_up_date'],
            'reason' => $request->input('reason'),
            'notes' => $request->input('notes'),
            'status' => $request->input('status') ?: 'pending',
            'appointment_id' => $request->input('appointment_id') ?: null,
        ];
    }

    private function formData(?array $followUp, string $title): array
    {
        return ['title' => $title, 'pageTitle' => $title, 'followUp' => $followUp, 'patients' => $this->options('patients', 'name', 'is_active = 1'), 'doctors' => $this->options('doctors', 'name', 'is_active = 1')];
    }
}
