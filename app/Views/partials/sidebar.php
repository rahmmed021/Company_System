<?php
$role = current_user()['role'] ?? 'admin';
$base = '/' . $role;
$adminOnly = $role === 'admin';
$labor = $role === 'labor';
$currentPath = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
$active = static function (string $path) use ($currentPath): string {
    $path = '/' . trim($path, '/');
    return $currentPath === $path || str_starts_with($currentPath . '/', $path . '/') ? ' active' : '';
};
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-mark"><img src="<?= e(asset('images/nousin-logo.svg')) ?>" alt="<?= e(__('app.name')) ?>"></span>
        <span><?= e(__('app.name')) ?></span>
    </div>
    <a class="<?= e($active($base . '/dashboard')) ?>" href="<?= e(url($base . '/dashboard')) ?>"><i class="fa-solid fa-chart-line"></i><?= e(__('nav.dashboard')) ?></a>
    <div class="nav-section"><?= e(__('nav.workforce')) ?></div>
    <?php if ($adminOnly): ?>
        <a class="<?= e($active('/admin/workers')) ?>" href="<?= e(url('/admin/workers')) ?>"><i class="fa-solid fa-people-group"></i><?= e(__('modules.workers')) ?></a>
        <a class="<?= e($active('/admin/worker-projects')) ?>" href="<?= e(url('/admin/worker-projects')) ?>"><i class="fa-solid fa-user-check"></i><?= e(__('modules.worker_projects')) ?></a>
    <?php elseif ($role === 'foreman'): ?>
        <a href="<?= e(url($base . '/worker-projects')) ?>"><i class="fa-solid fa-user-check"></i><?= e(__('modules.worker_projects')) ?></a>
    <?php endif; ?>
    <?php if ($adminOnly || $role === 'foreman'): ?><a class="<?= e($active($base . '/attendance')) ?>" href="<?= e(url($base . '/attendance')) ?>"><i class="fa-solid fa-clipboard-check"></i><?= e(__('modules.attendance')) ?></a><?php endif; ?>
    <div class="nav-section"><?= e(__('nav.payroll')) ?></div>
    <?php if ($adminOnly): ?>
        <a href="<?= e(url('/admin/advances')) ?>"><i class="fa-solid fa-hand-holding-dollar"></i><?= e(__('modules.advances')) ?></a>
        <a href="<?= e(url('/admin/withdrawals')) ?>"><i class="fa-solid fa-money-bill-transfer"></i><?= e(__('modules.withdrawals')) ?></a>
        <a href="<?= e(url('/admin/bonuses')) ?>"><i class="fa-solid fa-circle-plus"></i><?= e(__('modules.bonuses')) ?></a>
        <a href="<?= e(url('/admin/deductions')) ?>"><i class="fa-solid fa-circle-minus"></i><?= e(__('modules.deductions')) ?></a>
        <a href="<?= e(url($base . '/salary')) ?>"><i class="fa-solid fa-money-check-dollar"></i><?= e(__('modules.salary')) ?></a>
    <?php else: ?>
        <?php if ($role === 'foreman'): ?><a href="<?= e(url($base . '/advances')) ?>"><i class="fa-solid fa-hand-holding-dollar"></i><?= e(__('modules.advances')) ?></a><?php endif; ?>
        <a href="<?= e(url($base . '/salary')) ?>"><i class="fa-solid fa-money-check-dollar"></i><?= e(__('modules.salary')) ?></a>
    <?php endif; ?>
    <div class="nav-section"><?= e(__('nav.projects')) ?></div>
    <?php if (!$labor): ?>
        <a href="<?= e(url($base . '/projects')) ?>"><i class="fa-solid fa-diagram-project"></i><?= e(__('modules.projects')) ?></a>
        <a href="<?= e(url($base . '/expenses')) ?>"><i class="fa-solid fa-receipt"></i><?= e(__('modules.expenses')) ?></a>
        <a href="<?= e(url($base . '/materials')) ?>"><i class="fa-solid fa-screwdriver-wrench"></i><?= e(__('modules.materials')) ?></a>
        <?php if ($role === 'foreman'): ?><a href="<?= e(url('/foreman/store')) ?>"><i class="fa-solid fa-boxes-stacked"></i><?= e(__('store.title')) ?></a><?php endif; ?>
        <a href="<?= e(url($base . '/food')) ?>"><i class="fa-solid fa-bowl-food"></i><?= e(__('modules.food')) ?></a>
        <a href="<?= e(url($base . '/vehicles')) ?>"><i class="fa-solid fa-truck-pickup"></i><?= e(__('modules.vehicles')) ?></a>
    <?php endif; ?>
    <?php if ($adminOnly): ?>
        <a href="<?= e(url('/admin/payments')) ?>"><i class="fa-solid fa-sack-dollar"></i><?= e(__('modules.payments')) ?></a>
        <div class="nav-section"><?= e(__('nav.store')) ?></div>
        <a href="<?= e(url('/admin/store')) ?>"><i class="fa-solid fa-boxes-stacked"></i><?= e(__('store.title')) ?></a>
        <a href="<?= e(url('/admin/equipment')) ?>"><i class="fa-solid fa-toolbox"></i><?= e(__('modules.equipment')) ?></a>
        <a href="<?= e(url('/admin/equipment-assignments')) ?>"><i class="fa-solid fa-right-left"></i><?= e(__('modules.equipment_assignments')) ?></a>
    <?php endif; ?>
    <div class="nav-section"><?= e(__('nav.leave')) ?></div>
    <a href="<?= e(url($base . '/leave')) ?>"><i class="fa-regular fa-calendar-check"></i><?= e(__('modules.leave')) ?></a>
    <?php if (!$adminOnly): ?><a href="<?= e(url('/id-card')) ?>"><i class="fa-regular fa-id-card"></i><?= e(__('idcard.title')) ?></a><?php endif; ?>
    <?php if ($adminOnly): ?>
        <div class="nav-section"><?= e(__('nav.reports')) ?></div>
        <a href="<?= e(url($base . '/reports')) ?>"><i class="fa-solid fa-file-lines"></i><?= e(__('nav.reports')) ?></a>
    <?php endif; ?>
    <?php if ($adminOnly): ?>
        <a href="<?= e(url('/admin/admin-expenses')) ?>"><i class="fa-solid fa-wallet"></i><?= e(__('modules.admin_expenses')) ?></a>
        <a href="<?= e(url('/admin/id-cards')) ?>"><i class="fa-regular fa-id-card"></i><?= e(__('modules.id_cards')) ?></a>
        <a href="<?= e(url('/admin/homepage-sections')) ?>"><i class="fa-solid fa-house-chimney"></i><?= e(__('modules.homepage_sections')) ?></a>
        <a href="<?= e(url('/admin/homepage-updates')) ?>"><i class="fa-solid fa-newspaper"></i><?= e(__('modules.homepage_updates')) ?></a>
        <a href="<?= e(url('/admin/homepage-services')) ?>"><i class="fa-solid fa-briefcase"></i><?= e(__('modules.homepage_services')) ?></a>
        <a href="<?= e(url('/admin/homepage-media')) ?>"><i class="fa-regular fa-images"></i><?= e(__('modules.homepage_media')) ?></a>
        <a href="<?= e(url('/admin/notices/create')) ?>"><i class="fa-regular fa-paper-plane"></i><?= e(__('notices.title')) ?></a>
        <a href="<?= e(url('/admin/notifications')) ?>"><i class="fa-regular fa-bell"></i><?= e(__('modules.notifications')) ?></a>
        <a href="<?= e(url('/admin/audit-logs')) ?>"><i class="fa-solid fa-shield-halved"></i><?= e(__('modules.audit_logs')) ?></a>
        <a href="<?= e(url('/admin/login-history')) ?>"><i class="fa-solid fa-clock-rotate-left"></i><?= e(__('modules.login_history')) ?></a>
        <div class="nav-section"><?= e(__('nav.settings')) ?></div>
        <a href="<?= e(url('/admin/users')) ?>"><i class="fa-solid fa-users-gear"></i><?= e(__('modules.users')) ?></a>
        <a href="<?= e(url('/admin/roles')) ?>"><i class="fa-solid fa-user-lock"></i><?= e(__('modules.roles')) ?></a>
        <a href="<?= e(url('/admin/settings')) ?>"><i class="fa-solid fa-sliders"></i><?= e(__('modules.settings')) ?></a>
        <a href="<?= e(url('/admin/backups')) ?>"><i class="fa-solid fa-database"></i><?= e(__('nav.backup')) ?></a>
    <?php endif; ?>
</aside>
