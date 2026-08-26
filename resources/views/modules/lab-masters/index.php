<?php
$actions = '';
if (can('lab_masters.add')) {
    $actions = '<a href="' . app_url('lab-masters/create') . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Lab</a>';
}
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'labMastersTable';
$columns = ['#', 'Lab Name', 'Status', 'Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  RootsDataTable.init('#labMastersTable', {
    ajax: '<?= app_url('lab-masters/datatable') ?>',
    columns: [
      { data: 'id' },
      { data: 'name' },
      { data: 'status_badge', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap' }
    ],
    columnDefs: [{ targets: -1, width: '120px' }]
  });
});
</script>
