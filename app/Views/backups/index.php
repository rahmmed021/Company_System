<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('nav.backup')) ?></h1>
    <form method="post" action="<?= e(url('/admin/backups/create')) ?>"><?= csrf_field() ?><button class="btn btn-primary"><i class="fa-solid fa-database"></i> <?= e(__('actions.create_backup')) ?></button></form>
</div>
<div class="panel">
    <table class="table" data-table="true">
        <thead><tr><th><?= e(__('fields.id')) ?></th><th><?= e(__('fields.name_en')) ?></th><th><?= e(__('fields.created_at')) ?></th><th><?= e(__('actions.download')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $backup): ?>
            <tr><td><?= e($backup['id']) ?></td><td><?= e($backup['file_name']) ?></td><td><?= e($backup['created_at']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/backups/download/' . $backup['id'])) ?>"><?= e(__('actions.download')) ?></a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
