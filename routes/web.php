<?php

use App\Controllers\AppointmentController;
use App\Controllers\AppointmentStatusController;
use App\Controllers\ApprovalController;
use App\Controllers\AuditLogController;
use App\Controllers\AuthController;
use App\Controllers\BillingController;
use App\Controllers\CalendarController;
use App\Controllers\DashboardController;
use App\Controllers\DoctorController;
use App\Controllers\FollowUpController;
use App\Controllers\InventoryController;
use App\Controllers\MedicineMasterController;
use App\Controllers\PatientController;
use App\Controllers\PaymentController;
use App\Controllers\PrescriptionController;
use App\Controllers\PurchaseController;
use App\Controllers\ReferenceDoctorController;
use App\Controllers\ReportController;
use App\Controllers\RoleController;
use App\Controllers\SettingsController;
use App\Controllers\SupplierController;
use App\Controllers\TreatmentMasterController;
use App\Controllers\TreatmentPlanController;
use App\Controllers\UserController;
use App\Controllers\VisitController;
use App\Core\Request;
use App\Core\Response;

$router->get('/', fn (Request $request) => Response::redirect(app_url('dashboard')));

$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], ['csrf']);
    $router->get('/forgot', [AuthController::class, 'showForgot']);
    $router->post('/forgot', [AuthController::class, 'forgot'], ['csrf']);
    $router->get('/forgot-password', [AuthController::class, 'showForgot']);
    $router->post('/forgot-password', [AuthController::class, 'forgot'], ['csrf']);
    $router->get('/reset', [AuthController::class, 'showReset']);
    $router->post('/reset', [AuthController::class, 'reset'], ['csrf']);
    $router->get('/reset-password', [AuthController::class, 'showReset']);
    $router->post('/reset-password', [AuthController::class, 'reset'], ['csrf']);
});

