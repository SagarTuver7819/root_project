<?php
$actions = '<a href="' . app_url('patients') . '" class="btn btn-light"><i class="bi bi-list-ul me-1"></i>Patient List</a>';
if (!empty($patient['id']) && can('patients.edit')) {
    $actions .= '<a href="' . app_url('patients/' . $patient['id'] . '/edit') . '" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit Patient</a>';
}
require __DIR__ . '/../../components/page-header.php';

$q = $q ?? '';
$dateFrom = $dateFrom ?? '';
$dateTo = $dateTo ?? '';
$patient = $patient ?? null;
$matches = $matches ?? [];
$patients = $patients ?? [];
$timeline = $timeline ?? [];
$summary = $summary ?? [];
$hasFilters = ($q !== '' || $dateFrom !== '' || $dateTo !== '');
?>

<div class="card content-card mb-4">
    <div class="card-body">
        <form method="get" action="<?= app_url('patients/history') ?>" class="row g-3 align-items-end history-filter-form">
            <div class="col-lg-4 col-md-6">
                <label class="form-label fw-semibold" for="historySearchQ">Search Mobile / Name / ID</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-primary"></i></span>
                    <input
                        class="form-control"
                        type="search"
                        name="q"
                        id="historySearchQ"
                        value="<?= e($q) ?>"
                        placeholder="e.g. 9876511101"
                    >
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label fw-semibold" for="historyDateFrom">From Date</label>
                <input class="form-control" type="date" name="date_from" id="historyDateFrom" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label fw-semibold" for="historyDateTo">To Date</label>
                <input class="form-control" type="date" name="date_to" id="historyDateTo" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-lg-4 col-md-12 d-flex flex-wrap gap-2">
                <button class="btn btn-primary flex-fill" type="submit">
                    <i class="bi bi-funnel me-1"></i>Apply Filters
                </button>
                <?php if ($hasFilters): ?>
                    <a class="btn btn-outline-secondary" href="<?= app_url('patients/history') ?>">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-12">
                <div class="form-text mb-0">
                    By default all patients are listed. Use mobile search for full history, or From/To dates to filter activity period.
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($q !== '' && !$patient && empty($matches)): ?>
    <div class="alert alert-warning">
        No patient found for <strong><?= e($q) ?></strong>.
        <?php if (can('patients.add')): ?>
            <a href="<?= app_url('patients/create') ?>" class="alert-link ms-1">Add Patient</a>
        <?php endif; ?>
    </div>
