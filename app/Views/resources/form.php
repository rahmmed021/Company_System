<?php $isEdit = !empty($row['id']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__($config['title'])) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(url('/' . current_user()['role'] . '/' . $key)) ?>"><i class="fa-solid fa-arrow-left"></i></a>
</div>
<form class="panel" method="post" enctype="multipart/form-data" action="<?= e(url('/' . current_user()['role'] . '/' . $key . '/' . ($isEdit ? 'update/' . $row['id'] : 'store'))) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach ($config['fields'] as $field => $meta): ?>
            <?php $type = $meta['type'] ?? 'text'; if ($field === 'password' && $isEdit): continue; endif; ?>
            <?php if ($key === 'workers' && !$isEdit && $field === 'id_number'): continue; endif; ?>
            <?php if ($key === 'leave' && !is_role('admin') && in_array($field, ['worker_id', 'status', 'admin_note'], true)): continue; endif; ?>
            <?php if ($key === 'advances' && !is_role('admin') && $field === 'status'): continue; endif; ?>
            <div class="col-md-<?= $type === 'textarea' ? '12' : '6' ?>">
                <label class="form-label"><?= e(__($meta['label'])) ?></label>
                <?php if ($type === 'textarea'): ?>
                    <textarea class="form-control" name="<?= e($field) ?>" rows="3" <?= !empty($meta['required']) ? 'required' : '' ?>><?= e($row[$field] ?? '') ?></textarea>
                <?php elseif ($type === 'select'): ?>
                    <select class="form-select" name="<?= e($field) ?>" <?= !empty($meta['required']) ? 'required' : '' ?>>
                        <?php foreach (($meta['options'] ?? []) as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string)($row[$field] ?? '') === (string)$option ? 'selected' : '' ?>><?= e(option_label($option)) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'relation'): ?>
                    <select class="form-select" name="<?= e($field) ?>" <?= !empty($meta['required']) ? 'required' : '' ?>>
                        <option value=""></option>
                        <?php foreach ($relations[$field]['rows'] ?? [] as $option): ?>
                            <option value="<?= e($option['id']) ?>" <?= (string)($row[$field] ?? '') === (string)$option['id'] ? 'selected' : '' ?>><?= e($option[$relations[$field]['display']] ?? $option['id']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'file'): ?>
                    <input class="form-control" type="file" name="<?= e($field) ?>" accept="<?= $key === 'homepage-media' ? 'image/png,image/jpeg,image/webp,video/mp4,video/webm' : 'image/png,image/jpeg,image/webp' ?>">
                    <?php if (!empty($row[$field])): ?><div class="small mt-1"><?= display_value($field, $row[$field]) ?></div><?php endif; ?>
                <?php elseif ($type === 'attachment'): ?>
                    <input class="form-control" type="file" name="<?= e($field) ?>" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,image/png,image/jpeg,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                    <?php if (!empty($row[$field])): ?><div class="small mt-1"><?= e($row['project_attachment_name'] ?? basename((string) $row[$field])) ?></div><?php endif; ?>
                <?php else: ?>
                    <input class="form-control" type="<?= e($type === 'money' ? 'number' : $type) ?>" step="<?= $type === 'money' || $type === 'number' ? '0.01' : '' ?>" name="<?= e($field) ?>" value="<?= e($row[$field] ?? '') ?>" <?= !empty($meta['required']) ? 'required' : '' ?> <?= !empty($meta['readonly']) ? 'readonly' : '' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> <?= e(__('actions.save')) ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/' . current_user()['role'] . '/' . $key)) ?>"><?= e(__('actions.cancel')) ?></a>
    </div>
</form>
