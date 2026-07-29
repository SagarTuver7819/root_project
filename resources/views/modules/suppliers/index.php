<?php
$actions = '<a href="' . app_url('suppliers/create') . '" class="btn btn-primary">Add Supplier</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'suppliersTable';
$columns = ['#','Name','Contact','Mobile','Email','GST','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#suppliersTable', {
    ajax: '<?= app_url('suppliers/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'contact_person'},
      {data:'mobile'},
      {data:'email'},
      {data:'gst_number'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
