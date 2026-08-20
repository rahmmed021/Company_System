<?php
$companyUrl = 'https://nousinenterprise.com/';
$photo = $card['photo_path'] ?: ($worker['photo_path'] ?? null);
$role = $card['designation'] ?? $worker['role'] ?? '';
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=136x136&margin=8&data=' . rawurlencode($companyUrl);
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1 class="h3 mb-0"><?= e(__('idcard.title')) ?></h1>
    <div class="btn-group">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print"></i> <?= e(__('actions.print')) ?></button>
        <a class="btn btn-outline-primary" href="<?= e(url(($admin ? '/admin/id-card/download/' . $worker['id'] : '/id-card/download'))) ?>"><i class="fa-solid fa-download"></i> <?= e(__('idcard.download')) ?></a>
    </div>
</div>
<div class="panel id-card-stage">
    <section class="id-card id-card-front" aria-label="<?= e(__('idcard.front')) ?>">
        <div class="id-card-topline"></div>
        <div class="id-card-header">
            <img src="<?= e(asset('images/nousin-logo.svg')) ?>" alt="<?= e(__('app.name')) ?>">
            <div><strong><?= e(__('app.name')) ?></strong><div><?= e(__('idcard.employee_id')) ?></div></div>
        </div>
        <div class="id-card-body">
            <img class="id-card-photo" src="<?= e(public_file($photo)) ?>" alt="<?= e($worker['full_name']) ?>">
            <div class="id-card-info">
                <h2><?= e($worker['full_name']) ?></h2>
                <div class="id-card-role"><?= e(option_label($role)) ?></div>
                <dl>
                    <dt><?= e(__('fields.id_number')) ?></dt><dd><?= e($card['id_number'] ?? $worker['id_number'] ?? '') ?></dd>
                    
                    <dt><?= e(__('fields.mobile')) ?></dt><dd><?= e($card['mobile'] ?? $worker['mobile']) ?></dd>
                    <dt><?= e(__('fields.joining_date')) ?></dt><dd><?= e($worker['joining_date'] ?? '') ?></dd>
                </dl>
            </div>
        </div>
        <div class="id-card-footer"><?= e(__('idcard.carry_notice')) ?></div>
    </section>

    <section class="id-card id-card-back" aria-label="<?= e(__('idcard.back')) ?>">
        <div class="id-card-topline"></div>
        <h2><?= e(__('app.name')) ?></h2>
        <div class="id-card-back-content">
            <div class="id-card-back-details">
                <div class="id-card-back-item">
                    <span><?= e(__('fields.address')) ?></span>
                    <strong><?= e($companyAddress ?: '—') ?></strong>
                </div>
                <div class="id-card-back-item">
                    <span><?= e(__('fields.blood_group')) ?></span>
                    <strong><?= e($worker['blood_group'] ?? '—') ?></strong>
                </div>
                <div class="id-card-back-item">
                    <span><?= e(__('fields.date_of_birth')) ?></span>
                    <strong><?= e($worker['date_of_birth'] ?? '—') ?></strong>
                </div>
            </div>
            <div class="id-card-qr">
                <img src="<?= e($qrUrl) ?>" alt="QR Code">
            </div>
        </div>
        <div class="id-card-footer"><?= e(__('idcard.return_notice')) ?></div>
    </section>
</div>
