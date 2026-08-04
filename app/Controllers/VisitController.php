<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;
use App\Core\Session;

class VisitController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/visits/index', ['title' => 'Visits', 'pageTitle' => 'Patient Visits']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'patient_visits v',
            'joins' => ['INNER JOIN patients p ON p.id = v.patient_id', 'INNER JOIN doctors d ON d.id = v.doctor_id'],
            'columns' => ['v.id', 'v.visit_code', 'v.visit_date', 'v.visit_time', 'v.patient_id', 'v.doctor_id', 'p.name AS patient_name', 'd.name AS doctor_name', 'v.diagnosis', 'v.status'],
            'searchable' => ['v.visit_code', 'p.name', 'd.name', 'v.diagnosis', 'v.status'],
            'orderable' => [0 => 'v.id', 1 => 'v.visit_code', 2 => 'v.visit_date'],
            'defaultOrder' => ['v.id', 'DESC'],
            'where' => ['v.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['visit_date'] = format_date($row['visit_date'] ?? null);
                $row['visit_time'] = format_time($row['visit_time'] ?? null);
                $row['doctor_name'] = doctor_label($row['doctor_name'] ?? null);
                $row['status_badge'] = status_badge($row['status'] ?? '');
                
                $actions = '<div class="table-actions">';
                if (can('visits.view')) {
                    $actions .= '<a href="' . app_url('visits/' . $row['id']) . '" class="btn btn-action" title="View Full Report"><i class="bi bi-eye"></i></a>';
                    $actions .= '<a href="' . app_url('visits/' . $row['id'] . '/print') . '" class="btn btn-action btn-action-primary" target="_blank" title="Print/PDF Clinical Report"><i class="bi bi-printer"></i></a>';
                }
                if (can('visits.edit')) {
                    $actions .= '<a href="' . app_url('visits/' . $row['id'] . '/edit') . '" class="btn btn-action" title="Edit Visit Notes"><i class="bi bi-pencil"></i></a>';
                }
                if (can('billing.add')) {
                    $billQs = http_build_query(array_filter([
                        'patient_id' => $row['patient_id'] ?? null,
                        'doctor_id' => $row['doctor_id'] ?? null,
                        'treatment_master_id' => $row['treatment_master_id'] ?? null,
                    ]));
                    $actions .= '<a href="' . app_url('billing/create?' . $billQs) . '" class="btn btn-action" title="Collect Payment / Create Bill" style="color:#16a34a;border-color:#bbf7d0;background:#f0fdf4"><i class="bi bi-receipt"></i></a>';
                }
                if (can('visits.delete')) {
                    $actions .= '<button type="button" class="btn btn-action btn-action-danger btn-delete" data-url="' . app_url('visits/' . $row['id'] . '/delete') . '" title="Delete Visit"><i class="bi bi-trash"></i></button>';
                }
                $actions .= '</div>';
                
                $row['actions'] = $actions;
                return $row;
            },
        ]);
    }

    public function start(Request $request, string $appointmentId): void
    {
        $this->startVisitAndRedirect($request, $appointmentId);
    }

    public function open(Request $request, string $appointmentId): void
    {
        $this->startVisitAndRedirect($request, $appointmentId);
    }

    private function startVisitAndRedirect(Request $request, string $appointmentId): void
    {
        $appointment = Database::fetch('SELECT * FROM appointments WHERE id = ? AND deleted_at IS NULL', [$appointmentId]);
        if (!$appointment) {
            if ($request->isAjax()) {
                $this->jsonError('Appointment not found.', null, 404);
            }
            Session::flash('error', 'Appointment not found.');
            $this->redirect('dashboard');
        }

        $scopedDoctorId = current_doctor_id();
        if ($scopedDoctorId && (int) $appointment['doctor_id'] !== $scopedDoctorId) {
            if ($request->isAjax()) {
                $this->jsonError('This patient is assigned to another doctor.', null, 403);
            }
            Session::flash('error', 'This patient is assigned to another doctor.');
            $this->redirect('dashboard');
        }

        $visit = Database::fetch('SELECT id FROM patient_visits WHERE appointment_id = ? AND deleted_at IS NULL', [$appointmentId]);
        if ($visit) {
            if ($request->isAjax()) {
                $this->jsonSuccess('Opening visit.', ['id' => (int) $visit['id'], 'redirect' => app_url('visits/' . $visit['id'] . '/edit')]);
            }
            $this->redirect('visits/' . $visit['id'] . '/edit');
        }

        try {
            Database::beginTransaction();
            $id = Database::insert('patient_visits', [
                'visit_code' => $this->nextCode('patient_visits', 'visit_code', 'VIS'),
                'patient_id' => (int) $appointment['patient_id'],
                'appointment_id' => (int) $appointmentId,
                'doctor_id' => (int) $appointment['doctor_id'],
                'visit_date' => date('Y-m-d'),
                'visit_time' => date('H:i:s'),
                'chief_complaint' => $appointment['visit_reason'],
                'status' => 'in_progress',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
            Database::update('appointments', ['status' => 'with_doctor', 'updated_at' => $this->now()], 'id = :_id', ['_id' => (int) $appointmentId]);
            Database::insert('appointment_status_histories', [
                'appointment_id' => (int) $appointmentId,
                'from_status' => $appointment['status'],
                'to_status' => 'with_doctor',
                'changed_by' => $this->currentUserId(),
                'remarks' => 'Visit started',
                'created_at' => $this->now(),
            ]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            if ($request->isAjax()) {
                $this->jsonError($e->getMessage());
            }
            Session::flash('error', $e->getMessage());
            $this->redirect('dashboard');
        }

        $this->audit('visits', 'create', $id, null, ['appointment_id' => $appointmentId]);
        $editUrl = app_url('visits/' . $id . '/edit');
        if ($request->isAjax()) {
            $this->jsonSuccess('Visit started successfully.', ['id' => $id, 'redirect' => $editUrl]);
        }
        $this->redirect('visits/' . $id . '/edit');
    }

    public function show(Request $request, string $id): void
    {
        $visit = $this->findVisit($id);
        $examinations = Database::fetchAll('SELECT * FROM dental_examinations WHERE visit_id = ? ORDER BY id ASC', [$id]);
        $prescriptions = Database::fetchAll(
            'SELECT pr.*, pi.medicine_name, pi.dosage, pi.frequency, pi.duration, pi.before_after_food, pi.instructions
             FROM prescriptions pr
             LEFT JOIN prescription_items pi ON pi.prescription_id = pr.id
             WHERE pr.visit_id = ? AND pr.deleted_at IS NULL',
            [$id]
        );
        $treatments = Database::fetchAll(
            'SELECT ptp.*, tm.name AS treatment_name
             FROM patient_treatment_plans ptp
             LEFT JOIN treatment_masters tm ON tm.id = ptp.treatment_master_id
             WHERE ptp.visit_id = ? AND ptp.deleted_at IS NULL',
            [$id]
        );

        $this->view('modules/visits/show', [
            'title' => 'Clinical Report - ' . $visit['visit_code'],
            'pageTitle' => 'Clinical Visit Report',
            'visit' => $visit,
            'examinations' => $examinations,
            'prescriptions' => $prescriptions,
            'treatments' => $treatments,
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $visit = $this->findVisit($id);
        $this->view('modules/visits/form', [
            'title' => 'Edit Visit ' . $visit['visit_code'],
            'pageTitle' => 'Edit Visit Details',
            'visit' => $visit,
        ]);
    }

    public function print(Request $request, string $id): void
    {
        $visit = $this->findVisit($id);
        $examinations = Database::fetchAll('SELECT * FROM dental_examinations WHERE visit_id = ? ORDER BY id ASC', [$id]);
        $prescriptions = Database::fetchAll(
            'SELECT pr.*, pi.medicine_name, pi.dosage, pi.frequency, pi.duration, pi.before_after_food, pi.instructions
             FROM prescriptions pr
             LEFT JOIN prescription_items pi ON pi.prescription_id = pr.id
             WHERE pr.visit_id = ? AND pr.deleted_at IS NULL',
            [$id]
        );
        $treatments = Database::fetchAll(
            'SELECT ptp.*, tm.name AS treatment_name
             FROM patient_treatment_plans ptp
             LEFT JOIN treatment_masters tm ON tm.id = ptp.treatment_master_id
             WHERE ptp.visit_id = ? AND ptp.deleted_at IS NULL',
            [$id]
        );

        $this->view('modules/visits/print', [
            'title' => 'Dental Clinical Report - ' . $visit['visit_code'],
            'visit' => $visit,
            'examinations' => $examinations,
            'prescriptions' => $prescriptions,
            'treatments' => $treatments,
        ], 'layouts/print');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'patient_visits', $id, 'visits');
    }

    private function findVisit(string $id): array
    {
        $visit = Database::fetch(
            'SELECT v.*, 
                    p.name AS patient_name, p.patient_code, p.age, p.gender, p.mobile AS patient_mobile, p.email AS patient_email, p.address AS patient_address, p.blood_group, p.allergies, p.medical_history, p.existing_conditions, p.current_medicines,
                    d.name AS doctor_name, d.doctor_code, d.qualification, d.specialization, d.registration_number, d.mobile AS doctor_mobile, d.email AS doctor_email,
                    a.treatment_master_id
             FROM patient_visits v 
             INNER JOIN patients p ON p.id = v.patient_id 
             INNER JOIN doctors d ON d.id = v.doctor_id
             LEFT JOIN appointments a ON a.id = v.appointment_id
             WHERE v.id = ? AND v.deleted_at IS NULL',
            [$id]
        );
        if (!$visit) {
            $this->jsonError('Visit not found.', null, 404);
        }
        return $visit;
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('patient_visits', $id, 'Visit');
        $payload = [
            'chief_complaint' => $request->input('chief_complaint'),
            'symptoms' => $request->input('symptoms'),
            'clinical_examination' => $request->input('clinical_examination'),
            'diagnosis' => $request->input('diagnosis'),
            'doctor_notes' => $request->input('doctor_notes'),
            'recommended_treatment' => $request->input('recommended_treatment'),
            'follow_up_required' => (int) ($request->input('follow_up_required') ? 1 : 0),
            'follow_up_date' => $request->input('follow_up_date') ?: null,
        ];
        $this->updateWithTimestamp('patient_visits', $payload, (int) $id);
        $this->audit('visits', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Visit details updated successfully.', 'visits/' . $id);
    }

    public function complete(Request $request, string $id): void
    {
        $visit = $this->requireRow('patient_visits', $id, 'Visit');
        Database::update('patient_visits', ['status' => 'completed', 'updated_at' => $this->now()], 'id = :_id', ['_id' => (int) $id]);
        if ($visit['appointment_id']) {
            Database::update('appointments', ['status' => 'completed', 'updated_at' => $this->now()], 'id = :_id', ['_id' => (int) $visit['appointment_id']]);
        }
        $this->audit('visits', 'complete', (int) $id, $visit, ['status' => 'completed']);
        $this->finish($request, 'Visit completed successfully.', 'visits/' . $id);
    }
}
