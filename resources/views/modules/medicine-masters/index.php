<?php
$actions = '<a href="' . app_url('medicines/create') . '" class="btn btn-primary">Add Medicine</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'medicinesTable';
$columns = ['#','Name','Generic','Type','Dosage','Frequency','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#medicinesTable', {
    ajax: '<?= app_url('medicines/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'generic_name'},
      {data:'medicine_type'},
      {data:'default_dosage'},
      {data:'default_frequency'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
