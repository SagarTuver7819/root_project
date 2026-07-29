<?php

namespace App\Controllers;

use App\Core\DataTable;
use App\Core\Request;

class TreatmentMasterController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/treatment-masters/index', ['title' => 'Treatment Masters', 'pageTitle' => 'Treatment Masters']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'treatment_masters tm',
            'columns' => ['tm.id', 'tm.name', 'tm.category', 'tm.default_price', 'tm.estimated_sessions', 'tm.is_active'],
            'searchable' => ['tm.name', 'tm.category', 'tm.description'],
            'orderable' => [0 => 'tm.id', 1 => 'tm.name', 3 => 'tm.default_price'],
            'defaultOrder' => ['tm.id', 'DESC'],
            'where' => ['tm.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('treatment_masters', 'treatment-masters', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/treatment-masters/form', ['title' => 'Add Treatment', 'pageTitle' => 'Add Treatment', 'treatment' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $id = $this->insertWithTimestamps('treatment_masters', $payload);
        $this->audit('treatment_masters', 'create', $id, null, $payload);
        $this->finish($request, 'Treatment created successfully.', 'treatment-masters', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/treatment-masters/form', ['title' => 'Edit Treatment', 'pageTitle' => 'Edit Treatment', 'treatment' => $this->requireRow('treatment_masters', $id, 'Treatment')]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('treatment_masters', $id, 'Treatment');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('treatment_masters', $payload, (int) $id);
        $this->audit('treatment_masters', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Treatment updated successfully.', 'treatment-masters');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'treatment_masters', $id, 'treatment_masters');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'category' => $request->input('category'),
            'default_price' => $this->money($request->input('default_price')),
            'estimated_sessions' => (int) ($request->input('estimated_sessions') ?: 1),
            'description' => $request->input('description'),
            'is_active' => $this->activeValue($request),
        ];
    }
}
