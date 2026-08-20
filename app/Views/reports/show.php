<?php
$relationRow = static function (string $field, mixed $id) use ($relations): ?array {
    foreach (($relations[$field]['rows'] ?? []) as $row) {
        if ((string) $row['id'] === (string) $id) {
            return $row;
        }
    }
    return null;
};
$cell = static function (array $column, array $row) use ($relations, $relationRow): string {
    $field = $column['field'];
    $type = $column['type'] ?? 'text';
    if ($type === 'relation') {
        return display_value($field, $row[$field] ?? '', $relations);
    }
    if ($type === 'worker_code') {
        $worker = $relationRow('worker_id', $row['worker_id'] ?? null);
        return e($worker['id_number'] ?? '');
    }
    if ($type === 'worker_photo') {
        $worker = $relationRow('worker_id', $row['worker_id'] ?? null);
        return '<img class="avatar-sm" src="' . e(public_file($worker['photo_path'] ?? null)) . '" alt="' . e($worker['full_name'] ?? '') . '">';
    }
    if ($type === 'photo') {
        return '<img class="avatar-sm" src="' . e(public_file($row[$field] ?? null)) . '" alt="">';
    }
    if ($type === 'password') {
        return '<button class="btn btn-sm btn-outline-secondary password-toggle" type="button" data-hidden="••••••••" data-secret="' . e(__('reports.password_protected')) . '" aria-label="' . e(__('fields.password')) . '"><i class="fa-regular fa-eye"></i> <span>••••••••</span></button>';
    }
    if ($type === 'salary_advance') {
        $advance = (new \App\Repositories\BaseRepository('advances'))->sum(
            'amount',
            'worker_id = :worker AND date = :advance_date AND status = :status AND deleted_at IS NULL',
            ['worker' => (int) ($row['worker_id'] ?? 0), 'advance_date' => (string) ($row['transaction_date'] ?? ''), 'status' => 'approved']
        );
        return e(money($advance));
    }
    if ($type === 'salary_overtime_hours') {
        $attendance = !empty($row['attendance_id']) ? ((new \App\Repositories\BaseRepository('attendance'))->find((int) $row['attendance_id']) ?: []) : [];
        return e(number_format((float) ($attendance['overtime_hours'] ?? 0), 2));
    }
    if ($type === 'salary_total') {
        $advance = (new \App\Repositories\BaseRepository('advances'))->sum(
            'amount',
            'worker_id = :worker AND date = :advance_date AND status = :status AND deleted_at IS NULL',
            ['worker' => (int) ($row['worker_id'] ?? 0), 'advance_date' => (string) ($row['transaction_date'] ?? ''), 'status' => 'approved']
        );
        return e(money((float) ($row['amount'] ?? 0) + (float) ($row['overtime_amount'] ?? 0) - $advance));
    }
    return display_value($field, $row[$field] ?? '', $relations);
};
?>
<div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <div>
        <h1 class="h3 mb-1"><?= e(__($config['title'])) ?></h1>
        <div class="text-muted"><?= e(__('reports.print_hint')) ?></div>
    </div>
    <div class="btn-group flex-wrap">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print"></i> <?= e(__('actions.print')) ?></button>
        <a class="btn btn-outline-danger" href="<?= e(url('/' . current_user()['role'] . '/reports/' . $module . '/export/pdf?' . http_build_query($_GET))) ?>"><?= e(__('actions.pdf')) ?></a>
        <a class="btn btn-outline-success" href="<?= e(url('/' . current_user()['role'] . '/reports/' . $module . '/export/excel?' . http_build_query($_GET))) ?>"><?= e(__('actions.excel')) ?></a>
        <a class="btn btn-outline-primary" href="<?= e(url('/' . current_user()['role'] . '/reports/' . $module . '/export/csv?' . http_build_query($_GET))) ?>"><?= e(__('actions.csv')) ?></a>
    </div>
</div>
<div class="panel mb-3 no-print">
    <form class="row g-2">
        <div class="col-lg-3"><label class="form-label"><?= e(__('actions.search')) ?></label><input class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('reports.search_placeholder')) ?>"></div>
        <div class="col-lg-2"><label class="form-label"><?= e(__('reports.date_from')) ?></label><input class="form-control" type="date" name="date_from" value="<?= e($_GET['date_from'] ?? '') ?>"></div>
        <div class="col-lg-2"><label class="form-label"><?= e(__('reports.date_to')) ?></label><input class="form-control" type="date" name="date_to" value="<?= e($_GET['date_to'] ?? '') ?>"></div>
        <div class="col-lg-2"><label class="form-label"><?= e(__('fields.status')) ?></label><input class="form-control" name="status" value="<?= e($_GET['status'] ?? '') ?>"></div>
        <div class="col-lg-3 d-flex align-items-end gap-2"><button class="btn btn-primary"><i class="fa-solid fa-filter"></i> <?= e(__('actions.filter')) ?></button><a class="btn btn-outline-secondary" href="?"><?= e(__('actions.reset')) ?></a></div>
    </form>
</div>
<div class="panel">
    <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
        <div>
            <h2 class="h5 mb-1"><?= e(env('APP_NAME', __('app.name'))) ?></h2>
            <div><?= e(__($config['title'])) ?></div>
        </div>
        <div class="text-end">
            <div><?= e(__('reports.generated')) ?>: <?= e(date('Y-m-d H:i')) ?></div>
            <div><?= e(__('reports.generated_by')) ?>: <?= e(current_user()['name']) ?></div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-sm" data-table="<?= empty($print) ? 'true' : 'false' ?>">
            <thead><tr><?php foreach ($columns as $column): ?><th><?= e(__($column['label'])) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr><?php foreach ($columns as $column): ?><td><?= $cell($column, $row) ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="text-muted py-3"><?= e(__('messages.empty')) ?></div><?php endif; ?>
    <div class="mt-5 d-flex justify-content-between report-summary">
        <div>
            <strong><?= e(__('reports.summary')) ?></strong><br>
            <?php if ($module === 'attendance'): ?>
                <?= e(__('reports.total_overtime_hours')) ?>: <?= e(number_format((float) ($summary['total_overtime_hours'] ?? 0), 2)) ?><br>
                <?= e(__('reports.total_salary')) ?>: <?= e(money($summary['total_salary'] ?? 0)) ?>
            <?php elseif ($module === 'salary'): ?>
                <?= e(__('reports.total_amount')) ?>: <?= e(money($summary['total_amount'] ?? 0)) ?><br>
                <?= e(__('reports.total_overtime_hours')) ?>: <?= e(number_format((float) ($summary['total_overtime_hours'] ?? 0), 2)) ?><br>
                <?= e(__('reports.total_overtime_amount')) ?>: <?= e(money($summary['total_overtime_amount'] ?? 0)) ?><br>
                <?= e(__('reports.total_advance')) ?>: <?= e(money($summary['total_advance'] ?? 0)) ?>
            <?php else: ?>
                <?= e(__('dashboard.receivable')) ?>: <?= e(money($summary['receivable'] ?? 0)) ?>
            <?php endif; ?>
        </div>
        <div class="text-center border-top pt-2" style="min-width:220px;"><?= e(__('reports.signature')) ?></div>
    </div>
</div>
