<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__($config['title'])) ?></h1>
    <?php if (can_resource_action($key, 'CREATE')): ?><a class="btn btn-primary" href="<?= e(url('/' . current_user()['role'] . '/' . $key . '/create')) ?>"><i class="fa-solid fa-plus"></i> <?= e(__('actions.add')) ?></a><?php endif; ?>
</div>
<?php
$role = current_user()['role'] ?? 'admin';
$query = trim((string) ($_GET['q'] ?? ''));
$dateFrom = (string) ($_GET['date_from'] ?? '');
$dateTo = (string) ($_GET['date_to'] ?? '');
$dateFilterModules = ['materials', 'expenses', 'food', 'vehicles', 'salary'];
if (in_array($key, $dateFilterModules, true)) {
    $dateFrom = $dateFrom !== '' ? $dateFrom : date('Y-m-d');
    $dateTo = $dateTo !== '' ? $dateTo : $dateFrom;
}
$visibleFields = array_slice($config['fields'], 0, 7, true);
if (in_array($key, ['expenses', 'materials'], true) && isset($config['fields']['invoice_image'])) {
    $visibleFields['invoice_image'] = $config['fields']['invoice_image'];
}
if ($key === 'projects' && isset($config['fields']['project_attachment_path'])) {
    $visibleFields['project_attachment_path'] = $config['fields']['project_attachment_path'];
}
if ($key === 'equipment') {
    $visibleFields = [
        'name_en' => $config['fields']['name_en'],
        'category' => $config['fields']['category'],
        'quantity' => $config['fields']['quantity'],
        'created_at' => ['type' => 'text', 'label' => 'fields.created_at'],
        'remaining_after_assignments' => ['type' => 'number', 'label' => 'fields.remaining_after_assignments'],
    ];
    $equipmentRemaining = (new \App\Services\CalculationService())->equipmentRemainingById();
}
if ($key === 'equipment-assignments') {
    $visibleFields = [
        'equipment_id' => $config['fields']['equipment_id'],
        'project_id' => $config['fields']['project_id'],
        'quantity' => $config['fields']['quantity'],
        'issue_date' => $config['fields']['issue_date'],
    ];
}

