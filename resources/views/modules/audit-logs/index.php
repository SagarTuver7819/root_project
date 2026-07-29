<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'auditLogsTable';
$columns = ['#','Date','User','Module','Action','Record','IP','Actions'];
ob_start();
?>
<div class="filter-field">
    <label class="form-label">Module</label>
    <input class="form-control" name="module" placeholder="Module">
</div>
<div class="filter-field">
    <label class="form-label">From</label>
    <input class="form-control" type="date" name="date_from">
</div>
<div class="filter-field">
    <label class="form-label">To</label>
    <input class="form-control" type="date" name="date_to">
</div>
<?php
$filtersHtml = ob_get_clean();
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#auditLogsTable', {
    ajax: '<?= app_url('audit-logs/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'created_at'},
      {data:'user_name'},
      {data:'module'},
      {data:'action'},
      {data:'record_id'},
      {data:'ip_address'},
      {data:'actions', orderable:false, searchable:false, defaultContent:""},
    ]
  });
});
</script>
