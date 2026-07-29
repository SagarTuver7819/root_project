<?php
$actions = '<a href="' . app_url('users/create') . '" class="btn btn-primary">Add User</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'usersTable';
$columns = ['#','Name','Username','Email','Phone','Roles','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#usersTable', {
    ajax: '<?= app_url('users/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'username'},
      {data:'email'},
      {data:'phone'},
      {data:'roles'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ]
  });
});
</script>
