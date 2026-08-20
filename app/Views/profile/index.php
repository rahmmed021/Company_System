<h1 class="h3 mb-3"><?= e(__('nav.profile')) ?></h1>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <h2 class="h5"><?= e($user['name']) ?></h2>
            <table class="table">
                <tr><th><?= e(__('fields.mobile')) ?></th><td><?= e($user['mobile']) ?></td></tr>
                <tr><th><?= e(__('fields.role')) ?></th><td><?= e(option_label($user['role'])) ?></td></tr>
                <tr><th><?= e(__('fields.status')) ?></th><td><?= e(option_label($user['status'])) ?></td></tr>
                <?php if ($worker): ?>
                    <tr><th><?= e(__('fields.nid')) ?></th><td><?= e($worker['nid']) ?></td></tr>
                    <tr><th><?= e(__('fields.skill')) ?></th><td><?= e($worker['skill']) ?></td></tr>
                    <tr><th><?= e(__('fields.daily_salary')) ?></th><td><?= e(money($worker['daily_salary'])) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel mb-3">
            <h2 class="h5"><?= e(__('nav.payroll')) ?></h2>
            <?php foreach ($balance as $label => $value): ?>
                <div class="d-flex justify-content-between border-bottom py-2"><span><?= e(__('dashboard.' . $label)) ?></span><strong><?= e(money($value)) ?></strong></div>
            <?php endforeach; ?>
        </div>
        <form class="panel" method="post" action="<?= e(url('/profile/password')) ?>">
            <?= csrf_field() ?>
            <label class="form-label"><?= e(__('fields.password')) ?></label>
            <input class="form-control mb-3" type="password" name="password" required minlength="8">
            <button class="btn btn-primary"><i class="fa-solid fa-key"></i> <?= e(__('actions.change_password')) ?></button>
        </form>
    </div>
</div>
