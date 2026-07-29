<?php

namespace App\Controllers;

use App\Core\DataTable;
use App\Core\Request;

class SupplierController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/suppliers/index', ['title' => 'Suppliers', 'pageTitle' => 'Suppliers']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'suppliers s',
            'columns' => ['s.id', 's.name', 's.contact_person', 's.mobile', 's.email', 's.gst_number', 's.is_active'],
            'searchable' => ['s.name', 's.contact_person', 's.mobile', 's.email', 's.gst_number'],
            'orderable' => [0 => 's.id', 1 => 's.name'],
            'defaultOrder' => ['s.id', 'DESC'],
            'where' => ['s.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('suppliers', 'suppliers', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/suppliers/form', ['title' => 'Add Supplier', 'pageTitle' => 'Add Supplier', 'supplier' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $id = $this->insertWithTimestamps('suppliers', $payload);
        $this->audit('suppliers', 'create', $id, null, $payload);
        $this->finish($request, 'Supplier created successfully.', 'suppliers', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/suppliers/form', ['title' => 'Edit Supplier', 'pageTitle' => 'Edit Supplier', 'supplier' => $this->requireRow('suppliers', $id, 'Supplier')]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('suppliers', $id, 'Supplier');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $this->updateWithTimestamp('suppliers', $payload, (int) $id);
        $this->audit('suppliers', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Supplier updated successfully.', 'suppliers');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'suppliers', $id, 'suppliers');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'contact_person' => $request->input('contact_person'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'gst_number' => $request->input('gst_number'),
            'remarks' => $request->input('remarks'),
            'is_active' => $this->activeValue($request),
        ];
    }
}