$router->group(['middleware' => ['auth']], function ($router) {
    $router->post('/logout', [AuthController::class, 'logout'], ['csrf']);
    $router->get('/dashboard', [DashboardController::class, 'index'], ['permission:dashboard.view']);
    $router->get('/profile', [AuthController::class, 'profile']);
    $router->get('/change-password', [AuthController::class, 'showChangePassword']);
    $router->post('/change-password', [AuthController::class, 'changePassword'], ['csrf']);

    $router->get('/settings/branding', [SettingsController::class, 'branding'], ['permission:settings.view']);
    $router->post('/settings/branding', [SettingsController::class, 'updateBranding'], ['csrf', 'permission:settings.edit']);

    $router->get('/patients', [PatientController::class, 'index'], ['permission:patients.view']);
    $router->get('/patients/datatable', [PatientController::class, 'datatable'], ['permission:patients.view']);
    $router->get('/patients/search', [PatientController::class, 'search'], ['permission:patients.view']);
    $router->get('/patients/history', [PatientController::class, 'history'], ['permission:patients.view']);
    $router->get('/patients/create', [PatientController::class, 'create'], ['permission:patients.add']);
    $router->post('/patients', [PatientController::class, 'store'], ['csrf', 'permission:patients.add']);
    $router->get('/patients/{id}', [PatientController::class, 'show'], ['permission:patients.view']);
    $router->get('/patients/{id}/edit', [PatientController::class, 'edit'], ['permission:patients.edit']);
    $router->post('/patients/{id}', [PatientController::class, 'update'], ['csrf', 'permission:patients.edit']);
    $router->post('/patients/{id}/delete', [PatientController::class, 'destroy'], ['csrf', 'permission:patients.delete']);
    $router->get('/patients/{id}/tab/{tab}', [PatientController::class, 'tab'], ['permission:patients.view']);
    $router->post('/patients/{id}/documents', [PatientController::class, 'uploadDocument'], ['csrf', 'permission:patients.edit']);
    $router->post('/patients/{id}/documents/{docId}/delete', [PatientController::class, 'deleteDocument'], ['csrf', 'permission:patients.edit']);
    $router->post('/patients/{id}/clinical-chart', [PatientController::class, 'saveClinicalChart'], ['csrf', 'permission:patients.edit']);
    $router->post('/patients/{id}/suggested-plan', [PatientController::class, 'saveSuggestedPlan'], ['csrf', 'permission:patients.edit']);

    $router->get('/calendar', [CalendarController::class, 'index'], ['permission:appointments.view']);
    $router->get('/calendar/events', [CalendarController::class, 'events'], ['permission:appointments.view']);
    $router->get('/calendar/slots', [CalendarController::class, 'slots'], ['permission:appointments.view']);

    $router->get('/appointments', [AppointmentController::class, 'index'], ['permission:appointments.view']);
    $router->get('/appointments/datatable', [AppointmentController::class, 'datatable'], ['permission:appointments.view']);
    $router->post('/appointments', [AppointmentController::class, 'store'], ['csrf', 'permission:appointments.add']);
    $router->get('/appointments/{id}/edit', [AppointmentController::class, 'edit'], ['permission:appointments.edit']);
    $router->post('/appointments/{id}', [AppointmentController::class, 'update'], ['csrf', 'permission:appointments.edit']);
    $router->post('/appointments/{id}/delete', [AppointmentController::class, 'destroy'], ['csrf', 'permission:appointments.delete']);
    $router->post('/appointments/{id}/status', [AppointmentController::class, 'changeStatus'], ['csrf', 'permission:appointments.status_change']);
    $router->get('/queue', [AppointmentController::class, 'queue'], ['permission:appointments.view']);

    $router->get('/doctors', [DoctorController::class, 'index'], ['permission:doctors.view']);
    $router->get('/doctors/datatable', [DoctorController::class, 'datatable'], ['permission:doctors.view']);
    $router->get('/doctors/create', [DoctorController::class, 'create'], ['permission:doctors.add']);
    $router->post('/doctors', [DoctorController::class, 'store'], ['csrf', 'permission:doctors.add']);
    $router->get('/doctors/{id}/edit', [DoctorController::class, 'edit'], ['permission:doctors.edit']);
    $router->post('/doctors/{id}', [DoctorController::class, 'update'], ['csrf', 'permission:doctors.edit']);
    $router->post('/doctors/{id}/delete', [DoctorController::class, 'destroy'], ['csrf', 'permission:doctors.delete']);
    $router->get('/doctors/{id}/schedules', [DoctorController::class, 'schedules'], ['permission:doctors.view']);
    $router->post('/doctors/{id}/schedules', [DoctorController::class, 'saveSchedules'], ['csrf', 'permission:doctors.edit']);
    $router->get('/doctors/{id}/leaves', [DoctorController::class, 'leaves'], ['permission:doctors.view']);
    $router->post('/doctors/{id}/leaves', [DoctorController::class, 'storeLeave'], ['csrf', 'permission:doctors.edit']);
    $router->post('/doctors/{id}/leaves/{leaveId}', [DoctorController::class, 'updateLeave'], ['csrf', 'permission:doctors.edit']);
    $router->post('/doctors/{id}/leaves/{leaveId}/delete', [DoctorController::class, 'destroyLeave'], ['csrf', 'permission:doctors.delete']);

    $router->get('/reference-doctors', [ReferenceDoctorController::class, 'index'], ['permission:reference_doctors.view']);
    $router->get('/reference-doctors/datatable', [ReferenceDoctorController::class, 'datatable'], ['permission:reference_doctors.view']);
    $router->get('/reference-doctors/create', [ReferenceDoctorController::class, 'create'], ['permission:reference_doctors.add']);
    $router->post('/reference-doctors', [ReferenceDoctorController::class, 'store'], ['csrf', 'permission:reference_doctors.add']);
    $router->get('/reference-doctors/{id}/edit', [ReferenceDoctorController::class, 'edit'], ['permission:reference_doctors.edit']);
    $router->post('/reference-doctors/{id}', [ReferenceDoctorController::class, 'update'], ['csrf', 'permission:reference_doctors.edit']);
    $router->post('/reference-doctors/{id}/delete', [ReferenceDoctorController::class, 'destroy'], ['csrf', 'permission:reference_doctors.delete']);

    $router->get('/treatment-masters', [TreatmentMasterController::class, 'index'], ['permission:treatment_masters.view']);
    $router->get('/treatment-masters/datatable', [TreatmentMasterController::class, 'datatable'], ['permission:treatment_masters.view']);
    $router->get('/treatment-masters/create', [TreatmentMasterController::class, 'create'], ['permission:treatment_masters.add']);
    $router->post('/treatment-masters', [TreatmentMasterController::class, 'store'], ['csrf', 'permission:treatment_masters.add']);
    $router->get('/treatment-masters/{id}/edit', [TreatmentMasterController::class, 'edit'], ['permission:treatment_masters.edit']);
    $router->post('/treatment-masters/{id}', [TreatmentMasterController::class, 'update'], ['csrf', 'permission:treatment_masters.edit']);
    $router->post('/treatment-masters/{id}/delete', [TreatmentMasterController::class, 'destroy'], ['csrf', 'permission:treatment_masters.delete']);

    $router->get('/medicines', [MedicineMasterController::class, 'index'], ['permission:medicine_masters.view']);
    $router->get('/medicines/datatable', [MedicineMasterController::class, 'datatable'], ['permission:medicine_masters.view']);
    $router->get('/medicines/create', [MedicineMasterController::class, 'create'], ['permission:medicine_masters.add']);
    $router->post('/medicines', [MedicineMasterController::class, 'store'], ['csrf', 'permission:medicine_masters.add']);
    $router->get('/medicines/{id}/edit', [MedicineMasterController::class, 'edit'], ['permission:medicine_masters.edit']);
    $router->post('/medicines/{id}', [MedicineMasterController::class, 'update'], ['csrf', 'permission:medicine_masters.edit']);
    $router->post('/medicines/{id}/delete', [MedicineMasterController::class, 'destroy'], ['csrf', 'permission:medicine_masters.delete']);

    $router->get('/appointment-statuses', [AppointmentStatusController::class, 'index'], ['permission:appointment_statuses.view']);
    $router->get('/appointment-statuses/datatable', [AppointmentStatusController::class, 'datatable'], ['permission:appointment_statuses.view']);
    $router->get('/appointment-statuses/create', [AppointmentStatusController::class, 'create'], ['permission:appointment_statuses.add']);
    $router->post('/appointment-statuses', [AppointmentStatusController::class, 'store'], ['csrf', 'permission:appointment_statuses.add']);
    $router->get('/appointment-statuses/{id}/edit', [AppointmentStatusController::class, 'edit'], ['permission:appointment_statuses.edit']);
    $router->post('/appointment-statuses/{id}', [AppointmentStatusController::class, 'update'], ['csrf', 'permission:appointment_statuses.edit']);
    $router->post('/appointment-statuses/{id}/delete', [AppointmentStatusController::class, 'destroy'], ['csrf', 'permission:appointment_statuses.delete']);

    $router->get('/visits', [VisitController::class, 'index'], ['permission:visits.view']);
    $router->get('/visits/datatable', [VisitController::class, 'datatable'], ['permission:visits.view']);
    $router->post('/visits/start/{appointmentId}', [VisitController::class, 'start'], ['csrf', 'permission:visits.add']);
    $router->get('/visits/open/{appointmentId}', [VisitController::class, 'open'], ['permission:visits.add']);
    $router->get('/visits/{id}', [VisitController::class, 'show'], ['permission:visits.view']);
    $router->get('/visits/{id}/edit', [VisitController::class, 'edit'], ['permission:visits.edit']);
    $router->get('/visits/{id}/print', [VisitController::class, 'print'], ['permission:visits.view']);
    $router->post('/visits/{id}', [VisitController::class, 'update'], ['csrf', 'permission:visits.edit']);
    $router->post('/visits/{id}/delete', [VisitController::class, 'destroy'], ['csrf', 'permission:visits.delete']);
    $router->post('/visits/{id}/complete', [VisitController::class, 'complete'], ['csrf', 'permission:visits.edit']);

    $router->get('/treatment-plans', [TreatmentPlanController::class, 'index'], ['permission:treatments.view']);
    $router->get('/treatment-plans/datatable', [TreatmentPlanController::class, 'datatable'], ['permission:treatments.view']);
    $router->get('/treatment-plans/create', [TreatmentPlanController::class, 'create'], ['permission:treatments.add']);
    $router->post('/treatment-plans', [TreatmentPlanController::class, 'store'], ['csrf', 'permission:treatments.add']);
    $router->get('/treatment-plans/{id}', [TreatmentPlanController::class, 'show'], ['permission:treatments.view']);
    $router->get('/treatment-plans/{id}/print', [TreatmentPlanController::class, 'print'], ['permission:treatments.view']);
    $router->get('/treatment-plans/{id}/edit', [TreatmentPlanController::class, 'edit'], ['permission:treatments.edit']);
    $router->post('/treatment-plans/{id}', [TreatmentPlanController::class, 'update'], ['csrf', 'permission:treatments.edit']);
    $router->post('/treatment-plans/{id}/delete', [TreatmentPlanController::class, 'destroy'], ['csrf', 'permission:treatments.delete']);
    $router->post('/treatment-plans/{id}/sessions', [TreatmentPlanController::class, 'storeSession'], ['csrf', 'permission:treatment_sessions.add']);

    $router->get('/prescriptions', [PrescriptionController::class, 'index'], ['permission:prescriptions.view']);
    $router->get('/prescriptions/datatable', [PrescriptionController::class, 'datatable'], ['permission:prescriptions.view']);
    $router->get('/prescriptions/create', [PrescriptionController::class, 'create'], ['permission:prescriptions.add']);
    $router->post('/prescriptions', [PrescriptionController::class, 'store'], ['csrf', 'permission:prescriptions.add']);
    $router->get('/prescriptions/{id}', [PrescriptionController::class, 'show'], ['permission:prescriptions.view']);
    $router->get('/prescriptions/{id}/print', [PrescriptionController::class, 'print'], ['permission:prescriptions.view']);
    $router->get('/prescriptions/{id}/edit', [PrescriptionController::class, 'edit'], ['permission:prescriptions.edit']);
    $router->post('/prescriptions/{id}', [PrescriptionController::class, 'update'], ['csrf', 'permission:prescriptions.edit']);
    $router->post('/prescriptions/{id}/delete', [PrescriptionController::class, 'destroy'], ['csrf', 'permission:prescriptions.delete']);

    $router->get('/follow-ups', [FollowUpController::class, 'index'], ['permission:follow_ups.view']);
    $router->get('/follow-ups/datatable', [FollowUpController::class, 'datatable'], ['permission:follow_ups.view']);
    $router->get('/follow-ups/create', [FollowUpController::class, 'create'], ['permission:follow_ups.add']);
    $router->post('/follow-ups', [FollowUpController::class, 'store'], ['csrf', 'permission:follow_ups.add']);
    $router->get('/follow-ups/{id}/edit', [FollowUpController::class, 'edit'], ['permission:follow_ups.edit']);
    $router->post('/follow-ups/{id}', [FollowUpController::class, 'update'], ['csrf', 'permission:follow_ups.edit']);
    $router->post('/follow-ups/{id}/delete', [FollowUpController::class, 'destroy'], ['csrf', 'permission:follow_ups.delete']);
    $router->post('/follow-ups/{id}/convert', [FollowUpController::class, 'convertToAppointment'], ['csrf', 'permission:follow_ups.edit']);

    $router->get('/billing', [BillingController::class, 'index'], ['permission:billing.view']);
    $router->get('/billing/datatable', [BillingController::class, 'datatable'], ['permission:billing.view']);
    $router->get('/billing/booking-status', [BillingController::class, 'bookingStatus'], ['permission:billing.view']);
    $router->get('/billing/outstanding', [BillingController::class, 'outstanding'], ['permission:outstanding.view']);
    $router->get('/outstanding', [BillingController::class, 'outstanding'], ['permission:outstanding.view']);
    $router->get('/billing/create', [BillingController::class, 'create'], ['permission:billing.add']);
    $router->post('/billing', [BillingController::class, 'store'], ['csrf', 'permission:billing.add']);
    $router->get('/billing/{id}', [BillingController::class, 'show'], ['permission:billing.view']);
    $router->get('/billing/{id}/invoice', [BillingController::class, 'invoice'], ['permission:billing.view']);
    $router->get('/billing/{id}/print', [BillingController::class, 'invoice'], ['permission:billing.view']);
    $router->get('/billing/{id}/edit', [BillingController::class, 'edit'], ['permission:billing.edit']);
    $router->post('/billing/{id}', [BillingController::class, 'update'], ['csrf', 'permission:billing.edit']);
    $router->post('/billing/{id}/status', [BillingController::class, 'changeStatus'], ['csrf', 'permission:billing.edit']);
    $router->post('/billing/{id}/delete', [BillingController::class, 'destroy'], ['csrf', 'permission:billing.delete']);
    $router->get('/payments', [PaymentController::class, 'index'], ['permission:payments.view']);
    $router->get('/payments/datatable', [PaymentController::class, 'datatable'], ['permission:payments.view']);
    $router->post('/payments', [PaymentController::class, 'store'], ['csrf', 'permission:payments.add']);

    $router->get('/inventory', [InventoryController::class, 'index'], ['permission:inventory.view']);
    $router->get('/inventory/datatable', [InventoryController::class, 'datatable'], ['permission:inventory.view']);
    $router->get('/inventory/create', [InventoryController::class, 'create'], ['permission:inventory.add']);
    $router->post('/inventory', [InventoryController::class, 'store'], ['csrf', 'permission:inventory.add']);
    $router->get('/inventory/{id}/edit', [InventoryController::class, 'edit'], ['permission:inventory.edit']);
    $router->post('/inventory/{id}', [InventoryController::class, 'update'], ['csrf', 'permission:inventory.edit']);
    $router->post('/inventory/{id}/delete', [InventoryController::class, 'destroy'], ['csrf', 'permission:inventory.delete']);
    $router->post('/inventory/{id}/adjust-stock', [InventoryController::class, 'adjustStock'], ['csrf', 'permission:inventory.edit']);

    $router->get('/suppliers', [SupplierController::class, 'index'], ['permission:suppliers.view']);
    $router->get('/suppliers/datatable', [SupplierController::class, 'datatable'], ['permission:suppliers.view']);
    $router->get('/suppliers/create', [SupplierController::class, 'create'], ['permission:suppliers.add']);
    $router->post('/suppliers', [SupplierController::class, 'store'], ['csrf', 'permission:suppliers.add']);
    $router->get('/suppliers/{id}/edit', [SupplierController::class, 'edit'], ['permission:suppliers.edit']);
    $router->post('/suppliers/{id}', [SupplierController::class, 'update'], ['csrf', 'permission:suppliers.edit']);
    $router->post('/suppliers/{id}/delete', [SupplierController::class, 'destroy'], ['csrf', 'permission:suppliers.delete']);

    $router->get('/purchases', [PurchaseController::class, 'index'], ['permission:purchases.view']);
    $router->get('/purchases/datatable', [PurchaseController::class, 'datatable'], ['permission:purchases.view']);
    $router->get('/purchases/create', [PurchaseController::class, 'create'], ['permission:purchases.add']);
    $router->post('/purchases', [PurchaseController::class, 'store'], ['csrf', 'permission:purchases.add']);
    $router->get('/purchases/{id}', [PurchaseController::class, 'show'], ['permission:purchases.view']);
    $router->post('/purchases/{id}/confirm', [PurchaseController::class, 'confirm'], ['csrf', 'permission:purchases.edit']);
    $router->post('/purchases/{id}/delete', [PurchaseController::class, 'destroy'], ['csrf', 'permission:purchases.delete']);

    $router->get('/users', [UserController::class, 'index'], ['permission:users.view']);
    $router->get('/users/datatable', [UserController::class, 'datatable'], ['permission:users.view']);
    $router->get('/users/create', [UserController::class, 'create'], ['permission:users.add']);
    $router->post('/users', [UserController::class, 'store'], ['csrf', 'permission:users.add']);
    $router->get('/users/{id}/edit', [UserController::class, 'edit'], ['permission:users.edit']);
    $router->post('/users/{id}', [UserController::class, 'update'], ['csrf', 'permission:users.edit']);
    $router->post('/users/{id}/assign-role', [UserController::class, 'assignRole'], ['csrf', 'permission:users.edit']);
    $router->post('/users/{id}/delete', [UserController::class, 'destroy'], ['csrf', 'permission:users.delete']);

    $router->get('/roles', [RoleController::class, 'index'], ['permission:roles.view']);
    $router->get('/roles/{id}/edit', [RoleController::class, 'edit'], ['permission:roles.edit']);
    $router->post('/roles/{id}', [RoleController::class, 'update'], ['csrf', 'permission:roles.edit']);

    $router->get('/approvals', [ApprovalController::class, 'index'], ['permission:approvals.view']);
    $router->post('/approvals/{id}/approve', [ApprovalController::class, 'approve'], ['csrf', 'permission:approvals.approve']);
    $router->post('/approvals/{id}/reject', [ApprovalController::class, 'reject'], ['csrf', 'permission:approvals.approve']);

    $router->get('/audit-logs', [AuditLogController::class, 'index'], ['permission:audit_logs.view']);
    $router->get('/audit-logs/datatable', [AuditLogController::class, 'datatable'], ['permission:audit_logs.view']);

    $router->get('/reports', [ReportController::class, 'index'], ['permission:reports.view']);
    $router->get('/reports/data', [ReportController::class, 'data'], ['permission:reports.view']);
});
