<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <a href="<?= app_url('dashboard') ?>" class="brand-link" title="<?= e(branding('hospital_name')) ?>">
            <img src="<?= e(logo_url('logo_sidebar')) ?>" alt="<?= e(branding('hospital_name')) ?>" class="brand-logo brand-logo-full">
            <img src="<?= e(logo_url('logo_collapsed')) ?>" alt="<?= e(branding('hospital_name')) ?>" class="brand-logo brand-logo-mini">
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php if (can('dashboard.view')): ?>
        <div class="nav-section">
            <a class="nav-link <?= active_menu('dashboard') ?>" href="<?= app_url('dashboard') ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
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
                <a class="nav-link <?= active_menu('appointments') ?>" href="<?= app_url('appointments') ?>"><i class="bi bi-calendar-check"></i><span>Appointments</span></a>
                <a class="nav-link <?= active_menu('queue') ?>" href="<?= app_url('queue') ?>"><i class="bi bi-people"></i><span>Patient Queue</span></a>
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

        <?php if (can('doctors.view') || can('reference_doctors.view') || can('treatment_masters.view') || can('medicine_masters.view') || can('appointment_statuses.view')): ?>
        <div class="nav-section">
            <button class="nav-toggle <?= menu_open(['doctors','reference-doctors','treatment-masters','medicines','appointment-statuses']) ?>" type="button" data-target="menuMasters">
                <span><i class="bi bi-database"></i><span class="label">Masters</span></span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="nav-submenu <?= menu_open(['doctors','reference-doctors','treatment-masters','medicines','appointment-statuses']) ?>" id="menuMasters">
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
                <?php if (can('appointment_statuses.view')): ?>
                <a class="nav-link <?= active_menu('appointment-statuses') ?>" href="<?= app_url('appointment-statuses') ?>"><i class="bi bi-tags"></i><span>Appointment Status</span></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can('billing.view') || can('payments.view') || can('outstanding.view')): ?>
        <div class="nav-section">
            <button class="nav-toggle <?= menu_open(['billing','payments','outstanding']) ?>" type="button" data-target="menuAccounts">
                <span><i class="bi bi-cash-coin"></i><span class="label">Accounts</span></span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="nav-submenu <?= menu_open(['billing','payments','outstanding']) ?>" id="menuAccounts">
                <?php if (can('billing.view')): ?>
                <a class="nav-link <?= active_menu('billing') ?>" href="<?= app_url('billing') ?>"><i class="bi bi-receipt"></i><span>Billing</span></a>
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
            <button class="nav-toggle <?= menu_open(['inventory','suppliers','purchases']) ?>" type="button" data-target="menuInventory">
                <span><i class="bi bi-box-seam"></i><span class="label">Inventory</span></span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="nav-submenu <?= menu_open(['inventory','suppliers','purchases']) ?>" id="menuInventory">
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
            <button class="nav-toggle <?= menu_open(['users','roles','approvals','audit-logs','settings']) ?>" type="button" data-target="menuAdmin">
                <span><i class="bi bi-gear"></i><span class="label">Administration</span></span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="nav-submenu <?= menu_open(['users','roles','approvals','audit-logs','settings']) ?>" id="menuAdmin">
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
    </nav>
</aside>
