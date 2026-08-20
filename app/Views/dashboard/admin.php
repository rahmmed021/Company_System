<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('nav.dashboard')) ?></h1>
    <button class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> <?= e(__('actions.print')) ?></button>
</div>
<div class="row g-3 mb-3">
    <?php
    $cards = [];
    foreach (($stats['worker_type_counts'] ?? []) as $workerType => $total) {
        $cards[] = ['options.' . $workerType, $total, 'fa-people-group'];
    }
    $cards = array_merge($cards, [
        ['dashboard.total_projects', $stats['projects'], 'fa-diagram-project'],
        ['dashboard.total_earning', money($stats['total_earning']), 'fa-sack-dollar'],
        ['dashboard.total_expense', money($stats['total_expense']), 'fa-receipt'],
        ['dashboard.received', money($stats['received']), 'fa-circle-dollar-to-slot'],
        ['dashboard.receivable', money($stats['receivable']), 'fa-scale-balanced'],
        ['dashboard.admin_personal', money($stats['admin_personal']), 'fa-wallet'],
        ['dashboard.estimated_balance', money($stats['estimated_balance']), 'fa-chart-pie'],
        ['dashboard.equipment', $stats['equipment'], 'fa-toolbox'],
        ['dashboard.available_equipment', $stats['available_equipment'], 'fa-box-open'],
        ['dashboard.assigned_equipment', $stats['assigned_equipment'], 'fa-right-left'],
        ['dashboard.equipment_purchase_value', money($stats['equipment_purchase_value'] ?? 0), 'fa-tags'],
        ['dashboard.salary', money($stats['salary']), 'fa-money-check-dollar'],
    ]);
    foreach ($cards as $card): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="panel metric">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="text-muted"><?= e(__($card[0])) ?></span>
                    <i class="fa-solid <?= e($card[2]) ?> text-primary"></i>
                </div>
                <div class="fs-4 fw-bold mt-3"><?= e($card[1]) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <h2 class="h5"><?= e(__('dashboard.finance')) ?></h2>
            <canvas id="financeChart" height="130"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h2 class="h5"><?= e(__('dashboard.store')) ?></h2>
            <canvas id="storeChart" height="160"></canvas>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  new Chart(document.getElementById('financeChart'), {
    type: 'bar',
    data: { labels: ['<?= e(__('dashboard.total_earning')) ?>','<?= e(__('dashboard.total_expense')) ?>','<?= e(__('dashboard.received')) ?>','<?= e(__('dashboard.admin_personal')) ?>'], datasets: [{ data: [<?= (float) $stats['total_earning'] ?>, <?= (float) $stats['total_expense'] ?>, <?= (float) $stats['received'] ?>, <?= (float) $stats['admin_personal'] ?>], backgroundColor: ['#0f8b8d','#f59e0b','#2563eb','#10b981'] }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
  new Chart(document.getElementById('storeChart'), {
    type: 'doughnut',
    data: { labels: ['<?= e(__('dashboard.available_equipment')) ?>','<?= e(__('dashboard.assigned_equipment')) ?>'], datasets: [{ data: [<?= (float) $stats['available_equipment'] ?>, <?= (float) $stats['assigned_equipment'] ?>], backgroundColor: ['#10b981','#f97316'] }] }
  });
});
</script>
