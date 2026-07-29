<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'paymentsTable';
$columns = ['#','Receipt','Date','Bill','Patient','Amount','Mode','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  RootsDataTable.init('#paymentsTable', {
    ajax: '<?= app_url('payments/datatable') ?>',
    columns: [
      {data:'id'},
      {data:'receipt_number'},
      {data:'payment_date'},
      {data:'bill_number'},
      {data:'patient_name'},
      {data:'amount'},
      {data:'payment_mode'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false, defaultContent:""},
    ]
  });
});
</script>
