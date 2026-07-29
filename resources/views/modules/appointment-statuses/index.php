<?php
$actions = '<a href="' . app_url('appointment-statuses/create') . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Status</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'statusesTable';
$columns = ['#', 'Name', 'Slug', 'Color', 'Badge Style', 'Sort Order', 'Status', 'Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#statusesTable', {
    ajax: '<?= app_url('appointment-statuses/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'slug'},
      {data:'color_badge', orderable:false, searchable:false},
      {data:'badge_class'},
      {data:'sort_order'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
