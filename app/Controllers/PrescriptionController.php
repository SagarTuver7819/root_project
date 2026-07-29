<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class PrescriptionController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/prescriptions/index', ['title' => 'Prescriptions', 'pageTitle' => 'Prescriptions']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'prescriptions pr',
            'joins' => ['INNER JOIN patients p ON p.id = pr.patient_id', 'INNER JOIN doctors d ON d.id = pr.doctor_id'],
            'columns' => ['pr.id', 'pr.prescription_number', 'pr.prescription_date', 'p.name AS patient_name', 'd.name AS doctor_name', 'pr.diagnosis', 'pr.follow_up_date'],
            'searchable' => ['pr.prescription_number', 'p.name', 'd.name', 'pr.diagnosis'],
            'orderable' => [0 => 'pr.id', 1 => 'pr.prescription_number', 2 => 'pr.prescription_date'],
            'defaultOrder' => ['pr.id', 'DESC'],
            'where' => ['pr.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['prescription_date'] = format_date($row['prescription_date'] ?? null);
                $row['follow_up_date'] = format_date($row['follow_up_date'] ?? null);
                $row['doctor_name'] = doctor_label($row['doctor_name'] ?? null);
                $row['actions'] = $this->actions(
                    'prescriptions',
                    'prescriptions',
                    $row['id'],
                    true,
                    '<a href="' . app_url('prescriptions/' . $row['id'] . '/print') . '" class="btn btn-action btn-action-primary" target="_blank" title="Print"><i class="bi bi-printer"></i></a>'
                );
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/prescriptions/form', $this->formData(null, 'Add Prescription'));
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['patient_id' => 'required', 'doctor_id' => 'required', 'prescription_date' => 'required']);
        try {
            Database::beginTransaction();
            $payload = $this->payload($request, $data);
            $payload['prescription_number'] = $this->nextCode('prescriptions', 'prescription_number', 'RX');
            $id = $this->insertWithTimestamps('prescriptions', $payload);
            $this->syncItems($request, $id);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            $this->jsonError($e->getMessage());
        }
        $this->audit('prescriptions', 'create', $id, null, $payload);
        $this->finish($request, 'Prescription created successfully.', 'prescriptions', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $prescription = $this->requireRow('prescriptions', $id, 'Prescription');
        $data = $this->formData($prescription, 'Edit Prescription');
        $data['items'] = Database::fetchAll('SELECT * FROM prescription_items WHERE prescription_id = ?', [$id]);
        $this->view('modules/prescriptions/form', $data);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('prescriptions', $id, 'Prescription');
        $data = $this->validate($request, ['patient_id' => 'required', 'doctor_id' => 'required', 'prescription_date' => 'required']);
        try {
            Database::beginTransaction();
            $payload = $this->payload($request, $data);
            $this->updateWithTimestamp('prescriptions', $payload, (int) $id);
            Database::query('DELETE FROM prescription_items WHERE prescription_id = ?', [$id]);
            $this->syncItems($request, (int) $id);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            $this->jsonError($e->getMessage());
        }
        $this->audit('prescriptions', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Prescription updated successfully.', 'prescriptions');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'prescriptions', $id, 'prescriptions');
    }

    public function show(Request $request, string $id): void
    {
        $this->print($request, $id);
    }

    public function print(Request $request, string $id): void
    {
        $prescription = Database::fetch('SELECT pr.*, p.name AS patient_name, p.age, p.gender, d.name AS doctor_name FROM prescriptions pr INNER JOIN patients p ON p.id = pr.patient_id INNER JOIN doctors d ON d.id = pr.doctor_id WHERE pr.id = ? AND pr.deleted_at IS NULL', [$id]);
        if (!$prescription) {
            $this->jsonError('Prescription not found.', null, 404);
        }
        $this->view('modules/prescriptions/print', ['title' => 'Prescription', 'prescription' => $prescription, 'items' => Database::fetchAll('SELECT * FROM prescription_items WHERE prescription_id = ?', [$id])], 'layouts/print');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'patient_id' => (int) $data['patient_id'],
            'visit_id' => $request->input('visit_id') ?: null,
            'doctor_id' => (int) $data['doctor_id'],
            'diagnosis' => $request->input('diagnosis'),
            'prescription_date' => $data['prescription_date'],
            'advice' => $request->input('advice'),
            'follow_up_date' => $request->input('follow_up_date') ?: null,
            'notes' => $request->input('notes'),
        ];
    }

    private function syncItems(Request $request, int $prescriptionId): void
    {
        foreach ((array) $request->input('items', []) as $item) {
            if (empty($item['medicine_name']) && empty($item['medicine_id'])) {
                continue;
            }
            $medicineName = $item['medicine_name'] ?? '';
            if (!$medicineName && !empty($item['medicine_id'])) {
                $medicine = Database::fetch('SELECT name FROM medicine_masters WHERE id = ?', [$item['medicine_id']]);
                $medicineName = $medicine['name'] ?? '';
            }
            Database::insert('prescription_items', [
                'prescription_id' => $prescriptionId,
                'medicine_id' => $item['medicine_id'] ?: null,
                'medicine_name' => $medicineName,
                'dosage' => $item['dosage'] ?? null,
                'frequency' => $item['frequency'] ?? null,
                'duration' => $item['duration'] ?? null,
                'before_after_food' => $item['before_after_food'] ?? null,
                'instructions' => $item['instructions'] ?? null,
            ]);
        }
    }

    private function formData(?array $prescription, string $title): array
    {
        return ['title' => $title, 'pageTitle' => $title, 'prescription' => $prescription, 'patients' => $this->options('patients', 'name', 'is_active = 1'), 'doctors' => $this->options('doctors', 'name', 'is_active = 1'), 'medicines' => $this->options('medicine_masters', 'name', 'is_active = 1')];
    }
}
