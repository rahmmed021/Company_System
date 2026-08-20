<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('store.use_material')) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/' . current_user()['role'] . '/store')) ?>">
        <i class="fa-solid fa-arrow-left"></i> <?= e(__('actions.cancel')) ?>
    </a>
</div>

<div class="panel">
    <?php if (!$stocks): ?>
        <div class="alert alert-warning mb-0"><?= e(__('store.no_stock')) ?></div>
    <?php else: ?>
        <form method="post" action="<?= e(url('/' . current_user()['role'] . '/store/use')) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label"><?= e(__('store.material_stock')) ?></label>
                    <select class="form-select" name="stock_key" required>
                        <option value=""><?= e(__('store.select_material')) ?></option>
                        <?php foreach ($stocks as $stock): ?>
                            <?php
                            $stockPayload = base64_encode((string) json_encode([
                                'project_id' => (int)$stock['project_id'],
                                'material' => (string)$stock['material'],
                                'unit' => (string)$stock['unit'],
                            ], JSON_UNESCAPED_UNICODE));
                            ?>
                            <option value="<?= e($stockPayload) ?>">
                                <?= e(localized($stock, 'project_name') . ' — ' . $stock['material'] . ' (' . number_format((float)$stock['remaining_quantity'], 2) . ' ' . ($stock['unit'] ?: '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= e(__('store.quantity_used')) ?></label>
                    <input class="form-control" type="number" name="quantity" min="0.01" step="0.01" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= e(__('fields.date')) ?></label>
                    <input class="form-control" type="date" name="use_date" value="<?= e(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= e(__('fields.notes')) ?></label>
                    <input class="form-control" type="text" name="notes">
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= e(__('store.save_usage')) ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>