$relationRow = static function (string $field, mixed $id) use ($relations): ?array {
    foreach (($relations[$field]['rows'] ?? []) as $relationRow) {
        if ((string) $relationRow['id'] === (string) $id) {
            return $relationRow;
        }
    }
    return null;
};
$workerPhotoModules = ['workers', 'attendance', 'worker-projects', 'salary', 'advances', 'withdrawals', 'bonuses', 'deductions', 'leave'];
$showWorkerPhotoColumn = in_array($key, $workerPhotoModules, true) && !($key === 'worker-projects' && $role !== 'admin');
$workerPhoto = static function (array $row) use ($key, $relationRow): ?string {
    if ($key === 'workers') {
        return $row['photo_path'] ?? null;
    }
    $worker = $relationRow('worker_id', $row['worker_id'] ?? null);
    return $worker['photo_path'] ?? null;
};
$skipField = static function (string $field, array $meta) use ($key): bool {
    if ($field === 'password' || $field === 'id') {
        return true;
    }
    if (str_ends_with($field, '_id') && !in_array($key, ['equipment-assignments'], true)) {
        return true;
    }
    if ($key === 'advances' && $field === 'status') {
        return true;
    }
    if ($key === 'salary' && $field === 'amount') {
        return true;
    }
    if ($key === 'salary' && in_array($field, ['transaction_date', 'type', 'amount', 'overtime_amount', 'description'], true)) {
        return true;
    }
    return ($meta['type'] ?? '') === 'file' && in_array($field, ['photo_path'], true);
};
$attachmentActions = static function (array $row): string {
    if (empty($row['project_attachment_path'])) {
        return '<span class="text-muted">' . e(__('actions.no_attachment')) . '</span>';
    }
    $base = '/' . (current_user()['role'] ?? 'admin') . '/projects/attachment/' . (int) $row['id'];
    return '<span class="attachment-actions">'
        . '<a class="btn btn-sm btn-outline-primary" href="' . e(url($base . '/view')) . '" target="_blank"><i class="fa-regular fa-eye"></i> ' . e(__('actions.view')) . '</a>'
        . '<a class="btn btn-sm btn-outline-secondary" href="' . e(url($base . '/download')) . '"><i class="fa-solid fa-download"></i> ' . e(__('actions.download')) . '</a>'
        . '</span>';
};
$moneyColumn = match ($key) {
    'materials', 'food', 'vehicles' => 'total_amount',
    'expenses' => 'amount',
    default => null,
};
$grandTotal = $moneyColumn ? array_sum(array_map(static fn (array $row): float => (float) ($row[$moneyColumn] ?? 0), $rows)) : null;
$salaryRows = [];
$salarySummary = ['overtime_hours' => 0.0, 'overtime_amount' => 0.0, 'advance' => 0.0, 'total' => 0.0];
if ($key === 'salary') {
    $attendanceRepo = new \App\Repositories\BaseRepository('attendance');
    $advanceRepo = new \App\Repositories\BaseRepository('advances');
    foreach ($rows as $salaryRow) {
        $attendanceRow = !empty($salaryRow['attendance_id']) ? ($attendanceRepo->find((int) $salaryRow['attendance_id']) ?: []) : [];
        $advance = $advanceRepo->sum(
            'amount',
            'worker_id = :worker AND date = :advance_date AND status = :status AND deleted_at IS NULL',
            [
                'worker' => (int) ($salaryRow['worker_id'] ?? 0),
                'advance_date' => (string) ($salaryRow['transaction_date'] ?? ''),
                'status' => 'approved',
            ]
        );
        $attendanceAmount = (float) ($salaryRow['amount'] ?? 0);
        $overtimeAmount = (float) ($salaryRow['overtime_amount'] ?? 0);
        $total = $attendanceAmount + $overtimeAmount - $advance;
        $salaryRows[(int) $salaryRow['id']] = [
            'overtime_hours' => (float) ($attendanceRow['overtime_hours'] ?? 0),
            'attendance' => $attendanceAmount,
            'advance' => $advance,
            'total' => $total,
        ];
        $salarySummary['overtime_hours'] += (float) ($attendanceRow['overtime_hours'] ?? 0);
        $salarySummary['overtime_amount'] += $overtimeAmount;
        $salarySummary['advance'] += $advance;
        $salarySummary['total'] += $total;
    }
}
?>
<div class="panel mb-3 no-print">
    <form class="row g-2 align-items-end" method="get">
        <div class="<?= in_array($key, array_merge(['attendance'], $dateFilterModules), true) ? 'col-lg-4' : 'col-lg-8' ?>">
            <label class="form-label"><?= e(__('actions.search')) ?></label>
            <input class="form-control" name="q" value="<?= e($query) ?>" placeholder="<?= e(__('reports.search_placeholder')) ?>">
        </div>
        <?php if ($key === 'attendance' || in_array($key, $dateFilterModules, true)): ?>
            <div class="col-lg-2"><label class="form-label"><?= e(__('reports.date_from')) ?></label><input class="form-control" type="date" name="date_from" value="<?= e($dateFrom) ?>"></div>
            <div class="col-lg-2"><label class="form-label"><?= e(__('reports.date_to')) ?></label><input class="form-control" type="date" name="date_to" value="<?= e($dateTo) ?>"></div>
        <?php endif; ?>
        <div class="col-lg-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> <?= e(__('actions.search')) ?></button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/' . $role . '/' . $key)) ?>"><?= e(__('actions.reset')) ?></a>
        </div>
    </form>
</div>

<?php if ($key === 'worker-projects'): ?>
    <div class="resource-card-grid">
        <?php foreach ($cards as $card): ?>
            <?php $collapse = 'project-card-' . (int) $card['id']; $workers = array_filter(explode('||', (string) ($card['worker_list'] ?? ''))); ?>
            <article class="resource-card">
                <button class="resource-card-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($collapse) ?>" aria-expanded="false">
                    <span class="resource-card-title"><?= e(localized($card, 'name')) ?></span>
                    <span class="resource-card-meta"><?= e(__('dashboard.total_labor')) ?>: <?= e($card['total_labour'] ?? 0) ?></span>
                    <span class="resource-card-amount"><?= e(money($card['total_amount'] ?? 0)) ?></span>
                </button>
                <div class="collapse" id="<?= e($collapse) ?>">
                    <div class="resource-card-details">
                        <div><strong><?= e(__('fields.client_name')) ?>:</strong> <?= e($card['client_name'] ?? '') ?> <?= !empty($card['client_mobile']) ? '(' . e($card['client_mobile']) . ')' : '' ?></div>
                        <div><strong><?= e(__('fields.location')) ?>:</strong> <?= e($card['location'] ?? '') ?></div>
                        <div><strong><?= e(__('fields.work_type_en')) ?>:</strong> <?= e($card['work_type_en'] ?? '') ?></div>
                        <div><strong><?= e(__('fields.work_type_bn')) ?>:</strong> <?= e($card['work_type_bn'] ?? '') ?></div>
                        <div><strong><?= e(__('dashboard.salary')) ?>:</strong> <?= e(money($card['labour_amount'] ?? 0)) ?></div>
                        <div class="mt-2"><strong><?= e(__('fields.description')) ?>:</strong> <?= e(localized($card, 'description')) ?></div>
                        <?php if (!empty($card['project_attachment_path'])): ?><div class="mt-2"><?= $attachmentActions($card) ?></div><?php endif; ?>
                        <div class="mt-2"><strong><?= e(__('modules.workers')) ?></strong><ul class="mb-0"><?php foreach ($workers as $worker): ?><li><?= e($worker) ?></li><?php endforeach; ?></ul></div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$cards): ?><div class="panel text-muted"><?= e(__('messages.empty')) ?></div><?php endif; ?>
