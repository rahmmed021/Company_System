<!doctype html>
<html lang="<?= e(\App\Core\Lang::locale()) ?>" data-theme="<?= e(\App\Core\Session::get('theme', current_user()['theme'] ?? 'light')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(__('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/dt-2.0.8/r-3.0.2/b-3.0.2/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <!-- Favicon / App Icons -->
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('images/nousin-logo.svg')) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset('images/favicon/favicon-16x16.png')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('images/favicon/favicon-32x32.png')) ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= e(asset('images/favicon/favicon-48x48.png')) ?>">
    <link rel="shortcut icon" href="<?= e(url('favicon.ico')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('images/favicon/favicon-180x180.png')) ?>">
    <link rel="manifest" href="<?= e(url('site.webmanifest')) ?>">
    <meta name="theme-color" content="#ffffff">
</head>
<body>
<?php
$topbarNotifications = [];
$topbarUnread = 0;
if (current_user()) {
    $notificationUser = current_user();
    $notificationWhere = ($notificationUser['role'] ?? '') === 'admin'
        ? '(user_id = :user_id OR user_id IS NULL)'
        : 'user_id = :user_id';
    $notificationParams = ['user_id' => $notificationUser['id']];
    $notificationSql = 'SELECT * FROM notifications WHERE ' . $notificationWhere . ' ORDER BY is_read ASC, created_at DESC LIMIT 8';
    $notificationStmt = \App\Core\Database::connection()->prepare($notificationSql);
    $notificationStmt->execute($notificationParams);
    $topbarNotifications = $notificationStmt->fetchAll();
    $countStmt = \App\Core\Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE ' . $notificationWhere . ' AND is_read = 0');
    $countStmt->execute($notificationParams);
    $topbarUnread = (int) $countStmt->fetchColumn();
}
?>
<div class="app-shell">
    <?php require base_path('app/Views/partials/sidebar.php'); ?>
    <main>
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary btn-sm mobile-nav-toggle" type="button" aria-label="<?= e(__('nav.dashboard')) ?>"><i class="fa-solid fa-bars"></i></button>
                <img src="<?= e(asset('images/nousin-logo.svg')) ?>" alt="<?= e(__('app.name')) ?>" class="topbar-logo">
                <strong><?= e(__('app.tagline')) ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= e(__('modules.notifications')) ?>">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($topbarUnread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= e($topbarUnread) ?></span><?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-menu">
                        <div class="dropdown-header"><?= e(__('modules.notifications')) ?></div>
                        <?php foreach ($topbarNotifications as $notice): ?>
                            <a class="dropdown-item notification-item <?= !empty($notice['is_read']) ? '' : 'is-unread' ?>" href="<?= e(url('/notifications/open/' . $notice['id'])) ?>">
                                <strong><?= e(line_or_raw($notice['title_key'] ?? '')) ?></strong>
                                <span><?= e(line_or_raw($notice['body_key'] ?? '')) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$topbarNotifications): ?><div class="dropdown-item text-muted"><?= e(__('messages.empty')) ?></div><?php endif; ?>
                    </div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/language/en')) ?>"><?= e(__('app.english')) ?></a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/language/bn')) ?>"><?= e(__('app.bangla')) ?></a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/theme/light')) ?>"><i class="fa-regular fa-sun"></i></a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/theme/dark')) ?>"><i class="fa-regular fa-moon"></i></a>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/profile')) ?>"><i class="fa-regular fa-user"></i> <?= e(current_user()['name'] ?? '') ?></a>
                <a class="btn btn-sm btn-danger" href="<?= e(url('/logout')) ?>"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
        <div class="content">
            <?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($message = flash('error')): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?>
            <?= $content ?>
        </div>
    </main>
</div>
<script>window.ERP_LANG = "<?= e(\App\Core\Lang::locale()) ?>";</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.0.8/r-3.0.2/b-3.0.2/datatables.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
