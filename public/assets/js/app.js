(function () {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3500
    };

    function csrfHeaders() {
        return {
            'X-CSRF-TOKEN': window.CSRF_TOKEN || $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
    }

    function applySidebarState() {
        const wrapper = document.getElementById('appWrapper');
        if (!wrapper) return;
        const state = localStorage.getItem('sidebar_state') || 'expanded';
        wrapper.classList.toggle('sidebar-collapsed', state === 'collapsed');
    }

    document.addEventListener('DOMContentLoaded', function () {
        applySidebarState();

        const wrapper = document.getElementById('appWrapper');
        const toggle = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (toggle && wrapper) {
            toggle.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    wrapper.classList.toggle('sidebar-open');
                    backdrop && backdrop.classList.toggle('show', wrapper.classList.contains('sidebar-open'));
                    return;
                }
                const collapsed = wrapper.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar_state', collapsed ? 'collapsed' : 'expanded');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                wrapper.classList.remove('sidebar-open');
                backdrop.classList.remove('show');
            });
        }

        document.querySelectorAll('.nav-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (wrapper && wrapper.classList.contains('sidebar-collapsed') && window.innerWidth >= 992) {
                    return;
                }
                const target = document.getElementById(btn.dataset.target);
                btn.classList.toggle('show');
                target && target.classList.toggle('show');
            });
        });

        if (window.bootstrap) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        }

        $(document).on('click', '.toggle-password', function () {
            const input = document.querySelector($(this).data('target'));
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            $(this).find('i').toggleClass('bi-eye bi-eye-slash');
        });

        $(document).on('click', '.btn-delete', function (e) {
            e.preventDefault();
            const url = $(this).data('url');
            const tableSelector = $(this).data('table') || 'table.dataTable';
            Swal.fire({
                title: 'Are you sure?',
                text: $(this).data('message') || 'Do you want to delete this record?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'No, Keep'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { _token: window.CSRF_TOKEN },
                    headers: csrfHeaders(),
                    success: function (res) {
                        toastr.success(res.message || 'Deleted successfully.');
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable(tableSelector)) {
                            $(tableSelector).DataTable().ajax.reload(null, false);
                        } else {
                            setTimeout(function () { location.reload(); }, 600);
                        }
                    },
                    error: function (xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to process your request.');
                    }
                });
            });
        });

        $(document).on('submit', 'form.ajax-form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const submitter = (e.originalEvent && e.originalEvent.submitter) || document.activeElement;
            const formData = new FormData(this);
            if (submitter && submitter.name && this.contains(submitter)) {
                formData.set(submitter.name, submitter.value);
            }
            const $btn = $form.find('[type=submit]').prop('disabled', true);
            const original = $btn.html();
            $btn.data('original', original).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                method: ($form.attr('method') || 'POST').toUpperCase(),
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: csrfHeaders(),
                success: function (res) {
                    if (res && res.success === false) {
                        toastr.error((res && res.message) || 'Unable to process your request.');
                        return;
                    }
                    toastr.success((res && res.message) || 'Saved successfully.');

                    // Prefer explicit form redirect; otherwise use API redirect.
                    // data-reload / data-stay = stay on page (calendar modal, queue actions).
                    const stayOnPage = $form.is('[data-reload],[data-stay]');
                    const redirectTo = $form.data('redirect')
                        || (!stayOnPage && res && res.data && res.data.redirect)
                        || (!stayOnPage && res && res.redirect)
                        || null;

                    if (redirectTo) {
                        setTimeout(function () {
                            window.location.href = redirectTo;
                        }, 350);
                        return;
                    }

                    if (stayOnPage) {
                        setTimeout(function () { window.location.reload(); }, 400);
                        return;
                    }

                    const modal = $form.closest('.modal');
                    if (modal.length && window.bootstrap) {
                        bootstrap.Modal.getInstance(modal[0])?.hide();
                    }
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('table.dataTable')) {
                        $('table.dataTable').DataTable().ajax.reload(null, false);
                    }
                    if (window.rootsCalendar) {
                        window.rootsCalendar.refetchEvents();
                    }
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    toastr.error(res.message || 'Unable to process your request.');
                    if (res.errors) {
                        Object.keys(res.errors).forEach(function (key) {
                            toastr.warning(res.errors[key][0]);
                        });
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).html($btn.data('original') || original);
                }
            });
        });

        function initSelect2(scope) {
            if (!window.jQuery || !jQuery.fn.select2) {
                return;
            }
            const $root = scope ? jQuery(scope) : jQuery(document);
            $root.find('select').each(function () {
                const $el = jQuery(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    return;
                }
                if ($el.hasClass('no-select2') || $el.data('noSelect2') === 1 || $el.data('no-select2') === 1) {
                    return;
                }
                if ($el.closest('.dataTables_length, .dataTables_filter, .paginate_button').length) {
                    return;
                }
                if (
                    $el.hasClass('appointment-status-select') ||
                    $el.hasClass('bill-status-select')
                ) {
                    return;
                }
                // Keep tiny inline table controls native
                if ($el.closest('table.dataTable td, table.dataTable th').length) {
                    return;
                }

                const $modal = $el.closest('.modal');
                const hasEmpty = $el.find('option').filter(function () {
                    return String(jQuery(this).attr('value') ?? '') === '';
                }).length > 0;
                const placeholderText = $el.data('placeholder')
                    || (hasEmpty ? jQuery.trim($el.find('option[value=""]').first().text()) : '')
                    || 'Select';

                const config = {
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: placeholderText || 'Select',
                    allowClear: hasEmpty,
                    dropdownParent: $modal.length ? $modal : jQuery(document.body)
                };

                const ajaxUrl = $el.data('ajax');
                if (ajaxUrl) {
                    config.ajax = {
                        url: ajaxUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term, term: params.term };
                        },
                        processResults: function (data) {
                            const results = (data.data && data.data.results) ? data.data.results : (data.results || []);
                            return { results: results };
                        }
                    };
                    config.minimumInputLength = Number($el.data('minLength') || 1);
                }

                if ($el.prop('multiple')) {
                    config.closeOnSelect = false;
                }

                try {
                    $el.select2(config);
                } catch (err) {
                    // Ignore init errors on unsupported selects
                }
            });
        }

        $(document).on('click', '.clinical-doc-upload', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $btn = $(this);
            const type = $btn.data('type');
            const $form = $btn.closest('.clinical-chart-form');
            const uploadUrl = $form.data('upload-url') || $btn.data('upload-url');
            const fileInput = ($form.length ? $form : $(document))
                .find('.clinical-doc-file[data-type="' + type + '"]')
                .get(0);

            if (!uploadUrl) {
                toastr.error('Upload URL missing. Please refresh the page.');
                return;
            }
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                toastr.warning('Please choose a file first.');
                return;
            }

            const fd = new FormData();
            fd.append('_token', window.CSRF_TOKEN || $('meta[name="csrf-token"]').attr('content') || '');
            fd.append('document_type', type);
            fd.append('document', fileInput.files[0]);
            fd.append('description', fileInput.files[0].name);

            $btn.prop('disabled', true);
            $.ajax({
                url: uploadUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: csrfHeaders(),
                success: function (res) {
                    if (res && res.success === false) {
                        toastr.error((res && res.message) || 'Upload failed.');
                        return;
                    }
                    toastr.success((res && res.message) || 'Uploaded.');
                    const active = document.querySelector('#patientTabs .nav-link.active');
                    if (active) {
                        active.click();
                    } else {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    toastr.error(res.message || 'Upload failed.');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        initSelect2(document);

        // Re-bind when Bootstrap modals open (dropdownParent + late DOM)
        jQuery(document).on('shown.bs.modal', '.modal', function () {
            initSelect2(this);
        });

        // Expose for AJAX-loaded tabs / partials
        window.RootsApp = window.RootsApp || {};
        window.RootsApp.initSelect2 = initSelect2;

        const quickSearch = document.getElementById('quickPatientSearch');
        if (quickSearch) {
            quickSearch.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = this.value.trim();
                    if (q) {
                        window.location.href = (window.APP_URL || '') + '/patients/history?q=' + encodeURIComponent(q);
                    }
                }
            });
        }
    });

    window.RootsApp = Object.assign(window.RootsApp || {}, {
        csrfHeaders: csrfHeaders,
        post: function (url, data) {
            return $.ajax({
                url: url,
                method: 'POST',
                data: Object.assign({ _token: window.CSRF_TOKEN }, data || {}),
                headers: csrfHeaders()
            });
        },
        confirm: function (options) {
            return Swal.fire(Object.assign({
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00AEEF',
                cancelButtonText: 'Cancel'
            }, options || {}));
        }
    });
})();
