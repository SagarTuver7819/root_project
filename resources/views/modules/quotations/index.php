<?php
$actions = '';
if (can('quotations.add')) {
    $actions .= '<a href="' . app_url('quotations/create') . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Quotation</a>';
}
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'quotationsTable';
$columns = ['#', 'Quotation No', 'Date', 'Patient', 'Doctor', 'Net Amount', 'Status', 'Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  RootsDataTable.init('#quotationsTable', {
    ajax: '<?= app_url('quotations/datatable') ?>',
    columns: [
      { data: 'id' },
      { data: 'quotation_number' },
      { data: 'quotation_date' },
      { data: 'patient_name' },
      { data: 'doctor_name' },
      { data: 'net_amount', className: 'text-end text-nowrap' },
      { data: 'status_badge', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap' }
    ],
    columnDefs: [{ targets: -1, width: '140px' }]
  });
});
</script>
