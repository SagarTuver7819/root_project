<?php

namespace App\Controllers;

use App\Core\DataTable;
use App\Core\Request;

class MedicineMasterController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/medicine-masters/index', ['title' => 'Medicine Masters', 'pageTitle' => 'Medicine Masters']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'medicine_masters mm',
            'columns' => ['mm.id', 'mm.name', 'mm.generic_name', 'mm.medicine_type', 'mm.default_dosage', 'mm.default_frequency', 'mm.is_active'],
            'searchable' => ['mm.name', 'mm.generic_name', 'mm.medicine_type'],
            'orderable' => [0 => 'mm.id', 1 => 'mm.name', 3 => 'mm.medicine_type'],
            'defaultOrder' => ['mm.id', 'DESC'],
            'where' => ['mm.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('medicine_masters', 'medicines', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/medicine-masters/form', ['title' => 'Add Medicine', 'pageTitle' => 'Add Medicine', 'medicine' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $id = $this->insertWithTimestamps('medicine_masters', $payload);
        $this->audit('medicine_masters', 'create', $id, null, $payload);
        $this->finish($request, 'Medicine created successfully.', 'medicines', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/medicine-masters/form', ['title' => 'Edit Medicine', 'pageTitle' => 'Edit Medicine', 'medicine' => $this->requireRow('medicine_masters', $id, 'Medicine')]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('medicine_masters', $id, 'Medicine');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('medicine_masters', $payload, (int) $id);
        $this->audit('medicine_masters', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Medicine updated successfully.', 'medicines');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'medicine_masters', $id, 'medicine_masters');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'generic_name' => $request->input('generic_name'),
            'medicine_type' => $request->input('medicine_type') ?: 'Tablet',
            'default_dosage' => $request->input('default_dosage'),
            'default_frequency' => $request->input('default_frequency'),
            'default_duration' => $request->input('default_duration'),
            'default_instructions' => $request->input('default_instructions'),
            'is_active' => $this->activeValue($request),
        ];
    }
}
