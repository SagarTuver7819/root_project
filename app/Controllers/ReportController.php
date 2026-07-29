<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class ReportController extends \App\Core\Controller
{
    public function index(Request $request): void
    {
        $this->view('modules/reports/index', [
            'title' => 'Reports',
            'pageTitle' => 'Reports',
            'types' => ['appointments', 'revenue', 'outstanding', 'visits', 'inventory'],
        ]);
    }

    public function data(Request $request): void
    {
        $type = (string) $request->input('type', 'appointments');
        $from = (string) $request->input('date_from', date('Y-m-01'));
        $to = (string) $request->input('date_to', date('Y-m-d'));

        $data = match ($type) {
            'revenue' => Database::fetchAll('SELECT payment_date AS date, SUM(amount) AS total FROM payments WHERE deleted_at IS NULL AND status = ? AND payment_date BETWEEN ? AND ? GROUP BY payment_date ORDER BY payment_date', ['completed', $from, $to]),
            'outstanding' => Database::fetchAll('SELECT b.bill_number, p.name AS patient_name, b.net_amount, b.paid_amount, b.pending_amount, b.status FROM bills b INNER JOIN patients p ON p.id = b.patient_id WHERE b.deleted_at IS NULL AND b.pending_amount > 0 ORDER BY b.pending_amount DESC'),
            'visits' => Database::fetchAll('SELECT v.visit_date AS date, d.name AS doctor_name, COUNT(*) AS total FROM patient_visits v INNER JOIN doctors d ON d.id = v.doctor_id WHERE v.deleted_at IS NULL AND v.visit_date BETWEEN ? AND ? GROUP BY v.visit_date, d.id ORDER BY v.visit_date', [$from, $to]),
            'inventory' => Database::fetchAll('SELECT item_code, name, current_stock, minimum_stock, unit FROM inventory_items WHERE deleted_at IS NULL AND current_stock <= minimum_stock ORDER BY current_stock ASC'),
            default => Database::fetchAll('SELECT a.appointment_date AS date, a.status, COUNT(*) AS total FROM appointments a WHERE a.deleted_at IS NULL AND a.appointment_date BETWEEN ? AND ? GROUP BY a.appointment_date, a.status ORDER BY a.appointment_date', [$from, $to]),
        };

        Response::json(['type' => $type, 'date_from' => $from, 'date_to' => $to, 'data' => $data]);
    }
}
