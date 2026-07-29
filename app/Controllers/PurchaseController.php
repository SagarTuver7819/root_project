<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class PurchaseController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/purchases/index', ['title' => 'Purchases', 'pageTitle' => 'Purchases']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'purchases pu',
            'joins' => ['INNER JOIN suppliers s ON s.id = pu.supplier_id'],
            'columns' => ['pu.id', 'pu.purchase_number', 'pu.purchase_date', 's.name AS supplier_name', 'pu.invoice_number', 'pu.total', 'pu.status'],
            'searchable' => ['pu.purchase_number', 's.name', 'pu.invoice_number', 'pu.status'],
            'orderable' => [0 => 'pu.id', 1 => 'pu.purchase_number', 2 => 'pu.purchase_date', 5 => 'pu.total'],
            'defaultOrder' => ['pu.id', 'DESC'],
            'where' => ['pu.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['purchase_date'] = format_date($row['purchase_date'] ?? null);
                $row['total'] = format_money($row['total'] ?? 0);
                $row['status_badge'] = status_badge($row['status'] ?? '');
                $html = '<div class="table-actions">';
                if (can('purchases.view')) {
                    $html .= '<a href="' . app_url('purchases/' . $row['id']) . '" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>';
                }
                if (can('purchases.delete')) {
                    $html .= '<button type="button" class="btn btn-sm btn-light text-danger btn-delete" data-url="' . app_url('purchases/' . $row['id'] . '/delete') . '" title="Delete"><i class="bi bi-trash"></i></button>';
                }
                $row['actions'] = $html . '</div>';
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/purchases/form', ['title' => 'Create Purchase', 'pageTitle' => 'Create Purchase', 'purchase' => null, 'suppliers' => $this->options('suppliers', 'name', 'is_active = 1'), 'items' => $this->options('inventory_items', 'name', 'is_active = 1')]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['supplier_id' => 'required', 'purchase_date' => 'required']);
        $payload = $this->payload($request, $data);
        $payload['purchase_number'] = $this->nextCode('purchases', 'purchase_number', 'PUR');
        $payload['created_by'] = $this->currentUserId();
        try {
            Database::beginTransaction();
            $id = $this->insertWithTimestamps('purchases', $payload);
            $this->insertItems($request, $id, $payload['status'] === 'confirmed');
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            $this->jsonError($e->getMessage());
        }
        $this->audit('purchases', 'create', $id, null, $payload);
        $this->finish($request, 'Purchase created successfully.', 'purchases', ['id' => $id]);
    }

    public function show(Request $request, string $id): void
    {
        $purchase = Database::fetch('SELECT pu.*, s.name AS supplier_name FROM purchases pu INNER JOIN suppliers s ON s.id = pu.supplier_id WHERE pu.id = ? AND pu.deleted_at IS NULL', [$id]);
        if (!$purchase) {
            $this->jsonError('Purchase not found.', null, 404);
        }
        $this->view('modules/purchases/show', ['title' => $purchase['purchase_number'], 'pageTitle' => 'Purchase Details', 'purchase' => $purchase, 'items' => Database::fetchAll('SELECT pi.*, i.name AS item_name FROM purchase_items pi INNER JOIN inventory_items i ON i.id = pi.item_id WHERE pi.purchase_id = ?', [$id])]);
    }

    public function confirm(Request $request, string $id): void
    {
        $purchase = $this->requireRow('purchases', $id, 'Purchase');
        if ($purchase['status'] === 'confirmed') {
            $this->finish($request, 'Purchase is already confirmed.', 'purchases/' . $id);
        }
        try {
            Database::beginTransaction();
            $items = Database::fetchAll('SELECT * FROM purchase_items WHERE purchase_id = ?', [$id]);
            foreach ($items as $item) {
                $this->increaseStock($item, (int) $id);
            }
            Database::update('purchases', ['status' => 'confirmed', 'updated_at' => $this->now()], 'id = :_id', ['_id' => (int) $id]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            $this->jsonError($e->getMessage());
        }
        $this->audit('purchases', 'confirm', (int) $id, $purchase, ['status' => 'confirmed']);
        $this->finish($request, 'Purchase confirmed and stock updated.', 'purchases/' . $id);
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'purchases', $id, 'purchases');
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'supplier_id' => (int) $data['supplier_id'],
            'purchase_date' => $data['purchase_date'],
            'invoice_number' => $request->input('invoice_number'),
            'invoice_date' => $request->input('invoice_date') ?: null,
            'subtotal' => $this->money($request->input('subtotal')),
            'discount' => $this->money($request->input('discount')),
            'tax' => $this->money($request->input('tax')),
            'total' => $this->money($request->input('total')),
            'status' => $request->input('status') ?: 'draft',
            'notes' => $request->input('notes'),
        ];
    }

    private function insertItems(Request $request, int $purchaseId, bool $confirmed): void
    {
        foreach ((array) $request->input('items', []) as $item) {
            if (empty($item['item_id'])) {
                continue;
            }
            $row = [
                'purchase_id' => $purchaseId,
                'item_id' => (int) $item['item_id'],
                'quantity' => $this->money($item['quantity'] ?? 0),
                'rate' => $this->money($item['rate'] ?? 0),
                'discount' => $this->money($item['discount'] ?? 0),
                'tax' => $this->money($item['tax'] ?? 0),
                'total' => $this->money($item['total'] ?? 0),
            ];
            Database::insert('purchase_items', $row);
            if ($confirmed) {
                $this->increaseStock($row, $purchaseId);
            }
        }
    }

    private function increaseStock(array $item, int $purchaseId): void
    {
        Database::query('UPDATE inventory_items SET current_stock = current_stock + ?, updated_at = ? WHERE id = ?', [$item['quantity'], $this->now(), $item['item_id']]);
        Database::insert('inventory_transactions', ['item_id' => (int) $item['item_id'], 'transaction_type' => 'purchase', 'quantity' => $item['quantity'], 'reference_type' => 'purchase', 'reference_id' => $purchaseId, 'remarks' => 'Purchase stock in', 'created_by' => $this->currentUserId(), 'created_at' => $this->now()]);
    }
}
