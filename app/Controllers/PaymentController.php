<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class PaymentController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/payments/index', ['title' => 'Payments', 'pageTitle' => 'Payments']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'payments pay',
            'joins' => ['INNER JOIN bills b ON b.id = pay.bill_id', 'INNER JOIN patients p ON p.id = pay.patient_id'],
            'columns' => ['pay.id', 'pay.receipt_number', 'pay.payment_date', 'b.bill_number', 'p.name AS patient_name', 'pay.amount', 'pay.payment_mode', 'pay.status'],
            'searchable' => ['pay.receipt_number', 'b.bill_number', 'p.name', 'pay.payment_mode', 'pay.status'],
            'orderable' => [0 => 'pay.id', 1 => 'pay.receipt_number', 2 => 'pay.payment_date', 5 => 'pay.amount'],
            'defaultOrder' => ['pay.id', 'DESC'],
            'where' => ['pay.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['payment_date'] = format_date($row['payment_date'] ?? null);
                $row['amount'] = format_money($row['amount'] ?? 0);
                $row['status_badge'] = status_badge($row['status'] ?? '');
                return $row;
            },
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['bill_id' => 'required', 'payment_date' => 'required', 'amount' => 'required|numeric']);
        try {
            Database::beginTransaction();
            $bill = Database::fetch('SELECT * FROM bills WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$data['bill_id']]);
            if (!$bill) {
                throw new \RuntimeException('Bill not found.');
            }
            $amount = $this->money($data['amount']);
            if ($amount <= 0) {
                throw new \RuntimeException('Payment amount must be greater than zero.');
            }
            $id = Database::insert('payments', [
                'receipt_number' => $this->nextCode('payments', 'receipt_number', 'RCP'),
                'bill_id' => (int) $bill['id'],
                'patient_id' => (int) $bill['patient_id'],
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_mode' => $request->input('payment_mode') ?: 'Cash',
                'transaction_reference' => $request->input('transaction_reference'),
                'received_by' => $this->currentUserId(),
                'remarks' => $request->input('remarks'),
                'status' => $request->input('status') ?: 'completed',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
            $paid = $this->money($bill['paid_amount'] + $amount);
            $pending = max(0, $this->money($bill['net_amount']) - $paid);
            Database::update('bills', [
                'paid_amount' => $paid,
                'pending_amount' => $pending,
                'status' => $pending <= 0 ? 'paid' : 'partial',
                'updated_at' => $this->now(),
            ], 'id = :_id', ['_id' => (int) $bill['id']]);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            $this->jsonError($e->getMessage());
        }
        $this->audit('payments', 'create', $id, null, ['bill_id' => $data['bill_id'], 'amount' => $amount]);
        // After payment, return to billing grid.
        $this->finish($request, 'Payment recorded successfully.', 'billing', ['id' => $id]);
    }
}
