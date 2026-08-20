<h1 class="h3 mb-3"><?= e(__('reports.title')) ?></h1>
<div class="row g-3">
    <?php foreach ($modules as $key => $config): ?>
        <?php if (!in_array(current_user()['role'], $config['roles'], true)): continue; endif; ?>
        <div class="col-sm-6 col-xl-3">
            <a class="panel d-block text-decoration-none text-body" href="<?= e(url('/' . current_user()['role'] . '/reports/' . $key)) ?>">
                <div class="d-flex justify-content-between"><strong><?= e(__($config['title'])) ?></strong><i class="fa-solid fa-file-lines"></i></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
