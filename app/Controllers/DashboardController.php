<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $today = date('Y-m-d');
        $stats = [
            'patients' => (int) (Database::fetch('SELECT COUNT(*) c FROM patients WHERE deleted_at IS NULL')['c'] ?? 0),
            'doctors' => (int) (Database::fetch('SELECT COUNT(*) c FROM doctors WHERE deleted_at IS NULL AND is_active = 1')['c'] ?? 0),
            'appointments_today' => (int) (Database::fetch('SELECT COUNT(*) c FROM appointments WHERE appointment_date = ? AND deleted_at IS NULL', [$today])['c'] ?? 0),
            'visits_today' => (int) (Database::fetch('SELECT COUNT(*) c FROM patient_visits WHERE visit_date = ? AND deleted_at IS NULL', [$today])['c'] ?? 0),
            'pending_payments' => (float) (Database::fetch('SELECT COALESCE(SUM(pending_amount),0) c FROM bills WHERE deleted_at IS NULL AND pending_amount > 0')['c'] ?? 0),
            'revenue_today' => (float) (Database::fetch('SELECT COALESCE(SUM(amount),0) c FROM payments WHERE payment_date = ? AND deleted_at IS NULL AND status = ?', [$today, 'completed'])['c'] ?? 0),
            'low_stock' => (int) (Database::fetch('SELECT COUNT(*) c FROM inventory_items WHERE deleted_at IS NULL AND current_stock <= minimum_stock')['c'] ?? 0),
            'treatments_active' => (int) (Database::fetch("SELECT COUNT(*) c FROM patient_treatment_plans WHERE deleted_at IS NULL AND status IN ('started','in_progress','approved','planned')")['c'] ?? 0),
            'suppliers_count' => (int) (Database::fetch('SELECT COUNT(*) c FROM suppliers WHERE deleted_at IS NULL')['c'] ?? 0),
            'purchases_open' => (int) (Database::fetch("SELECT COUNT(*) c FROM purchases WHERE deleted_at IS NULL AND status IN ('draft','ordered','partial','pending')")['c'] ?? 0),
            'inventory_items' => (int) (Database::fetch('SELECT COUNT(*) c FROM inventory_items WHERE deleted_at IS NULL')['c'] ?? 0),
        ];

        $queue = [
            'scheduled' => $this->countStatus($today, 'scheduled'),
            'confirmed' => $this->countStatus($today, 'confirmed'),
            'waiting' => $this->countStatus($today, 'waiting'),
            'checked_in' => $this->countStatus($today, 'checked_in'),
            'with_doctor' => $this->countStatus($today, 'with_doctor'),
            'completed' => $this->countStatus($today, 'completed'),
            'cancelled' => $this->countStatus($today, 'cancelled'),
            'no_show' => $this->countStatus($today, 'no_show'),
        ];

        $doctorId = null;
        if (Auth::hasRole('doctor')) {
            $doc = Database::fetch('SELECT id FROM doctors WHERE user_id = ? AND deleted_at IS NULL', [Auth::id()]);
            $doctorId = $doc['id'] ?? null;
        }

        $doctorQueue = [];
        if ($doctorId) {
            $doctorQueue = Database::fetchAll(
                "SELECT a.*, p.name AS patient_name, p.mobile, p.patient_code
                 FROM appointments a
                 INNER JOIN patients p ON p.id = a.patient_id
                 WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.deleted_at IS NULL
                 AND a.status IN ('waiting','checked_in','with_doctor','scheduled','confirmed')
                 ORDER BY a.start_time ASC",
                [$doctorId, $today]
            );
        }

        $view = 'modules/dashboard/admin';
        if (Auth::hasRole('super_admin') || Auth::hasRole('admin')) {
            $view = 'modules/dashboard/admin';
        } elseif (Auth::hasRole('receptionist')) {
            $view = 'modules/dashboard/receptionist';
        } elseif (Auth::hasRole('doctor')) {
            $view = 'modules/dashboard/doctor';
        } elseif (Auth::hasRole('accounts')) {
            $view = 'modules/dashboard/accounts';
        } elseif (Auth::hasRole('inventory')) {
            $view = 'modules/dashboard/inventory';
        }

        $this->view($view, [
            'title' => 'Dashboard',
            'pageTitle' => 'Dashboard',
            'stats' => $stats,
            'queue' => $queue,
            'doctorQueue' => $doctorQueue,
            'today' => $today,
            'recentPayments' => Database::fetchAll(
                'SELECT pay.payment_date, pay.amount, pay.payment_mode, p.name AS patient_name
                 FROM payments pay
                 INNER JOIN patients p ON p.id = pay.patient_id
                 WHERE pay.deleted_at IS NULL AND pay.status = ?
                 ORDER BY pay.payment_date DESC, pay.id DESC LIMIT 5',
                ['completed']
            ),
        ]);
    }

    private function countStatus(string $date, string $status): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(*) c FROM appointments WHERE appointment_date = ? AND status = ? AND deleted_at IS NULL',
            [$date, $status]
        )['c'] ?? 0);
    }
}
