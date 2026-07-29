<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class InventoryController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/inventory/index', ['title' => 'Inventory', 'pageTitle' => 'Inventory Items']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'inventory_items i',
            'joins' => ['LEFT JOIN inventory_categories c ON c.id = i.category_id'],
            'columns' => ['i.id', 'i.item_code', 'i.name', 'c.name AS category_name', 'i.brand', 'i.unit', 'i.current_stock', 'i.minimum_stock', 'i.purchase_rate', 'i.is_active'],
            'searchable' => ['i.item_code', 'i.name', 'c.name', 'i.brand', 'i.batch_no'],
            'orderable' => [0 => 'i.id', 1 => 'i.item_code', 2 => 'i.name', 6 => 'i.current_stock'],
            'defaultOrder' => ['i.id', 'DESC'],
            'where' => ['i.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['low_stock'] = (float) $row['current_stock'] <= (float) $row['minimum_stock'];
                $row['actions'] = $this->actions('inventory', 'inventory', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/inventory/form', $this->formData(null, 'Add Inventory Item'));
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        $payload['item_code'] = $this->nextCode('inventory_items', 'item_code', 'ITM');
        $id = $this->insertWithTimestamps('inventory_items', $payload);
        $this->audit('inventory', 'create', $id, null, $payload);
        $this->finish($request, 'Inventory item created successfully.', 'inventory', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/inventory/form', $this->formData($this->requireRow('inventory_items', $id, 'Inventory item'), 'Edit Inventory Item'));
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('inventory_items', $id, 'Inventory item');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = $this->payload($request, $data);
        unset($payload['current_stock']);
        $this->updateWithTimestamp('inventory_items', $payload, (int) $id);
        $this->audit('inventory', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Inventory item updated successfully.', 'inventory');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'inventory_items', $id, 'inventory');
    }

    public function adjustStock(Request $request, string $id): void
    {
        $item = $this->requireRow('inventory_items', $id, 'Inventory item');
        $data = $this->validate($request, ['transaction_type' => 'required', 'quantity' => 'required|numeric']);
        $qty = $this->money($data['quantity']);
        $sign = in_array($data['transaction_type'], ['out', 'issue', 'damage', 'expired'], true) ? -1 : 1;
        $newStock = $this->money($item['current_stock'] + ($qty * $sign));
        if ($newStock < 0) {
            $this->jsonError('Stock cannot become negative.');
        }
        Database::update('inventory_items', ['current_stock' => $newStock, 'updated_at' => $this->now()], 'id = :_id', ['_id' => (int) $id]);
        Database::insert('inventory_transactions', [
            'item_id' => (int) $id,
            'transaction_type' => $data['transaction_type'],
            'quantity' => $qty * $sign,
            'reference_type' => $request->input('reference_type'),
            'reference_id' => $request->input('reference_id') ?: null,
            'remarks' => $request->input('remarks'),
            'created_by' => $this->currentUserId(),
            'created_at' => $this->now(),
        ]);
        $this->audit('inventory', 'stock_adjust', (int) $id, ['stock' => $item['current_stock']], ['stock' => $newStock]);
        $this->finish($request, 'Stock adjusted successfully.', 'inventory');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'category_id' => $request->input('category_id') ?: null,
            'brand' => $request->input('brand'),
            'unit' => $request->input('unit') ?: 'pcs',
            'current_stock' => $this->money($request->input('current_stock')),
            'minimum_stock' => $this->money($request->input('minimum_stock')),
            'purchase_rate' => $this->money($request->input('purchase_rate')),
            'batch_no' => $request->input('batch_no'),
            'expiry_date' => $request->input('expiry_date') ?: null,
            'is_active' => $this->activeValue($request),
        ];
    }

    private function formData(?array $item, string $title): array
    {
        return ['title' => $title, 'pageTitle' => $title, 'item' => $item, 'categories' => Database::fetchAll('SELECT id, name FROM inventory_categories WHERE is_active = 1 ORDER BY name')];
    }
}
