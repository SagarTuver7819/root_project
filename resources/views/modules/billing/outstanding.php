<?php
$actions = '<a href="' . app_url('billing') . '" class="btn btn-light">All Bills</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'outstandingTable';
$columns = ['#','Bill No','Date','Patient','Doctor','Net','Paid','Pending','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const statusClasses = ['status-pending', 'status-partial', 'status-paid', 'status-cancelled'];

  function paintBillStatus($select) {
    const status = String($select.val() || $select.data('current') || 'pending');
    const cls = 'status-' + status.replace(/_/g, '-');
    $select.removeClass(statusClasses.join(' ')).addClass(cls);
  }

  const table = RootsDataTable.init('#outstandingTable', {
    ajax: '<?= app_url('billing/datatable') ?>',
    filterData: function () { return { outstanding_only: 1 }; },
    columns: [
      {data:'id'},
      {data:'bill_number'},
      {data:'billing_date'},
      {data:'patient_name'},
      {data:'doctor_name'},
      {data:'net_amount', className: 'text-end text-nowrap'},
      {data:'paid_amount', className: 'text-end text-nowrap'},
      {data:'pending_amount', className: 'text-end text-nowrap'},
      {data:'status_badge', orderable:false, searchable:false, className: 'text-nowrap'},
      {data:'actions', orderable:false, searchable:false, className: 'text-end text-nowrap'},
    ],
    columnDefs: [
      { targets: -1, width: '140px' }
    ],
    drawCallback: function () {
      $('#outstandingTable .bill-status-select').each(function () {
        paintBillStatus($(this));
      });
    }
  });

  $(document).on('change', '#outstandingTable .bill-status-select', function () {
    const $select = $(this);
    const id = $select.data('id');
    const previous = String($select.data('current') || '');
    const status = String($select.val() || '');
    paintBillStatus($select);
    if (!id || !status || status === previous) return;

    RootsApp.confirm({
      title: 'Change bill status?',
      text: 'Update bill status to "' + status.replace(/_/g, ' ') + '"? Paid / pending amounts may adjust.',
      confirmButtonText: 'Yes, update'
    }).then(function (result) {
      if (!result.isConfirmed) {
        $select.val(previous);
        paintBillStatus($select);
        return;
      }
      $select.prop('disabled', true);
      RootsApp.post('<?= app_url('billing') ?>/' + id + '/status', { status: status })
        .done(function (res) {
          toastr.success(res.message || 'Bill status updated.');
          $select.data('current', status);
          if (table) table.ajax.reload(null, false);
        })
        .fail(function (xhr) {
          $select.val(previous);
          paintBillStatus($select);
          toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to update status.');
        })
        .always(function () {
          $select.prop('disabled', false);
        });
    });
  });
});
</script>
