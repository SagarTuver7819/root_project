<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'visitsTable';
$columns = ['#','Code','Date','Time','Patient','Doctor','Diagnosis','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#visitsTable', {
    ajax: '<?= app_url('visits/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'visit_code'},
      {data:'visit_date'},
      {data:'visit_time'},
      {data:'patient_name'},
      {data:'doctor_name'},
      {data:'diagnosis'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
