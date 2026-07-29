<?php
$actions = '<a href="' . app_url('treatment-masters/create') . '" class="btn btn-primary">Add Treatment</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'treatmentsTable';
$columns = ['#','Name','Category','Default Price','Sessions','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#treatmentsTable', {
    ajax: '<?= app_url('treatment-masters/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'category'},
      {data:'default_price'},
      {data:'estimated_sessions'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
