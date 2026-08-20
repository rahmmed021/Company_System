<table border="1">
    <thead><tr><?php foreach ($columns as $column): ?><th><?= e(__($column['label'])) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($exportRows as $row): ?>
        <tr><?php foreach ($row as $value): ?><td><?= e($value) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody>
</table>
