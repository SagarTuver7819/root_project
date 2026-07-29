<?php
$actions = can('patients.add') ? '<a href="' . app_url('patients/create') . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Patient</a>' : '';
require __DIR__ . '/../../components/page-header.php';
$tableId = 'patientsTable';
$columns = ['#', 'OPD Number', 'Name', 'Mobile', 'Gender', 'Age', 'Reg Date', 'Ref Doctor', 'Status', 'Actions'];
ob_start();
?>
<div class="filter-field">
    <label class="form-label" for="patientStatusFilter">Status</label>
    <select class="form-select" name="status" id="patientStatusFilter">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>
</div>
<div class="filter-field">
    <label class="form-label" for="patientDateFrom">From</label>
    <input class="form-control" type="date" name="date_from" id="patientDateFrom">
</div>
<div class="filter-field">
    <label class="form-label" for="patientDateTo">To</label>
    <input class="form-control" type="date" name="date_to" id="patientDateTo">
</div>
<?php
$filtersHtml = ob_get_clean();
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams(window.location.search);
  RootsDataTable.init('#patientsTable', {
    ajax: '<?= app_url('patients/datatable') ?>',
    filterData: function () {
      return { q: params.get('q') || '' };
    },
    columns: [
      {data: 'id'},
      {data: 'patient_code'},
      {data: 'name'},
      {data: 'mobile'},
      {data: 'gender'},
      {data: 'age'},
      {data: 'registration_date'},
      {data: 'reference_doctor'},
      {data: 'status_badge', orderable: false, searchable: false, className: 'text-nowrap'},
      {data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap'}
    ],
    columnDefs: [
      { targets: -1, width: '120px' }
    ]
  });
});
</script>
