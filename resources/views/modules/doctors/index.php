<?php
$actions = '<a href="' . app_url('doctors/create') . '" class="btn btn-primary">Add Doctor</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'doctorsTable';
$columns = ['#','Code','Name','Mobile','Specialization','Fee','Slot','User','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#doctorsTable', {
    ajax: '<?= app_url('doctors/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'doctor_code'},
      {data:'name'},
      {data:'mobile'},
      {data:'specialization'},
      {data:'consultation_fee'},
      {data:'slot_duration'},
      {data:'user_name'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
