window.RootsDataTable = {
    init: function (selector, options) {
        const $table = $(selector);
        if (!$table.length || !$.fn.DataTable) {
            return null;
        }

        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }

        const defaults = {
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            responsive: true,
            order: [[0, 'desc']],
            ajax: {
                url: options.ajax,
                data: function (d) {
                    if (typeof options.filterData === 'function') {
                        Object.assign(d, options.filterData());
                    }
                    const $filters = $(options.filterForm || '.datatable-filters');
                    $filters.find('input, select').each(function () {
                        const name = $(this).attr('name');
                        if (name) {
                            d[name] = $(this).val();
                        }
                    });
                },
                error: function () {
                    if (window.toastr) {
                        toastr.error('Unable to load table data.');
                    }
                }
            },
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...'
            }
        };

        const config = $.extend(true, {}, defaults, options);
        const table = $table.DataTable(config);

        $(document).off('click.rootsFilter').on('click.rootsFilter', '.btn-filter-datatable', function () {
            table.ajax.reload();
        });

        $(document).off('click.rootsReset').on('click.rootsReset', '.btn-reset-datatable', function () {
            const $filters = $($(this).data('form') || '.datatable-filters');
            $filters.find('input, select').val('');
            table.ajax.reload();
        });

        return table;
    }
};
