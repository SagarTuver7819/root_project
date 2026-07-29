<?php
$actions = '<a href="' . app_url('inventory/create') . '" class="btn btn-primary">Add Item</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'inventoryTable';
$columns = ['#','Code','Name','Category','Brand','Unit','Stock','Min Stock','Rate','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#inventoryTable', {
    ajax: '<?= app_url('inventory/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'item_code'},
      {data:'name'},
      {data:'category_name'},
      {data:'brand'},
      {data:'unit'},
      {data:'current_stock'},
      {data:'minimum_stock'},
      {data:'purchase_rate'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
