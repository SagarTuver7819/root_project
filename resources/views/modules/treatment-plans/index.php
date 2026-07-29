<?php
$actions = '<a href="' . app_url('treatment-plans/create') . '" class="btn btn-primary">Add Plan</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'plansTable';
$columns = ['#','Code','Patient','Doctor','Treatment','Tooth','Amount','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#plansTable', {
    ajax: '<?= app_url('treatment-plans/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'plan_code'},
      {data:'patient_name'},
      {data:'doctor_name'},
      {data:'treatment_name'},
      {data:'tooth_number'},
      {data:'final_amount'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
