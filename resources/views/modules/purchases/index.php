<?php
$actions = '<a href="' . app_url('purchases/create') . '" class="btn btn-primary">Create Purchase</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'purchasesTable';
$columns = ['#','Purchase No','Date','Supplier','Invoice','Total','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#purchasesTable', {
    ajax: '<?= app_url('purchases/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'purchase_number'},
      {data:'purchase_date'},
      {data:'supplier_name'},
      {data:'invoice_number'},
      {data:'total'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
