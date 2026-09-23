<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="h4 mb-0"><?= e($pageTitle ?? $title ?? '') ?></h2>
    </div>
    <?php if (!empty($actions)): ?>
        <div class="d-flex flex-wrap gap-2"><?= $actions ?></div>
    <?php endif; ?>
</div>