<?php elseif ($key === 'attendance'): ?>
    <div class="resource-card-grid">
        <?php foreach ($cards as $card): ?>
            <?php $collapse = 'attendance-card-' . (int) $card['id']; $items = array_filter(explode('||', (string) ($card['attendance_list'] ?? ''))); ?>
            <article class="resource-card">
                <?php if ($role === 'labor'): ?>
                    <div class="resource-card-button" aria-label="<?= e(__('modules.attendance')) ?>">
                        <span class="resource-card-title"><?= e(localized($card, 'name')) ?></span>
                        <span class="resource-card-meta"><?= e(__('reports.total_attendance')) ?>: <?= e($card['total_attendance'] ?? 0) ?></span>
                    </div>
                <?php else: ?>
                    <button class="resource-card-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($collapse) ?>" aria-expanded="false">
                        <span class="resource-card-title"><?= e(localized($card, 'name')) ?></span>
                        <span class="resource-card-meta"><?= e(__('reports.total_attendance')) ?>: <?= e($card['total_attendance'] ?? 0) ?></span>
                    </button>
                    <div class="collapse" id="<?= e($collapse) ?>">
                        <div class="resource-card-details">
                            <div><strong><?= e(__('dashboard.salary')) ?>:</strong> <?= e(money($card['total_salary'] ?? 0)) ?></div>
                            <div class="mt-2"><strong><?= e(__('modules.workers')) ?></strong>
                                <ul class="mb-0 attendance-action-list">
                                    <?php foreach ($items as $item): ?>
                                        <?php [$attendanceId, $label] = array_pad(explode('::', $item, 2), 2, $item); ?>
                                        <li>
                                            <span><?= e($label) ?></span>
                                            <?php if (is_role('admin')): ?>
                                                <span class="attendance-actions no-print">
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/attendance/edit/' . (int) $attendanceId)) ?>"><i class="fa-regular fa-pen-to-square"></i></a>
                                                    <form method="post" action="<?= e(url('/admin/attendance/delete/' . (int) $attendanceId)) ?>" class="d-inline" data-confirm="<?= e(__('messages.confirm_delete')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button></form>
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$cards): ?><div class="panel text-muted"><?= e(__('messages.empty')) ?></div><?php endif; ?>
<?php else: ?>
    <div class="panel">
        <?php if ($grandTotal !== null): ?><div class="alert alert-info mb-3"><strong><?= e(__('reports.grand_total')) ?>:</strong> <?= e(money($grandTotal)) ?></div><?php endif; ?>
        <?php if ($key === 'salary'): ?>
            <div class="row g-2 mb-3">
                <div class="col-sm-6 col-xl-3"><div class="summary-card"><span><?= e(__('reports.total_overtime_hours')) ?></span><strong><?= e(number_format($salarySummary['overtime_hours'], 2)) ?></strong></div></div>
                <div class="col-sm-6 col-xl-3"><div class="summary-card"><span><?= e(__('reports.total_overtime_amount')) ?></span><strong><?= e(money($salarySummary['overtime_amount'])) ?></strong></div></div>
                <div class="col-sm-6 col-xl-3"><div class="summary-card"><span><?= e(__('reports.total_advance')) ?></span><strong><?= e(money($salarySummary['advance'])) ?></strong></div></div>
                <div class="col-sm-6 col-xl-3"><div class="summary-card"><span><?= e(__('reports.total_amount')) ?></span><strong><?= e(money($salarySummary['total'])) ?></strong></div></div>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle" data-table="true">
                <thead>
                <tr>
                    <?php if ($showWorkerPhotoColumn): ?><th><?= e(__('fields.photo')) ?></th><?php endif; ?>
                    <?php if ($key === 'advances'): ?>
                        <th><?= e(__('fields.worker')) ?></th>
                        <th><?= e(__('fields.id_number')) ?></th>
                    <?php elseif ($key === 'salary'): ?>
                        <th><?= e(__('fields.worker')) ?></th>
                        <th><?= e(__('fields.date')) ?></th>
                        <th><?= e(__('fields.overtime_hours')) ?></th>
                        <th><?= e(__('fields.overtime_amount')) ?></th>
                        <th><?= e(__('fields.attendance')) ?></th>
                        <th><?= e(__('dashboard.advance')) ?></th>
                        <th><?= e(__('fields.total_amount')) ?></th>
                    <?php endif; ?>
                    <?php foreach ($visibleFields as $field => $meta): ?>
                        <?php if (!$skipField($field, $meta)): ?><th><?= e(__($meta['label'])) ?></th><?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (can_resource_action($key, 'UPDATE') || can_resource_action($key, 'DELETE')): ?><th class="no-print"><?= e(__('actions.view')) ?></th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php if ($showWorkerPhotoColumn): ?>
                            <td><img class="avatar-sm" src="<?= e(public_file($workerPhoto($row))) ?>" alt="<?= e(display_value('worker_id', $row['worker_id'] ?? '', $relations)) ?>"></td>
                        <?php endif; ?>
                        <?php if ($key === 'advances'): ?>
                            <?php $advanceWorker = $relationRow('worker_id', $row['worker_id'] ?? null); ?>
                            <td><?= e($advanceWorker['full_name'] ?? '') ?></td>
                            <td><?= e($advanceWorker['id_number'] ?? '') ?></td>
                        <?php elseif ($key === 'salary'): ?>
                            <?php
                            $salaryWorker = $relationRow('worker_id', $row['worker_id'] ?? null);
                            $salaryCalc = $salaryRows[(int) $row['id']] ?? ['overtime_hours' => 0, 'attendance' => 0, 'advance' => 0, 'total' => 0];
                            ?>
                            <td><?= e($salaryWorker['full_name'] ?? '') ?></td>
                            <td><?= e($row['transaction_date'] ?? '') ?></td>
                            <td><?= e(number_format((float) $salaryCalc['overtime_hours'], 2)) ?></td>
                            <td><?= e(money($row['overtime_amount'] ?? 0)) ?></td>
                            <td><?= e(money($salaryCalc['attendance'])) ?></td>
                            <td><?= e(money($salaryCalc['advance'])) ?></td>
                            <td><?= e(money($salaryCalc['total'])) ?></td>
                        <?php endif; ?>
                        <?php foreach ($visibleFields as $field => $meta): ?>
                            <?php if (!$skipField($field, $meta)): ?>
                                <td><?= $field === 'project_attachment_path' ? $attachmentActions($row) : ($field === 'remaining_after_assignments' ? e((string) ($equipmentRemaining[(int) $row['id']] ?? 0)) : display_value($field, $row[$field] ?? '', $relations)) ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (can_resource_action($key, 'UPDATE') || can_resource_action($key, 'DELETE')): ?>
                            <td class="no-print">
                                <?php if (can_resource_action($key, 'UPDATE')): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/' . current_user()['role'] . '/' . $key . '/edit/' . $row['id'])) ?>"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                <?php if ($key === 'workers' && is_role('admin')): ?>
                                    <a class="btn btn-sm btn-outline-success" href="<?= e(url('/admin/workers/profile/' . $row['id'])) ?>" title="<?= e(__('worker_profile.title')) ?>"><i class="fa-regular fa-user"></i></a>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/id-card/' . $row['id'])) ?>" title="<?= e(__('idcard.title')) ?>"><i class="fa-regular fa-id-card"></i></a>
                                <?php endif; ?>
                                <?php if ($key === 'id-cards' && is_role('admin')): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/id-card/' . $row['worker_id'])) ?>" title="<?= e(__('idcard.title')) ?>"><i class="fa-regular fa-id-card"></i></a>
                                <?php endif; ?>
                                <?php if (can_resource_action($key, 'DELETE')): ?>
                                    <form method="post" action="<?= e(url('/' . current_user()['role'] . '/' . $key . '/delete/' . $row['id'])) ?>" class="d-inline" data-confirm="<?= e(__('messages.confirm_delete')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
