<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('worker_profile.title')) ?></h1>
    <div class="btn-group">
        <a class="btn btn-outline-primary" href="<?= e(url('/admin/id-card/' . $worker['id'])) ?>"><i class="fa-regular fa-id-card"></i> <?= e(__('idcard.title')) ?></a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/workers')) ?>"><i class="fa-solid fa-arrow-left"></i></a>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel text-center">
            <img class="id-card-photo mb-3" src="<?= e(public_file($worker['photo_path'] ?? null)) ?>" alt="<?= e($worker['full_name']) ?>">
            <h2 class="h5"><?= e($worker['full_name']) ?></h2>
            <div><?= e(option_label($worker['role'])) ?></div>
            <div><?= e($worker['mobile']) ?></div>
            <div class="mt-2"><strong><?= e($worker['id_number'] ?? '') ?></strong></div>
        </div>
        <form class="panel mt-3" method="post" action="<?= e(url('/admin/workers/profile/' . $worker['id'] . '/password')) ?>">
            <?= csrf_field() ?>
            <h2 class="h5"><?= e(__('worker_profile.change_worker_password')) ?></h2>
            <div class="mb-2">
                <label class="form-label"><?= e(__('fields.password')) ?></label>
                <input class="form-control" type="password" name="password" minlength="8" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= e(__('fields.password_confirmation')) ?></label>
                <input class="form-control" type="password" name="password_confirmation" minlength="8" required>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-key"></i> <?= e(__('actions.change_password')) ?></button>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="row g-3 mb-3">
            <?php foreach (['earned','overtime','advance','withdrawn','balance'] as $key): ?>
                <div class="col-sm-6 col-xl-4"><div class="panel metric"><div class="text-muted"><?= e(__('dashboard.' . $key)) ?></div><div class="fs-5 fw-bold mt-2"><?= e(money($balance[$key] ?? 0)) ?></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="panel mb-3">
            <h2 class="h5"><?= e(__('modules.worker_projects')) ?></h2>
            <table class="table table-sm"><tbody><?php foreach ($assignments as $row): ?><tr><td><?= e(localized($projects[(int) $row['project_id']] ?? [], 'name') ?: $row['project_id']) ?></td><td><?= e(option_label($row['status'])) ?></td><td><?= e($row['start_date']) ?></td></tr><?php endforeach; ?></tbody></table>
        </div>
        <div class="panel mb-3">
            <h2 class="h5"><?= e(__('modules.attendance')) ?></h2>
            <table class="table table-sm"><tbody><?php foreach ($attendance as $row): ?><tr><td><?= e($row['attendance_date']) ?></td><td><?= e(option_label($row['status'])) ?></td><td><?= e(money($row['total_salary'])) ?></td></tr><?php endforeach; ?></tbody></table>
        </div>
        <div class="panel">
            <h2 class="h5"><?= e(__('modules.leave')) ?></h2>
            <table class="table table-sm"><tbody><?php foreach ($leaves as $row): ?><tr><td><?= e($row['start_date']) ?></td><td><?= e($row['end_date']) ?></td><td><?= e(option_label($row['status'])) ?></td></tr><?php endforeach; ?></tbody></table>
        </div>
    </div>
</div>
