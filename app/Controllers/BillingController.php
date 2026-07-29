<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;
use App\Services\ApprovalService;
use App\Services\BookingService;

class BillingController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/billing/index', ['title' => 'Billing', 'pageTitle' => 'Bills']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'bills b',
            'joins' => ['INNER JOIN patients p ON p.id = b.patient_id', 'LEFT JOIN doctors d ON d.id = b.doctor_id'],
            'columns' => ['b.id', 'b.bill_number', 'b.billing_date', 'p.name AS patient_name', 'd.name AS doctor_name', 'b.net_amount', 'b.paid_amount', 'b.pending_amount', 'b.status'],
            'searchable' => ['b.bill_number', 'p.name', 'd.name', 'b.status'],
            'orderable' => [0 => 'b.id', 1 => 'b.bill_number', 2 => 'b.billing_date', 5 => 'b.net_amount'],
            'defaultOrder' => ['b.id', 'DESC'],
            'where' => ['b.deleted_at IS NULL'],
            'filters' => function (Request $req, array &$where, array &$bindings) {
                if ($req->input('outstanding_only')) {
                    $where[] = 'b.pending_amount > 0';
                    $where[] = "b.status IN ('pending','partial')";
                }
                if ($req->input('status')) {
                    $where[] = 'b.status = ?';
                    $bindings[] = $req->input('status');
                }
            },
            'rowFormatter' => function (array $row) {
                $row['billing_date'] = format_date($row['billing_date'] ?? null);
                $row['net_amount'] = format_money($row['net_amount'] ?? 0);
                $row['paid_amount'] = format_money($row['paid_amount'] ?? 0);
                $row['pending_amount'] = format_money($row['pending_amount'] ?? 0);
                $row['doctor_name'] = doctor_label($row['doctor_name'] ?? null);
                $status = strtolower((string) ($row['status'] ?? 'pending'));
                if (can('billing.edit')) {
                    $options = [
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ];
                    $statusClass = 'status-' . str_replace('_', '-', $status);
                    $html = '<select class="form-select form-select-sm bill-status-select ' . e($statusClass) . '" data-id="' . e((string) $row['id']) . '" data-current="' . e($status) . '">';
                    foreach ($options as $value => $label) {
                        $selected = $status === $value ? ' selected' : '';
                        $html .= '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
                    }
                    $html .= '</select>';
                    $row['status_badge'] = $html;
                } else {
                    $row['status_badge'] = status_badge($status);
                }
                $row['actions'] = $this->actions(
                    'billing',
                    'billing',
                    $row['id'],
                    true,
                    '<a href="' . app_url('billing/' . $row['id'] . '/invoice') . '" class="btn btn-action btn-action-primary" target="_blank" title="View / Print Invoice"><i class="bi bi-printer"></i></a>'
                );
                return $row;
            },
        ]);
    }

    public function outstanding(Request $request): void
    {
        $this->view('modules/billing/outstanding', ['title' => 'Outstanding Bills', 'pageTitle' => 'Outstanding Bills']);
    }

    public function create(Request $request): void
    {
        BookingService::ensureSchema();
        $this->view('modules/billing/form', $this->formData(null, 'Create Bill'));
    }

    public function store(Request $request): void
    {
        BookingService::ensureSchema();
        $data = $this->validate($request, ['patient_id' => 'required', 'billing_date' => 'required']);
        $payload = $this->payload($request, $data);
        $payload['bill_number'] = $this->nextCode('bills', 'bill_number', 'BILL');
        $payload['created_by'] = $this->currentUserId();
        $id = $this->insertWithTimestamps('bills', $payload);
        if ($payload['discount'] > 0) {
            ApprovalService::request('billing', $id, 'discount', 'Bill discount of ' . $payload['discount'], $payload);
        }
        $this->audit('billing', 'create', $id, null, $payload);
        // New unpaid bills open details so payment can be added; paid/zero go to grid.
        $redirect = ((float) ($payload['pending_amount'] ?? 0) > 0) ? ('billing/' . $id) : 'billing';
        $this->finish($request, 'Bill created successfully.', $redirect, ['id' => $id]);
    }

    public function show(Request $request, string $id): void
    {
        $bill = Database::fetch(
            'SELECT b.*, p.name AS patient_name, p.patient_code, p.mobile, p.email, p.address,
                    d.name AS doctor_name
             FROM bills b
             INNER JOIN patients p ON p.id = b.patient_id
             LEFT JOIN doctors d ON d.id = b.doctor_id
             WHERE b.id = ? AND b.deleted_at IS NULL',
            [$id]
        );
        if (!$bill) {
            $this->jsonError('Bill not found.', null, 404);
        }
        $this->view('modules/billing/show', [
            'title' => $bill['bill_number'],
            'pageTitle' => 'Bill Details',
            'bill' => $bill,
            'payments' => Database::fetchAll(
                'SELECT * FROM payments WHERE bill_id = ? AND deleted_at IS NULL ORDER BY payment_date DESC, id DESC',
                [$id]
            ),
        ]);
    }

    public function invoice(Request $request, string $id): void
    {
        $bill = Database::fetch(
            'SELECT b.*,
                    p.name AS patient_name, p.patient_code, p.mobile, p.email, p.address, p.age, p.gender,
                    d.name AS doctor_name, d.qualification, d.registration_number,
                    tm.name AS treatment_name, tm.default_price AS treatment_price,
                    ptp.plan_code
             FROM bills b
             INNER JOIN patients p ON p.id = b.patient_id
             LEFT JOIN doctors d ON d.id = b.doctor_id
             LEFT JOIN patient_treatment_plans ptp ON ptp.id = b.treatment_plan_id
             LEFT JOIN treatment_masters tm ON tm.id = ptp.treatment_master_id
             WHERE b.id = ? AND b.deleted_at IS NULL',
            [$id]
        );
        if (!$bill) {
            $this->jsonError('Bill not found.', null, 404);
        }

        $payments = Database::fetchAll(
            'SELECT * FROM payments WHERE bill_id = ? AND deleted_at IS NULL ORDER BY payment_date ASC, id ASC',
            [$id]
        );

        $autoPrint = $request->query('print') === '1' || $request->query('autoprint') === '1';

        $this->view('modules/billing/invoice', [
            'title' => 'Invoice ' . ($bill['bill_number'] ?? ''),
            'bill' => $bill,
            'payments' => $payments,
            'autoPrint' => $autoPrint,
        ], null);
    }

    public function edit(Request $request, string $id): void
    {
        BookingService::ensureSchema();
        $this->view('modules/billing/form', $this->formData($this->requireRow('bills', $id, 'Bill'), 'Edit Bill'));
    }

    public function update(Request $request, string $id): void
    {
        BookingService::ensureSchema();
        $old = $this->requireRow('bills', $id, 'Bill');
        $data = $this->validate($request, ['patient_id' => 'required', 'billing_date' => 'required']);
        $payload = $this->payload($request, $data, (float) $old['paid_amount'], (int) $id);
        $this->updateWithTimestamp('bills', $payload, (int) $id);
        if ($payload['discount'] > (float) $old['discount']) {
            ApprovalService::request('billing', (int) $id, 'discount', 'Discount increased to ' . $payload['discount'], $payload);
        }
        $this->audit('billing', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Bill updated successfully.', 'billing');
    }

    public function bookingStatus(Request $request): void
    {
        BookingService::ensureSchema();
        $patientId = (int) $request->query('patient_id', 0);
        $date = $request->query('billing_date') ?: date('Y-m-d');
        $status = BookingService::statusForPatient($patientId > 0 ? $patientId : null, $date);
        $this->jsonSuccess('OK', [
            'fee' => BookingService::amount(),
            'validity_months' => BookingService::validityMonths(),
            'status' => $status,
        ]);
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'bills', $id, 'billing');
    }

    public function changeStatus(Request $request, string $id): void
    {
        $data = $this->validate($request, ['status' => 'required']);
        $status = strtolower((string) $data['status']);
        $allowed = ['pending', 'partial', 'paid', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            $this->jsonError('Invalid bill status.');
        }

        $bill = $this->requireRow('bills', $id, 'Bill');
        $net = $this->money($bill['net_amount']);
        $paid = $this->money($bill['paid_amount']);

        $payload = [
            'status' => $status,
            'updated_at' => $this->now(),
        ];

        if ($status === 'paid') {
            $payload['paid_amount'] = $net;
            $payload['pending_amount'] = 0;
        } elseif ($status === 'pending') {
            $payload['paid_amount'] = 0;
            $payload['pending_amount'] = $net;
        } elseif ($status === 'partial') {
            if ($paid <= 0 || $paid >= $net) {
                $paid = $this->money($net / 2);
            }
            $payload['paid_amount'] = $paid;
            $payload['pending_amount'] = max(0, $this->money($net - $paid));
        }

        Database::update('bills', $payload, 'id = :_id', ['_id' => (int) $id]);
        $this->audit('billing', 'status', (int) $id, ['status' => $bill['status']], $payload);
        $this->finish($request, 'Bill status updated successfully.', 'billing');
    }

    private function payload(Request $request, array $data, float $paid = 0, ?int $excludeBillId = null): array
    {
        BookingService::ensureSchema();
        $treatment = $this->money($request->input('treatment_amount', $request->input('gross_amount')));
        $discount = $this->money($request->input('discount'));
        $billingDate = $data['billing_date'];
        $patientId = (int) $data['patient_id'];

        $status = BookingService::statusForPatient($patientId, $billingDate);
        // On create: charge booking only when case is due. On edit: keep bill's booking unless cleared.
        $booking = 0.0;
        if ($excludeBillId) {
            $existing = Database::fetch('SELECT booking_amount FROM bills WHERE id = ?', [$excludeBillId]);
            $existingBooking = $this->money($existing['booking_amount'] ?? 0);
            if ($request->input('booking_amount') !== null && $request->input('booking_amount') !== '') {
                $booking = $this->money($request->input('booking_amount'));
            } else {
                $booking = $existingBooking > 0 ? $existingBooking : ($status['due'] ? $status['amount'] : 0.0);
            }
        } else {
            $booking = $status['due'] ? $status['amount'] : 0.0;
        }

        $gross = max(0, $treatment + $booking);
        $net = max(0, $gross - $discount);
        $pending = max(0, $net - $paid);

        $notes = trim((string) $request->input('notes'));
        if ($booking > 0 && stripos($notes, 'booking') === false) {
            $caseLabel = ($status['case_type'] ?? '') === 'renewal' ? 'New case booking' : 'First-visit booking';
            $bookingLine = $caseLabel . ' ₹' . number_format($booking, 2) . ' (valid ' . BookingService::validityMonths() . ' months)';
            $notes = $notes !== '' ? ($notes . "\n" . $bookingLine) : $bookingLine;
        }

        return [
            'patient_id' => $patientId,
            'treatment_plan_id' => $request->input('treatment_plan_id') ?: null,
            'doctor_id' => $request->input('doctor_id') ?: null,
            'gross_amount' => $gross,
            'booking_amount' => $booking,
            'discount' => $discount,
            'net_amount' => $net,
            'paid_amount' => $paid,
            'pending_amount' => $pending,
            'billing_date' => $billingDate,
            'status' => $pending <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
            'notes' => $notes !== '' ? $notes : null,
        ];
    }

    private function formData(?array $bill, string $title): array
    {
        BookingService::ensureSchema();
        $treatments = Database::fetchAll(
            'SELECT id, name, default_price
             FROM treatment_masters
             WHERE deleted_at IS NULL AND is_active = 1
             ORDER BY name ASC'
        );
        $treatmentPlans = Database::fetchAll(
            'SELECT ptp.*, tm.name AS treatment_name, tm.default_price
             FROM patient_treatment_plans ptp 
             LEFT JOIN treatment_masters tm ON tm.id = ptp.treatment_master_id 
             WHERE ptp.deleted_at IS NULL ORDER BY ptp.id DESC'
        );

        $prefillTreatmentId = $_GET['treatment_master_id'] ?? null;
        $prefillAmount = null;
        if ($prefillTreatmentId && empty($bill['id'])) {
            foreach ($treatments as $tm) {
                if ((string) $tm['id'] === (string) $prefillTreatmentId) {
                    $prefillAmount = $tm['default_price'];
                    break;
                }
            }
        }

        $patientId = (int) ($bill['patient_id'] ?? $_GET['patient_id'] ?? 0);
        $bookingStatus = BookingService::statusForPatient($patientId > 0 ? $patientId : null, $bill['billing_date'] ?? date('Y-m-d'));
        $existingBooking = (float) ($bill['booking_amount'] ?? 0);
        if (!empty($bill['id']) && $existingBooking > 0) {
            $bookingStatus['due'] = true;
            $bookingStatus['amount'] = $existingBooking;
            $bookingStatus['message'] = 'Booking ₹' . number_format($existingBooking, 0) . ' on this bill';
            $bookingStatus['label'] = 'Booking on bill ₹' . number_format($existingBooking, 0);
        }

        $treatmentAmount = null;
        if (!empty($bill['id'])) {
            $treatmentAmount = max(0, (float) ($bill['gross_amount'] ?? 0) - (float) ($bill['booking_amount'] ?? 0));
        } elseif ($prefillAmount !== null) {
            $treatmentAmount = (float) $prefillAmount;
        }

        return [
            'title' => $title,
            'pageTitle' => $title,
            'bill' => $bill,
            'patients' => $this->options('patients', 'name', 'is_active = 1'),
            'doctors' => $this->options('doctors', 'name', 'is_active = 1'),
            'treatments' => $treatments,
            'treatmentPlans' => $treatmentPlans,
            'prefillAmount' => $prefillAmount,
            'treatmentAmount' => $treatmentAmount,
            'bookingStatus' => $bookingStatus,
            'bookingFee' => BookingService::amount(),
            'bookingValidityMonths' => BookingService::validityMonths(),
        ];
    }
}
