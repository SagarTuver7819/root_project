<div class="card content-card">
    <?php if (!empty($filtersHtml)): ?>
        <div class="card-body datatable-filter-panel border-bottom py-3">
            <div class="datatable-filters">
                <div class="datatable-filter-fields">
                    <?= $filtersHtml ?>
                </div>
                <div class="datatable-filter-actions">
                    <button type="button" class="btn btn-outline-secondary btn-reset-datatable">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                    <button type="button" class="btn btn-primary btn-filter-datatable">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="card-body">
        <div class="table-responsive">
            <table id="<?= e($tableId ?? 'dataTable') ?>" class="table table-hover align-middle w-100 roots-grid">
                <thead>
                <tr>
                    <?php foreach (($columns ?? []) as $label): ?>
                        <th><?= e($label) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
