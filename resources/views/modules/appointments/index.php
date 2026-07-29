<?php
$actions = '<a href="' . app_url('calendar') . '" class="btn btn-primary">Book Appointment</a><a href="' . app_url('queue') . '" class="btn btn-light">Queue</a>';
require __DIR__ . '/../../components/page-header.php';
?>
<?php
$tableId = 'appointmentsTable';
$columns = ['#','Code','Date','Start','End','Patient','Mobile','Doctor','Treatment','Status','Actions'];
require __DIR__ . '/../../components/datatable.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const statusClasses = [
    'status-scheduled', 'status-confirmed', 'status-waiting', 'status-checked-in',
    'status-with-doctor', 'status-completed', 'status-cancelled', 'status-no-show'
  ];

  function paintStatusSelect($select) {
    const status = String($select.val() || $select.data('current') || 'scheduled');
    const cls = 'status-' + status.replace(/_/g, '-');
    $select.removeClass(statusClasses.join(' ')).addClass(cls);
  }

  const urlParams = new URLSearchParams(window.location.search);
  const searchCode = urlParams.get('code') || urlParams.get('q') || '';
  const searchId = urlParams.get('id') || urlParams.get('appointment_id') || '';

  const tableConfig = {
    ajax: {
      url: '<?= app_url('appointments/datatable') ?>',
      data: function (d) {
        if (searchCode) d.code = searchCode;
        if (searchId) d.appointment_id = searchId;
      }
    },
    columns: [
      {data:'id'},
      {data:'appointment_code'},
      {data:'appointment_date'},
      {data:'start_time'},
      {data:'end_time'},
      {data:'patient_name'},
      {data:'mobile'},
      {data:'doctor_name'},
      {data:'treatment_name'},
      {data:'status_badge', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    drawCallback: function () {
      $('#appointmentsTable .appointment-status-select').each(function () {
        paintStatusSelect($(this));
      });
    }
  };

  if (searchCode) {
    tableConfig.search = { search: searchCode };
  }

  const table = RootsDataTable.init('#appointmentsTable', tableConfig);

  $(document).on('change', '#appointmentsTable .appointment-status-select', function () {
    const $select = $(this);
    const id = $select.data('id');
    const previous = String($select.data('current') || '');
    const status = String($select.val() || '');
    paintStatusSelect($select);
    if (!id || !status || status === previous) {
      return;
    }

    RootsApp.confirm({
      title: 'Change status?',
      text: 'Update appointment status to "' + status.replace(/_/g, ' ') + '"?',
      confirmButtonText: 'Yes, update'
    }).then(function (result) {
      if (!result.isConfirmed) {
        $select.val(previous);
        paintStatusSelect($select);
        return;
      }

      $select.prop('disabled', true);
      RootsApp.post('<?= app_url('appointments') ?>/' + id + '/status', { status: status })
        .done(function (res) {
          toastr.success(res.message || 'Status updated successfully.');
          $select.data('current', status);
          paintStatusSelect($select);
          if (table) {
            table.ajax.reload(null, false);
          }
        })
        .fail(function (xhr) {
          $select.val(previous);
          paintStatusSelect($select);
          toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to update status.');
        })
        .always(function () {
          $select.prop('disabled', false);
        });
    });
  });
});
</script>
