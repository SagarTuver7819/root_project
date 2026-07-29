<?php
$actions = '<a href="' . app_url('reference-doctors/create') . '" class="btn btn-primary">Add Reference Doctor</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'referenceDoctorsTable';
$columns = ['#','Code','Name','Clinic/Hospital','Mobile','Specialization','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#referenceDoctorsTable', {
    ajax: '<?= app_url('reference-doctors/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'ref_code'},
      {data:'name'},
      {data:'clinic_hospital'},
      {data:'mobile'},
      {data:'specialization'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
