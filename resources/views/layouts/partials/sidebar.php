<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <?php
        // Admin + Reception: simplified 3-menu view (full menus hidden below, not deleted).
        $frontDeskSimple = \App\Core\Auth::hasRole('receptionist')
            || \App\Core\Auth::hasRole('admin')
            || \App\Core\Auth::hasRole('super_admin');
        $brandHome = $frontDeskSimple ? app_url('calendar') : app_url('dashboard');
        ?>
        <a href="<?= $brandHome ?>" class="brand-link" title="<?= e(branding('hospital_name')) ?>">
            <img src="<?= e(logo_url('logo_sidebar')) ?>" alt="<?= e(branding('hospital_name')) ?>" class="brand-logo brand-logo-full">
            <img src="<?= e(logo_url('logo_collapsed')) ?>" alt="<?= e(branding('hospital_name')) ?>" class="brand-logo brand-logo-mini">
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php if ($frontDeskSimple): ?>
            <?php /* ===== ACTIVE: Admin / Reception — only 3 menus ===== */ ?>
            <div class="nav-section">
                <?php if (can('appointments.view') || can('calendar.view')): ?>
                <a class="nav-link <?= active_menu('calendar') ?>" href="<?= app_url('calendar') ?>" title="Calendar View">
                    <i class="bi bi-calendar3"></i><span>Calendar View</span>
                </a>
                <?php endif; ?>

                <?php if (can('appointments.view') || can('patients.view')): ?>
                <button class="nav-toggle <?= menu_open(['queue','patients']) ?>" type="button" data-target="menuWalkin">
                    <span><i class="bi bi-person-walking"></i><span class="label">Walkin Customer</span></span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <div class="nav-submenu <?= menu_open(['queue','patients']) ?>" id="menuWalkin">
                    <a class="nav-link <?= active_menu('queue') ?>" href="<?= app_url('queue') ?>">
                        <i class="bi bi-table"></i><span>Today's Walk-in</span>
                    </a>
                    <?php if (can('patients.add') || can('appointments.add')): ?>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#walkinAddPatientModal">
                        <i class="bi bi-person-plus"></i><span>Add Patient</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (can('payments.view')): ?>
                <a class="nav-link <?= active_menu('payments') ?>" href="<?= app_url('payments') ?>" title="Payments">
                    <i class="bi bi-wallet2"></i><span>Payments</span>
                </a>
                <?php endif; ?>
            </div>

            <?php
            /*
             * =====================================================================
             * HIDDEN for Admin / Reception (coding ma rakhyu — view nathi)
             * Full original sidebar menus. Restore: change `if (false)` → `if (true)`
             * or remove this wrapper after client confirms.
             * =====================================================================
             */
            ?>
            <?php if (false): ?>
                <?php if (can('dashboard.view')): ?>
                <div class="nav-section">
                    <a class="nav-link <?= active_menu('dashboard') ?>" href="<?= app_url('dashboard') ?>" title="Dashboard">
                        <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (can('appointments.view') || can('follow_ups.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['calendar','appointments','queue','follow-ups']) ?>" type="button" data-target="menuFrontDeskHidden">
                        <span><i class="bi bi-reception-4"></i><span class="label">Front Desk</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['calendar','appointments','queue','follow-ups']) ?>" id="menuFrontDeskHidden">
                        <?php if (can('appointments.view')): ?>
                        <a class="nav-link <?= active_menu('calendar') ?>" href="<?= app_url('calendar') ?>"><i class="bi bi-calendar3"></i><span>Calendar</span></a>
                        <a class="nav-link <?= active_menu('queue') ?>" href="<?= app_url('queue') ?>"><i class="bi bi-people"></i><span>Today's Walk-in Patients</span></a>
                        <?php if (can('appointments.add') && (\App\Core\Auth::hasRole('super_admin') || \App\Core\Auth::hasRole('admin'))): ?>
                        <a class="nav-link <?= active_menu('appointments') ?>" href="<?= app_url('appointments') ?>"><i class="bi bi-calendar-check"></i><span>Appointments</span></a>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if (can('follow_ups.view') && (\App\Core\Auth::hasRole('super_admin') || \App\Core\Auth::hasRole('admin') || \App\Core\Auth::hasRole('doctor'))): ?>
                        <a class="nav-link <?= active_menu('follow-ups') ?>" href="<?= app_url('follow-ups') ?>"><i class="bi bi-arrow-repeat"></i><span>Follow-Ups</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (can('patients.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['patients']) ?>" type="button" data-target="menuPatientsHidden">
                        <span><i class="bi bi-person-vcard"></i><span class="label">Patients</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['patients']) ?>" id="menuPatientsHidden">
                        <?php
                        $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
                        $isPatientCreate = str_contains($reqPath, '/patients/create');
                        $isPatientHistory = str_contains($reqPath, '/patients/history');
                        $isPatientList = active_menu('patients') === 'active' && !$isPatientCreate && !$isPatientHistory;
                        ?>
                        <a class="nav-link <?= $isPatientList ? 'active' : '' ?>" href="<?= app_url('patients') ?>"><i class="bi bi-list-ul"></i><span>Patient List</span></a>
                        <a class="nav-link <?= $isPatientHistory ? 'active' : '' ?>" href="<?= app_url('patients/history') ?>"><i class="bi bi-clock-history"></i><span>Patient History</span></a>
                        <?php if (can('patients.add')): ?>
                        <a class="nav-link <?= $isPatientCreate ? 'active' : '' ?>" href="<?= app_url('patients/create') ?>"><i class="bi bi-person-plus"></i><span>Add Patient</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (can('visits.view') || can('treatments.view') || can('prescriptions.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['visits','treatment-plans','prescriptions']) ?>" type="button" data-target="menuClinicalHidden">
                        <span><i class="bi bi-heart-pulse"></i><span class="label">Clinical</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['visits','treatment-plans','prescriptions']) ?>" id="menuClinicalHidden">
                        <?php if (can('visits.view')): ?>
                        <a class="nav-link <?= active_menu('visits') ?>" href="<?= app_url('visits') ?>"><i class="bi bi-clipboard2-pulse"></i><span>Patient Visits</span></a>
                        <?php endif; ?>
                        <?php if (can('treatments.view')): ?>
                        <a class="nav-link <?= active_menu('treatment-plans') ?>" href="<?= app_url('treatment-plans') ?>"><i class="bi bi-journal-medical"></i><span>Treatment Plans</span></a>
                        <?php endif; ?>
                        <?php if (can('prescriptions.view')): ?>
                        <a class="nav-link <?= active_menu('prescriptions') ?>" href="<?= app_url('prescriptions') ?>"><i class="bi bi-prescription2"></i><span>Prescriptions</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (can('doctors.view') || can('reference_doctors.view') || can('treatment_masters.view') || can('medicine_masters.view') || can('lab_masters.view') || can('appointment_statuses.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['doctors','reference-doctors','treatment-masters','medicines','lab-masters','appointment-statuses']) ?>" type="button" data-target="menuMastersHidden">
                        <span><i class="bi bi-database"></i><span class="label">Masters</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['doctors','reference-doctors','treatment-masters','medicines','lab-masters','appointment-statuses']) ?>" id="menuMastersHidden">
                        <?php if (can('doctors.view')): ?>
                        <a class="nav-link <?= active_menu('doctors') ?>" href="<?= app_url('doctors') ?>"><i class="bi bi-person-badge"></i><span>Doctors</span></a>
                        <?php endif; ?>
                        <?php if (can('reference_doctors.view')): ?>
                        <a class="nav-link <?= active_menu('reference-doctors') ?>" href="<?= app_url('reference-doctors') ?>"><i class="bi bi-hospital"></i><span>Reference Doctors</span></a>
                        <?php endif; ?>
                        <?php if (can('treatment_masters.view')): ?>
                        <a class="nav-link <?= active_menu('treatment-masters') ?>" href="<?= app_url('treatment-masters') ?>"><i class="bi bi-tooth"></i><span>Treatment Master</span></a>
                        <?php endif; ?>
                        <?php if (can('medicine_masters.view')): ?>
                        <a class="nav-link <?= active_menu('medicines') ?>" href="<?= app_url('medicines') ?>"><i class="bi bi-capsule"></i><span>Medicine Master</span></a>
                        <?php endif; ?>
                        <?php if (can('lab_masters.view')): ?>
                        <a class="nav-link <?= active_menu('lab-masters') ?>" href="<?= app_url('lab-masters') ?>"><i class="bi bi-eyedropper"></i><span>Lab Master</span></a>
                        <?php endif; ?>
                        <?php if (can('appointment_statuses.view')): ?>
                        <a class="nav-link <?= active_menu('appointment-statuses') ?>" href="<?= app_url('appointment-statuses') ?>"><i class="bi bi-tags"></i><span>Appointment Status</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (can('billing.view') || can('quotations.view') || can('payments.view') || can('outstanding.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['billing','quotations','payments','outstanding']) ?>" type="button" data-target="menuAccountsHidden">
                        <span><i class="bi bi-cash-coin"></i><span class="label">Accounts</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['billing','quotations','payments','outstanding']) ?>" id="menuAccountsHidden">
                        <?php if (can('billing.view')): ?>
                        <a class="nav-link <?= active_menu('billing') ?>" href="<?= app_url('billing') ?>"><i class="bi bi-receipt"></i><span>Billing</span></a>
                        <?php endif; ?>
                        <?php if (can('quotations.view')): ?>
                        <a class="nav-link <?= active_menu('quotations') ?>" href="<?= app_url('quotations') ?>"><i class="bi bi-file-earmark-text"></i><span>Quotation</span></a>
                        <?php endif; ?>
                        <?php if (can('payments.view')): ?>
                        <a class="nav-link <?= active_menu('payments') ?>" href="<?= app_url('payments') ?>"><i class="bi bi-wallet2"></i><span>Payments</span></a>
                        <?php endif; ?>
                        <?php if (can('outstanding.view')): ?>
                        <a class="nav-link <?= active_menu('outstanding') ?>" href="<?= app_url('outstanding') ?>"><i class="bi bi-exclamation-circle"></i><span>Outstanding</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (can('inventory.view') || can('suppliers.view') || can('purchases.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['inventory','suppliers','purchases']) ?>" type="button" data-target="menuInventoryHidden">
                        <span><i class="bi bi-box-seam"></i><span class="label">Inventory</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['inventory','suppliers','purchases']) ?>" id="menuInventoryHidden">
                        <?php if (can('inventory.view')): ?>
                        <a class="nav-link <?= active_menu('inventory') ?>" href="<?= app_url('inventory') ?>"><i class="bi bi-boxes"></i><span>Items</span></a>
                        <?php endif; ?>
                        <?php if (can('suppliers.view')): ?>
                        <a class="nav-link <?= active_menu('suppliers') ?>" href="<?= app_url('suppliers') ?>"><i class="bi bi-truck"></i><span>Suppliers</span></a>
                        <?php endif; ?>
                        <?php if (can('purchases.view')): ?>
                        <a class="nav-link <?= active_menu('purchases') ?>" href="<?= app_url('purchases') ?>"><i class="bi bi-cart-check"></i><span>Purchases</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (can('reports.view')): ?>
                <div class="nav-section">
                    <a class="nav-link <?= active_menu('reports') ?>" href="<?= app_url('reports') ?>" title="Reports">
                        <i class="bi bi-graph-up-arrow"></i><span>Reports</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (can('users.view') || can('roles.view') || can('approvals.view') || can('audit_logs.view') || can('settings.view')): ?>
                <div class="nav-section">
                    <button class="nav-toggle <?= menu_open(['users','roles','approvals','audit-logs','settings']) ?>" type="button" data-target="menuAdminHidden">
                        <span><i class="bi bi-gear"></i><span class="label">Administration</span></span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>
                    <div class="nav-submenu <?= menu_open(['users','roles','approvals','audit-logs','settings']) ?>" id="menuAdminHidden">
                        <?php if (can('users.view')): ?>
                        <a class="nav-link <?= active_menu('users') ?>" href="<?= app_url('users') ?>"><i class="bi bi-people"></i><span>Users</span></a>
                        <?php endif; ?>
                        <?php if (can('roles.view')): ?>
                        <a class="nav-link <?= active_menu('roles') ?>" href="<?= app_url('roles') ?>"><i class="bi bi-shield-lock"></i><span>Roles & Permissions</span></a>
                        <?php endif; ?>
                        <?php if (can('approvals.view')): ?>
                        <a class="nav-link <?= active_menu('approvals') ?>" href="<?= app_url('approvals') ?>"><i class="bi bi-check2-square"></i><span>Approval Requests</span></a>
                        <?php endif; ?>
                        <?php if (can('audit_logs.view')): ?>
                        <a class="nav-link <?= active_menu('audit-logs') ?>" href="<?= app_url('audit-logs') ?>"><i class="bi bi-clock-history"></i><span>Audit Logs</span></a>
                        <?php endif; ?>
                        <?php if (can('settings.view')): ?>
                        <a class="nav-link <?= active_menu('settings') ?>" href="<?= app_url('settings/branding') ?>"><i class="bi bi-palette"></i><span>Settings</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; /* end hidden full admin menus */ ?>

        <?php else: ?>
            <?php /* Doctor / other roles — clinical menus */ ?>
            <?php if (can('dashboard.view')): ?>
            <div class="nav-section">
                <a class="nav-link <?= active_menu('dashboard') ?>" href="<?= app_url('dashboard') ?>" title="Dashboard">
                    <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (can('appointments.view') || can('follow_ups.view')): ?>
            <div class="nav-section">
                <button class="nav-toggle <?= menu_open(['calendar','appointments','queue','follow-ups']) ?>" type="button" data-target="menuFrontDesk">
                    <span><i class="bi bi-reception-4"></i><span class="label">Front Desk</span></span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <div class="nav-submenu <?= menu_open(['calendar','appointments','queue','follow-ups']) ?>" id="menuFrontDesk">
                    <?php if (can('appointments.view')): ?>
                    <a class="nav-link <?= active_menu('calendar') ?>" href="<?= app_url('calendar') ?>"><i class="bi bi-calendar3"></i><span>Calendar</span></a>
                    <a class="nav-link <?= active_menu('queue') ?>" href="<?= app_url('queue') ?>"><i class="bi bi-people"></i><span>Today's Walk-in Patients</span></a>
                    <?php endif; ?>
                    <?php if (can('follow_ups.view')): ?>
                    <a class="nav-link <?= active_menu('follow-ups') ?>" href="<?= app_url('follow-ups') ?>"><i class="bi bi-arrow-repeat"></i><span>Follow-Ups</span></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (can('patients.view')): ?>
            <div class="nav-section">
                <button class="nav-toggle <?= menu_open(['patients']) ?>" type="button" data-target="menuPatients">
                    <span><i class="bi bi-person-vcard"></i><span class="label">Patients</span></span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <div class="nav-submenu <?= menu_open(['patients']) ?>" id="menuPatients">
                    <a class="nav-link <?= active_menu('patients') ?>" href="<?= app_url('patients') ?>"><i class="bi bi-list-ul"></i><span>Patient List</span></a>
                    <?php if (can('patients.add')): ?>
                    <a class="nav-link" href="<?= app_url('patients/create') ?>"><i class="bi bi-person-plus"></i><span>Add Patient</span></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (can('visits.view') || can('treatments.view') || can('prescriptions.view')): ?>
            <div class="nav-section">
                <button class="nav-toggle <?= menu_open(['visits','treatment-plans','prescriptions']) ?>" type="button" data-target="menuClinical">
                    <span><i class="bi bi-heart-pulse"></i><span class="label">Clinical</span></span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <div class="nav-submenu <?= menu_open(['visits','treatment-plans','prescriptions']) ?>" id="menuClinical">
                    <?php if (can('visits.view')): ?>
                    <a class="nav-link <?= active_menu('visits') ?>" href="<?= app_url('visits') ?>"><i class="bi bi-clipboard2-pulse"></i><span>Patient Visits</span></a>
                    <?php endif; ?>
                    <?php if (can('treatments.view')): ?>
                    <a class="nav-link <?= active_menu('treatment-plans') ?>" href="<?= app_url('treatment-plans') ?>"><i class="bi bi-journal-medical"></i><span>Treatment Plans</span></a>
                    <?php endif; ?>
                    <?php if (can('prescriptions.view')): ?>
                    <a class="nav-link <?= active_menu('prescriptions') ?>" href="<?= app_url('prescriptions') ?>"><i class="bi bi-prescription2"></i><span>Prescriptions</span></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (can('payments.view')): ?>
            <div class="nav-section">
                <a class="nav-link <?= active_menu('payments') ?>" href="<?= app_url('payments') ?>"><i class="bi bi-wallet2"></i><span>Payments</span></a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</aside>

<?php if ($frontDeskSimple && (can('patients.add') || can('appointments.add'))): ?>
<div class="modal fade" id="walkinAddPatientModal" tabindex="-1" aria-labelledby="walkinAddPatientTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="walkinAddPatientTitle"><i class="bi bi-person-plus me-2"></i>Add Patient — Walk-in</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Choose walk-in type:</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-lg text-start" id="btnWalkinExisting">
                        <i class="bi bi-person-check me-2"></i>
                        <strong>Existing Customer Walk-in</strong>
                        <div class="small text-muted fw-normal">Search old patient → send to waiting</div>
                    </button>
                    <a class="btn btn-outline-success btn-lg text-start" href="<?= app_url('patients/create?from=walkin') ?>">
                        <i class="bi bi-person-plus me-2"></i>
                        <strong>New Patient</strong>
                        <div class="small text-muted fw-normal">Register new patient → waiting queue</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="walkinExistingModal" tabindex="-1" aria-labelledby="walkinExistingTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content ajax-form" method="post" action="<?= app_url('queue/walk-in') ?>" data-reload="1">
            <div class="modal-header">
                <h5 class="modal-title" id="walkinExistingTitle"><i class="bi bi-person-walking me-2"></i>Existing Customer Walk-in</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Patient <span class="required-star">*</span></label>
                        <select class="form-select" name="patient_id" id="walkinExistingPatient" required style="width:100%"
                                data-ajax="<?= e(app_url('patients/search')) ?>"
                                data-placeholder="Search name / mobile / OPD">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Doctor <span class="required-star">*</span></label>
                        <select class="form-select no-select2" name="doctor_id" id="walkinExistingDoctor" required data-no-select2="1">
                            <option value="">Select Doctor</option>
                            <?php
                            $walkinDocs = \App\Core\Database::fetchAll(
                                'SELECT id, name FROM doctors WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC'
                            );
                            foreach ($walkinDocs as $wd):
                            ?>
                                <option value="<?= e((string) $wd['id']) ?>"><?= e(doctor_label($wd['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Visit Reason / Notes</label>
                        <input class="form-control" type="text" name="reason" placeholder="Optional — e.g. scaling, pain">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-hourglass-split me-1"></i>Send to Waiting</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const addModalEl = document.getElementById('walkinAddPatientModal');
  const existingModalEl = document.getElementById('walkinExistingModal');
  document.getElementById('btnWalkinExisting')?.addEventListener('click', function () {
    const addModal = addModalEl && bootstrap.Modal.getOrCreateInstance(addModalEl);
    const existingModal = existingModalEl && bootstrap.Modal.getOrCreateInstance(existingModalEl);
    addModal && addModal.hide();
    setTimeout(function () { existingModal && existingModal.show(); }, 200);
  });
});
</script>
<?php endif; ?>
