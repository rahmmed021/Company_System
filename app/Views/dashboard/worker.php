<h1 class="h3 mb-3"><?= e(__('nav.dashboard')) ?></h1>
<div class="row g-3 mb-3">
    <?php foreach (['earned','overtime','bonus','advance','withdrawn','deduction','balance'] as $key): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="panel metric">
                <div class="text-muted"><?= e(__('dashboard.' . $key)) ?></div>
                <div class="fs-4 fw-bold mt-3"><?= e(money($balance[$key] ?? 0)) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="h5"><?= e(__('modules.worker_projects')) ?></h2>
            <table class="table table-sm">
                <?php foreach ($assignments as $row): ?><tr><td><?= e(localized($projects[(int) $row['project_id']] ?? [], 'name') ?: $row['project_id']) ?></td><td><?= e(option_label($row['status'])) ?></td><td><?= e($row['start_date']) ?></td></tr><?php endforeach; ?>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="h5"><?= e(__('modules.attendance')) ?></h2>
            <table class="table table-sm">
                <?php foreach ($attendance as $row): ?><tr><td><?= e($row['attendance_date']) ?></td><td><?= e(option_label($row['status'])) ?></td><td><?= e(money($attendanceAdvances[(string) ($row['attendance_date'] ?? '')] ?? 0)) ?></td></tr><?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
