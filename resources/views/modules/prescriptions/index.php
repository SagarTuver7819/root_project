<?php
$actions = '<a href="' . app_url('prescriptions/create') . '" class="btn btn-primary">Add Prescription</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'prescriptionsTable';
$columns = ['#','Number','Date','Patient','Doctor','Diagnosis','Follow Up','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#prescriptionsTable', {
    ajax: '<?= app_url('prescriptions/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'prescription_number'},
      {data:'prescription_date'},
      {data:'patient_name'},
      {data:'doctor_name'},
      {data:'diagnosis'},
      {data:'follow_up_date'},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
