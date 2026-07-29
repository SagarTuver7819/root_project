<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class TreatmentPlanController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/treatment-plans/index', ['title' => 'Treatment Plans', 'pageTitle' => 'Treatment Plans']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'patient_treatment_plans tp',
            'joins' => ['INNER JOIN patients p ON p.id = tp.patient_id', 'INNER JOIN doctors d ON d.id = tp.doctor_id', 'INNER JOIN treatment_masters tm ON tm.id = tp.treatment_master_id'],
            'columns' => ['tp.id', 'tp.plan_code', 'p.name AS patient_name', 'd.name AS doctor_name', 'tm.name AS treatment_name', 'tp.tooth_number', 'tp.final_amount', 'tp.status'],
            'searchable' => ['tp.plan_code', 'p.name', 'd.name', 'tm.name', 'tp.tooth_number', 'tp.status'],
            'orderable' => [0 => 'tp.id', 1 => 'tp.plan_code', 6 => 'tp.final_amount'],
            'defaultOrder' => ['tp.id', 'DESC'],
            'where' => ['tp.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['final_amount'] = format_money($row['final_amount'] ?? 0);
                $row['doctor_name'] = doctor_label($row['doctor_name'] ?? null);
                $row['status_badge'] = status_badge($row['status'] ?? '');
                $row['actions'] = $this->actions('treatments', 'treatment-plans', $row['id'], true);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/treatment-plans/form', $this->formData(null, 'Add Treatment Plan'));
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['patient_id' => 'required', 'doctor_id' => 'required', 'treatment_master_id' => 'required']);
        $payload = $this->payload($request, $data);
        $payload['plan_code'] = $this->nextCode('patient_treatment_plans', 'plan_code', 'PLAN');
        $id = $this->insertWithTimestamps('patient_treatment_plans', $payload);
        $this->audit('treatment_plans', 'create', $id, null, $payload);
        $this->finish($request, 'Treatment plan created successfully.', 'treatment-plans', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/treatment-plans/form', $this->formData($this->requireRow('patient_treatment_plans', $id, 'Treatment plan'), 'Edit Treatment Plan'));
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('patient_treatment_plans', $id, 'Treatment plan');
        $data = $this->validate($request, ['patient_id' => 'required', 'doctor_id' => 'required', 'treatment_master_id' => 'required']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('patient_treatment_plans', $payload, (int) $id);
        $this->audit('treatment_plans', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Treatment plan updated successfully.', 'treatment-plans');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'patient_treatment_plans', $id, 'treatment_plans');
    }

    public function show(Request $request, string $id): void
    {
        $plan = $this->findPlan($id);
        $this->view('modules/treatment-plans/show', [
            'title' => $plan['plan_code'],
            'pageTitle' => 'Treatment Plan',
            'plan' => $plan,
            'sessions' => Database::fetchAll('SELECT * FROM treatment_sessions WHERE treatment_plan_id = ? AND deleted_at IS NULL ORDER BY session_number', [$id]),
            'doctors' => $this->options('doctors', 'name', 'is_active = 1'),
        ]);
    }

    public function print(Request $request, string $id): void
    {
        $plan = $this->findPlan($id);
        $this->view('modules/treatment-plans/print', [
            'title' => 'Print ' . $plan['plan_code'],
            'plan' => $plan,
            'sessions' => Database::fetchAll('SELECT * FROM treatment_sessions WHERE treatment_plan_id = ? AND deleted_at IS NULL ORDER BY session_number', [$id]),
        ], 'layouts/print');
    }

    private function findPlan(string $id): array
    {
        $plan = Database::fetch('SELECT tp.*, p.name AS patient_name, d.name AS doctor_name, tm.name AS treatment_name FROM patient_treatment_plans tp INNER JOIN patients p ON p.id = tp.patient_id INNER JOIN doctors d ON d.id = tp.doctor_id INNER JOIN treatment_masters tm ON tm.id = tp.treatment_master_id WHERE tp.id = ? AND tp.deleted_at IS NULL', [$id]);
        if (!$plan) {
            $this->jsonError('Treatment plan not found.', null, 404);
        }
        return $plan;
    }

    public function storeSession(Request $request, string $id): void
    {
        $plan = $this->requireRow('patient_treatment_plans', $id, 'Treatment plan');
        $data = $this->validate($request, ['session_date' => 'required', 'doctor_id' => 'required']);
        $next = (int) (Database::fetch('SELECT COALESCE(MAX(session_number),0) + 1 AS n FROM treatment_sessions WHERE treatment_plan_id = ?', [$id])['n'] ?? 1);
        $payload = [
            'treatment_plan_id' => (int) $id,
            'session_number' => (int) ($request->input('session_number') ?: $next),
            'session_date' => $data['session_date'],
            'doctor_id' => (int) $data['doctor_id'],
            'tooth_number' => $request->input('tooth_number') ?: $plan['tooth_number'],
            'procedure_performed' => $request->input('procedure_performed'),
            'clinical_notes' => $request->input('clinical_notes'),
            'material_used' => $request->input('material_used'),
            'next_session_date' => $request->input('next_session_date') ?: null,
            'status' => $request->input('status') ?: 'completed',
        ];
        $sessionId = $this->insertWithTimestamps('treatment_sessions', $payload);
        $this->audit('treatment_plans', 'session_create', $sessionId, null, $payload);
        $this->finish($request, 'Treatment session saved successfully.', 'treatment-plans/' . $id, ['id' => $sessionId]);
    }

    private function payload(Request $request, array $data): array
    {
        $cost = $this->money($request->input('cost'));
        $discount = $this->money($request->input('discount'));
        return [
            'patient_id' => (int) $data['patient_id'],
            'visit_id' => $request->input('visit_id') ?: null,
            'doctor_id' => (int) $data['doctor_id'],
            'treatment_master_id' => (int) $data['treatment_master_id'],
            'tooth_number' => $request->input('tooth_number'),
            'diagnosis' => $request->input('diagnosis'),
            'description' => $request->input('description'),
            'start_date' => $request->input('start_date') ?: null,
            'estimated_completion' => $request->input('estimated_completion') ?: null,
            'sessions' => (int) ($request->input('sessions') ?: 1),
            'cost' => $cost,
            'discount' => $discount,
            'final_amount' => max(0, $cost - $discount),
            'status' => $request->input('status') ?: 'recommended',
        ];
    }

    private function formData(?array $plan, string $title): array
    {
        return ['title' => $title, 'pageTitle' => $title, 'plan' => $plan, 'patients' => $this->options('patients', 'name', 'is_active = 1'), 'doctors' => $this->options('doctors', 'name', 'is_active = 1'), 'treatments' => $this->options('treatment_masters', 'name', 'is_active = 1')];
    }
}
