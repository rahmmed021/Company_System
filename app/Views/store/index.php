<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('store.title')) ?></h1>
    <a class="btn btn-primary" href="<?= e(url('/' . current_user()['role'] . '/store/use')) ?>">
        <i class="fa-solid fa-arrow-up-from-bracket"></i> <?= e(__('store.use_material')) ?>
    </a>
</div>

<div class="panel mb-3 no-print">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-3">
            <label class="form-label"><?= e(__('store.from_date')) ?></label>
            <input class="form-control" type="date" name="from_date" value="<?= e($fromDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= e(__('store.to_date')) ?></label>
            <input class="form-control" type="date" name="to_date" value="<?= e($toDate) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= e(__('actions.search')) ?></label>
            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="<?= e(__('store.search_placeholder')) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill" type="submit"><i class="fa-solid fa-magnifying-glass"></i> <?= e(__('actions.search')) ?></button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/' . current_user()['role'] . '/store')) ?>"><?= e(__('actions.reset')) ?></a>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle" data-table="true">
            <thead>
            <tr>
                <th><?= e(__('fields.project')) ?></th>
                <th><?= e(__('fields.material')) ?></th>
                <th><?= e(__('fields.unit')) ?></th>
                <th><?= e(__('store.received')) ?></th>
                <th><?= e(__('store.used')) ?></th>
                <th><?= e(__('store.remaining')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($stocks as $stock): ?>
                <tr>
                    <td><?= e(localized($stock, 'project_name')) ?></td>
                    <td><?= e($stock['material']) ?></td>
                    <td><?= e($stock['unit']) ?></td>
                    <td><?= e(number_format((float)$stock['received_quantity'], 2)) ?></td>
                    <td><?= e(number_format((float)$stock['used_quantity'], 2)) ?></td>
                    <td><strong><?= e(number_format((float)$stock['remaining_quantity'], 2)) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
