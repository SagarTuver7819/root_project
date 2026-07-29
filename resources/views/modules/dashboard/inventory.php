<?php
$actions = '<a href="' . app_url('inventory/create') . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Item</a>'
    . '<a href="' . app_url('purchases/create') . '" class="btn btn-light"><i class="bi bi-cart-plus me-1"></i>New Purchase</a>';
require __DIR__ . '/../../components/page-header.php';

$statCards = [
    ['Low Stock Items', 'low_stock', 'bi-exclamation-triangle', 'rose', app_url('inventory')],
    ['Suppliers', 'suppliers_count', 'bi-truck', 'teal', app_url('suppliers')],
    ['Open Purchases', 'purchases_open', 'bi-cart-check', 'amber', app_url('purchases')],
    ['Inventory Items', 'inventory_items', 'bi-boxes', 'cyan', app_url('inventory')],
];
?>
<div class="row g-3 mb-4">
<?php foreach ($statCards as $card):
    [$label, $key, $icon, $tone, $href] = $card;
    $value = (string) ($stats[$key] ?? 0);
?>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= e($href) ?>" class="dash-stat dash-stat-<?= e($tone) ?> text-decoration-none">
            <div class="dash-stat-icon"><i class="bi <?= e($icon) ?>"></i></div>
            <div class="dash-stat-body">
                <div class="dash-stat-label"><?= e($label) ?></div>
                <div class="dash-stat-value"><?= e($value) ?></div>
            </div>
        </a>
    </div>
<?php endforeach; ?>
</div>

<div class="card content-card">
    <div class="card-body">
        <h3 class="h5 mb-2">Inventory focus</h3>
        <p class="text-muted mb-0">Track stock levels, suppliers, and purchase orders. Items at or below minimum stock appear in Low Stock.</p>
    </div>
</div>
