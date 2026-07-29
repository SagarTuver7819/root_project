<?php

namespace App\Controllers;

use App\Core\DataTable;
use App\Core\Request;

class ReferenceDoctorController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/reference-doctors/index', ['title' => 'Reference Doctors', 'pageTitle' => 'Reference Doctors']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'reference_doctors rd',
            'columns' => ['rd.id', 'rd.ref_code', 'rd.name', 'rd.clinic_hospital', 'rd.mobile', 'rd.specialization', 'rd.is_active'],
            'searchable' => ['rd.ref_code', 'rd.name', 'rd.clinic_hospital', 'rd.mobile', 'rd.email', 'rd.specialization'],
            'orderable' => [0 => 'rd.id', 1 => 'rd.ref_code', 2 => 'rd.name'],
            'defaultOrder' => ['rd.id', 'DESC'],
            'where' => ['rd.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('reference_doctors', 'reference-doctors', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/reference-doctors/form', ['title' => 'Add Reference Doctor', 'pageTitle' => 'Add Reference Doctor', 'referenceDoctor' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $payload['ref_code'] = $this->nextCode('reference_doctors', 'ref_code', 'REF');
        $id = $this->insertWithTimestamps('reference_doctors', $payload);
        $this->audit('reference_doctors', 'create', $id, null, $payload);
        $this->finish($request, 'Reference doctor created successfully.', 'reference-doctors', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/reference-doctors/form', ['title' => 'Edit Reference Doctor', 'pageTitle' => 'Edit Reference Doctor', 'referenceDoctor' => $this->requireRow('reference_doctors', $id, 'Reference doctor')]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('reference_doctors', $id, 'Reference doctor');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('reference_doctors', $payload, (int) $id);
        $this->audit('reference_doctors', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Reference doctor updated successfully.', 'reference-doctors');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'reference_doctors', $id, 'reference_doctors');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'clinic_hospital' => $request->input('clinic_hospital'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'specialization' => $request->input('specialization'),
            'remarks' => $request->input('remarks'),
            'is_active' => $this->activeValue($request),
        ];
    }
}
