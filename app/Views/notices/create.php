<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('notices.title')) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/dashboard')) ?>"><i class="fa-solid fa-arrow-left"></i></a>
</div>
<form class="panel" method="post" action="<?= e(url('/admin/notices/send')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label"><?= e(__('notices.recipient_type')) ?></label>
            <select class="form-select" name="recipient_type" data-recipient-type required>
                <option value="single"><?= e(__('notices.single_worker')) ?></option>
                <option value="group"><?= e(__('notices.worker_group')) ?></option>
                <option value="all"><?= e(__('notices.all_workers')) ?></option>
            </select>
        </div>
        <div class="col-md-6" data-recipient-panel="single">
            <label class="form-label"><?= e(__('fields.worker')) ?></label>
            <select class="form-select" name="worker_id">
                <?php foreach ($workers as $worker): ?><option value="<?= e($worker['id']) ?>"><?= e($worker['full_name']) ?> - <?= e(option_label($worker['role'])) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 d-none" data-recipient-panel="group">
            <label class="form-label"><?= e(__('fields.role')) ?></label>
            <select class="form-select" name="worker_type">
                <?php foreach ($workerTypes as $type): ?><option value="<?= e($type) ?>"><?= e(option_label($type)) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= e(__('notices.notice_title')) ?></label>
            <input class="form-control" name="title" required>
        </div>
        <div class="col-12">
            <label class="form-label"><?= e(__('notices.message')) ?></label>
            <textarea class="form-control" name="message" rows="5" required></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary" type="submit"><i class="fa-regular fa-paper-plane"></i> <?= e(__('notices.send')) ?></button>
    </div>
</form>
