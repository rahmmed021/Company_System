<div class="auth-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <img src="<?= e(asset('images/nousin-logo.svg')) ?>" alt="<?= e(__('app.name')) ?>" class="login-logo">
            <h1 class="h4 mb-1"><?= e(__('auth.login_title')) ?></h1>
            <div class="text-muted small"><?= e(__('auth.login_hint')) ?></div>
        </div>
        <div class="d-flex gap-1">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/language/en')) ?>">EN</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/language/bn')) ?>">BN</a>
        </div>
    </div>
    <?php if ($message = flash('error')): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?>
    <?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/login')) ?>" class="needs-validation">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label"><?= e(__('auth.email_mobile')) ?></label>
            <input class="form-control" name="login" required autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label"><?= e(__('auth.password')) ?></label>
            <input class="form-control" type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-lock"></i> <?= e(__('auth.login')) ?></button>
    </form>
</div>
