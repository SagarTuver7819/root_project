<?php
$actions = '<a href="' . app_url('follow-ups/create') . '" class="btn btn-primary">Add Follow Up</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'followUpsTable';
$columns = ['#','Date','Patient','Doctor','Reason','Status','Appointment','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#followUpsTable', {
    ajax: '<?= app_url('follow-ups/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'follow_up_date'},
      {data:'patient_name'},
      {data:'doctor_name'},
      {data:'reason'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'appointment_id'},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