<?php elseif ($q !== '' && !$patient && count($matches) > 1): ?>
    <div class="card content-card">
        <div class="card-body">
            <h6 class="mb-3">Multiple patients found — select one</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 roots-grid">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Age / Gender</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $row): ?>
                            <tr>
                                <td><?= e($row['patient_code'] ?? '') ?></td>
                                <td><?= e($row['name'] ?? '') ?></td>
                                <td><?= e($row['mobile'] ?? '') ?></td>
                                <td><?= e(($row['age'] ?? '-') . ' / ' . ucfirst((string) ($row['gender'] ?? '-'))) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="<?= app_url('patients/history?' . http_build_query(array_filter([
                                        'q' => $row['mobile'] ?: $row['patient_code'],
                                        'date_from' => $dateFrom ?: null,
                                        'date_to' => $dateTo ?: null,
                                    ]))) ?>">View History</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($patient): ?>
    <div class="card content-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                <div>
                    <div class="text-muted small">Patient History</div>
                    <h4 class="mb-1"><?= e($patient['name'] ?? '') ?></h4>
                    <div class="text-muted">
                        <?= e($patient['patient_code'] ?? '') ?>
                        · <?= e($patient['mobile'] ?? '') ?>
                        · <?= e(($patient['age'] ?? '-') . ' yrs / ' . ucfirst((string) ($patient['gender'] ?? '-'))) ?>
                    </div>
                    <?php if ($dateFrom || $dateTo): ?>
                        <div class="small text-primary mt-1">
                            Date filter:
                            <?= e($dateFrom ? format_date($dateFrom) : 'Any') ?>
                            →
                            <?= e($dateTo ? format_date($dateTo) : 'Any') ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['allergies'])): ?>
                        <div class="text-danger small mt-1"><strong>Allergies:</strong> <?= e($patient['allergies']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary" href="<?= app_url('patients/' . ($patient['id'] ?? '') . '?tab=history') ?>">
                        <i class="bi bi-person-vcard me-1"></i>Full Profile
                    </a>
                    <a class="btn btn-light" href="<?= app_url('calendar?patient_id=' . ($patient['id'] ?? '')) ?>">
                        <i class="bi bi-calendar-plus me-1"></i>Book Appointment
                    </a>
                    <a class="btn btn-outline-secondary" href="<?= app_url('patients/history') ?>">
                        <i class="bi bi-people me-1"></i>All Patients
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['Appointments', $summary['appointments'] ?? 0, 'bi-calendar-check', '#0EA5E9'],
            ['Visits', $summary['visits'] ?? 0, 'bi-clipboard2-pulse', '#8B5CF6'],
            ['Treatments', $summary['treatments'] ?? 0, 'bi-journal-medical', '#00AEEF'],
            ['Prescriptions', $summary['prescriptions'] ?? 0, 'bi-prescription2', '#F59E0B'],
            ['Payments', $summary['payments'] ?? 0, 'bi-cash-coin', '#22C55E'],
        ];
        foreach ($cards as [$label, $count, $icon, $color]):
        ?>
            <div class="col">
                <div class="history-summary-card" style="--c: <?= e($color) ?>">
                    <i class="bi <?= e($icon) ?>"></i>
                    <div>
                        <div class="history-summary-count"><?= e((string) $count) ?></div>
                        <div class="history-summary-label"><?= e($label) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card content-card">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-clock-history me-1 text-primary"></i> Clinical History Timeline</strong>
            <span class="badge bg-primary"><?= e((string) count($timeline)) ?> records</span>
        </div>
        <div class="card-body">
            <?php
            $rows = $timeline;
            require __DIR__ . '/tabs/history.php';
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="card content-card">
        <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong><i class="bi bi-people me-1 text-primary"></i> All Patients — History Overview</strong>
                <div class="small text-muted">
                    Basic patient list with appointment / visit / treatment counts
                    <?php if ($dateFrom || $dateTo): ?>
                        · Filtered:
                        <?= e($dateFrom ? format_date($dateFrom) : 'Any') ?>
                        →
                        <?= e($dateTo ? format_date($dateTo) : 'Any') ?>
                    <?php endif; ?>
                </div>
            </div>
            <span class="badge bg-light text-dark border"><?= e((string) count($patients)) ?> patients</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="historyPatientsTable" class="table table-hover align-middle w-100 roots-grid">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Age / Gender</th>
                            <th>Reg Date</th>
                            <th class="text-center">Appts</th>
                            <th class="text-center">Visits</th>
                            <th class="text-center">Treatments</th>
                            <th>Last Activity</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No patients found for selected filters.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($patients as $row):
                            $last = (string) ($row['last_activity_date'] ?? '');
                            if ($last === '' || str_starts_with($last, '1970')) {
                                $lastLabel = '—';
                                $lastSort = '1970-01-01';
                            } else {
                                $lastLabel = format_date($last);
                                $lastSort = substr($last, 0, 10);
                            }
                            $historyUrl = app_url('patients/history?' . http_build_query(array_filter([
                                'q' => $row['mobile'] ?: $row['patient_code'],
                                'date_from' => $dateFrom ?: null,
                                'date_to' => $dateTo ?: null,
                            ])));
                            $appts = (int) ($row['appointments_count'] ?? 0);
                            $visits = (int) ($row['visits_count'] ?? 0);
                            $treatments = (int) ($row['treatments_count'] ?? 0);
                        ?>
                            <tr>
                                <td class="fw-semibold"><?= e($row['patient_code'] ?? '') ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($row['name'] ?? '') ?></div>
                                    <?= status_badge(!empty($row['is_active']) ? 'active' : 'inactive') ?>
                                </td>
                                <td class="text-nowrap"><?= e($row['mobile'] ?? '') ?></td>
                                <td><?= e(($row['age'] ?? '-') . ' / ' . ucfirst((string) ($row['gender'] ?? '-'))) ?></td>
                                <td data-order="<?= e((string) ($row['registration_date'] ?? '')) ?>"><?= e(format_date($row['registration_date'] ?? null)) ?></td>
                                <td class="text-center" data-order="<?= e((string) $appts) ?>">
                                    <span class="history-count-pill history-count-appts"><?= e((string) $appts) ?></span>
                                </td>
                                <td class="text-center" data-order="<?= e((string) $visits) ?>">
                                    <span class="history-count-pill history-count-visits"><?= e((string) $visits) ?></span>
                                </td>
                                <td class="text-center" data-order="<?= e((string) $treatments) ?>">
                                    <span class="history-count-pill history-count-treatments"><?= e((string) $treatments) ?></span>
                                </td>
                                <td data-order="<?= e($lastSort) ?>"><?= e($lastLabel) ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-action btn-action-primary" href="<?= e($historyUrl) ?>" title="View History"><i class="bi bi-clock-history"></i></a>
                                    <a class="btn btn-action" href="<?= app_url('patients/' . ($row['id'] ?? '') . '?tab=history') ?>" title="Profile"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if (!empty($patients)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.jQuery || !$.fn.DataTable) return;
      const $table = $('#historyPatientsTable');
      if ($.fn.DataTable.isDataTable($table)) {
        $table.DataTable().destroy();
      }
      $table.DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[8, 'desc']],
        columnDefs: [
          { orderable: false, targets: -1 },
          { className: 'text-center', targets: [5, 6, 7] }
        ],
        language: {
          search: 'Search:',
          lengthMenu: 'Show _MENU_ entries',
          info: 'Showing _START_ to _END_ of _TOTAL_ patients',
          paginate: { previous: 'Previous', next: 'Next' }
        }
      });
    });
    </script>
    <?php endif; ?>
<?php endif; ?>
