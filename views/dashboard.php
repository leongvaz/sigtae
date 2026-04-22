<?php
$activas = $activas ?? [];
$enProceso = $enProceso ?? [];
$vencidas = $vencidas ?? [];
$incumplidas = $incumplidas ?? [];
$atendidas = $atendidas ?? [];
$promedioArea = $promedioArea ?? 0;
$porEstado = $porEstado ?? [];
$porPrioridad = $porPrioridad ?? [];
$porOficina = $porOficina ?? [];
$perfArea = $perfArea ?? [];
$proximasVencer = $proximasVencer ?? [];
$ultimasEvidencias = $ultimasEvidencias ?? [];
$userRepo = $userRepo ?? null;
$desempenoPorOficina = $desempenoPorOficina ?? [];
$integrantesDashboard = $integrantesDashboard ?? [];
?>

<?php if (!empty($successSeed)): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($successSeed) ?>
        <a href="<?= htmlspecialchars($basePath ?? '') ?>/seguimiento.php" class="alert-link">Ver seguimiento</a>
    </div>
<?php endif; ?>

<?php
$pageActions = '<a href="' . htmlspecialchars($basePath ?? '') . '/reportes.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-graph-up"></i> Ir a reportes</a>';
sigtae_page_header('Dashboard', 'Resumen operativo y cumplimiento del departamento', $pageActions);
?>

<!-- ================= KPIs ================= -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['label' => 'Tareas activas', 'value' => count($activas),     'icon' => 'bi-list-task',       'color' => '#1d4ed8'],
        ['label' => 'En proceso',     'value' => count($enProceso),   'icon' => 'bi-arrow-repeat',    'color' => '#c17d0a'],
        ['label' => 'Vencidas',       'value' => count($vencidas),    'icon' => 'bi-exclamation-triangle', 'color' => '#a16207'],
        ['label' => 'Incumplidas',    'value' => count($incumplidas), 'icon' => 'bi-x-octagon',       'color' => '#b91c1c'],
        ['label' => 'Atendidas',      'value' => count($atendidas),   'icon' => 'bi-check2-circle',   'color' => '#047857'],
        ['label' => 'Desempeño área', 'value' => $promedioArea . '%', 'icon' => 'bi-speedometer2',    'color' => '#1a4d6d'],
    ];
    foreach ($kpis as $k):
    ?>
        <div class="col-6 col-md-4 col-xl-2">
            <?php sigtae_kpi_card($k); ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- ================= Charts + Sidebar ================= -->
<div class="row g-3">
    <div class="col-xl-8">
        <div class="row g-3">
            <div class="col-md-6">
                <?php sigtae_chart_card_open('Tareas por estado', 'Distribución de tareas según su estado actual (asignada, en proceso, vencida, incumplimiento, atendida).', 220); ?>
                    <canvas id="chartEstado"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-md-6">
                <?php sigtae_chart_card_open('Tareas por prioridad', 'Número de tareas agrupadas por prioridad (alta, media, baja).', 220); ?>
                    <canvas id="chartPrioridad"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-md-6">
                <?php sigtae_chart_card_open('Cumplimiento por oficina', '% de tareas atendidas respecto al total de cada oficina.', 240); ?>
                    <canvas id="chartOficina"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-md-6">
                <?php sigtae_chart_card_open('Desempeño por oficina', 'Promedio del % de desempeño de los integrantes con tareas evaluadas, por oficina.', 240); ?>
                    <canvas id="chartDesempOficina"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-12">
                <?php sigtae_chart_card_open('Ranking de desempeño', '% de desempeño por responsable: 100% en tiempo, 50% fuera de tiempo, 0% no presentadas.', 280); ?>
                    <canvas id="chartRanking"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span>Avance por integrante <?= sigtae_info_icon('Desempeño acumulado de cada integrante activo, considerando todas sus tareas evaluadas.') ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($integrantesDashboard)): ?>
                            <?php sigtae_empty_state('Sin tareas asignadas aún.', 'bi-people'); ?>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Integrante</th>
                                            <th>Oficina</th>
                                            <th style="min-width: 220px">% desempeño</th>
                                            <th class="text-end">Tareas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($integrantesDashboard as $row): ?>
                                            <tr>
                                                <td class="small"><?= htmlspecialchars($row['nombre']) ?><br><span class="text-muted"><?= htmlspecialchars($row['rpe']) ?></span></td>
                                                <td class="small"><?= htmlspecialchars($row['oficina']) ?></td>
                                                <td>
                                                    <?php
                                                    $pct = (float)($row['porcentaje'] ?? 0);
                                                    $bar = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                                                    ?>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?= $bar ?>" role="progressbar"
                                                             style="width: <?= min(100, max(0, $pct)) ?>%"
                                                             aria-valuenow="<?= htmlspecialchars((string)$pct) ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?= htmlspecialchars((string)$pct) ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end small"><?= (int)($row['evaluadas'] ?? 0) ?> / <?= (int)($row['total'] ?? 0) ?> eval.</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Sidebar derecho: tabs ================= -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist" style="margin: 0;">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-proximas" type="button">
                            <i class="bi bi-hourglass-split me-1"></i> Próximas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-evidencias" type="button">
                            <i class="bi bi-paperclip me-1"></i> Evidencias
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-top" type="button">
                            <i class="bi bi-trophy me-1"></i> Top
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-proximas">
                        <?php if (empty($proximasVencer)): ?>
                            <?php sigtae_empty_state('No hay tareas próximas a vencer.', 'bi-calendar-check'); ?>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($proximasVencer as $t): ?>
                                    <?php
                                    $resp = $userRepo ? $userRepo->find($t['responsable_id'] ?? '') : null;
                                    $nombreResp = $resp ? $resp['nombre'] : ($t['responsable_id'] ?? '');
                                    ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                                        <div class="pe-2" style="min-width: 0">
                                            <a class="fw-semibold" href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id'] ?? '') ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a>
                                            <div class="small text-muted text-truncate"><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 50)) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($nombreResp) ?></div>
                                        </div>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($t['fecha_limite'] ?? '') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-evidencias">
                        <?php if (empty($ultimasEvidencias)): ?>
                            <?php sigtae_empty_state('Sin evidencias recientes.', 'bi-paperclip'); ?>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($ultimasEvidencias as $ev): ?>
                                    <li class="list-group-item py-2">
                                        <div class="small fw-semibold"><?= htmlspecialchars($ev['tarea_folio']) ?></div>
                                        <div class="small text-muted text-truncate"><?= htmlspecialchars(mb_substr($ev['titulo'], 0, 45)) ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem"><?= htmlspecialchars($ev['fecha']) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-top">
                        <?php
                        $top = array_slice($perfArea, 0, 5);
                        if (empty($top)):
                            sigtae_empty_state('Sin datos de desempeño.', 'bi-trophy');
                        else:
                            foreach ($top as $idx => $p):
                                $u = $userRepo ? $userRepo->find($p['responsable_id']) : null;
                                $nombre = $u ? $u['nombre'] : $p['responsable_id'];
                                $pct = (int)($p['porcentaje_desempeno'] ?? 0);
                                $badgeCls = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                        ?>
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <span class="small">
                                    <span class="text-muted">#<?= $idx + 1 ?></span>
                                    <?= htmlspecialchars($nombre) ?>
                                </span>
                                <span class="badge <?= $badgeCls ?>"><?= $pct ?>%</span>
                            </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$estadoLabels = ['asignada' => 'Asignada', 'en_proceso' => 'En proceso', 'incumplimiento' => 'Incumplimiento', 'vencida' => 'Vencida', 'atendida' => 'Atendida'];
$estadoData = [];
$estadoColors = ['#3b82f6', '#f59e0b', '#ef4444', '#eab308', '#10b981'];
foreach (['asignada','en_proceso','incumplimiento','vencida','atendida'] as $e) {
    $estadoData[] = $porEstado[$e] ?? 0;
}
$prioridadLabels = ['alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'];
$prioridadData = [ $porPrioridad['alta'] ?? 0, $porPrioridad['media'] ?? 0, $porPrioridad['baja'] ?? 0 ];
$oficinaLabels = array_column($porOficina, 'nombre');
$oficinaData = array_column($porOficina, 'porcentaje');
$rankingLabels = array_slice(array_map(function($p) use ($userRepo) {
    $u = $userRepo ? $userRepo->find($p['responsable_id']) : null;
    return $u ? mb_substr($u['nombre'], 0, 24) : $p['responsable_id'];
}, $perfArea), 0, 10);
$rankingData = array_slice(array_column($perfArea, 'porcentaje_desempeno'), 0, 10);
$despOfLabels = array_column($desempenoPorOficina, 'nombre');
$despOfData = array_map('floatval', array_column($desempenoPorOficina, 'promedio'));
?>
<script>
(function() {
    function buildCharts() {
        const common = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { enabled: true, padding: 8, titleFont: { size: 12 }, bodyFont: { size: 12 } }
            }
        };

        const estadoCtx = document.getElementById('chartEstado');
        if (estadoCtx) {
            new Chart(estadoCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_values($estadoLabels)) ?>,
                    datasets: [{ data: <?= json_encode($estadoData) ?>, backgroundColor: <?= json_encode($estadoColors) ?>, borderWidth: 2, borderColor: '#fff' }]
                },
                options: { ...common, cutout: '58%', plugins: { ...common.plugins, legend: { position: 'right', labels: { boxWidth: 10, font: { size: 11 } } } } }
            });
        }
        const prioridadCtx = document.getElementById('chartPrioridad');
        if (prioridadCtx) {
            new Chart(prioridadCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_values($prioridadLabels)) ?>,
                    datasets: [{ label: 'Tareas', data: <?= json_encode($prioridadData) ?>, backgroundColor: ['#ef4444','#f59e0b','#94a3b8'], borderRadius: 6 }]
                },
                options: { ...common, plugins: { ...common.plugins, legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }
        const oficinaCtx = document.getElementById('chartOficina');
        if (oficinaCtx && <?= json_encode($oficinaLabels) ?>.length) {
            new Chart(oficinaCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($oficinaLabels) ?>,
                    datasets: [{ label: '% Cumplimiento', data: <?= json_encode($oficinaData) ?>, backgroundColor: 'rgba(74,159,184,0.8)', borderRadius: 6 }]
                },
                options: { ...common, indexAxis: 'y', scales: { x: { max: 100, beginAtZero: true, ticks: { callback: v => v + '%' } } } }
            });
        }
        const rankingCtx = document.getElementById('chartRanking');
        if (rankingCtx && <?= json_encode($rankingLabels) ?>.length) {
            new Chart(rankingCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($rankingLabels) ?>,
                    datasets: [{ label: '% Desempeño', data: <?= json_encode($rankingData) ?>, backgroundColor: 'rgba(13,125,92,0.75)', borderRadius: 6 }]
                },
                options: { ...common, indexAxis: 'y', scales: { x: { max: 100, beginAtZero: true, ticks: { callback: v => v + '%' } } } }
            });
        }
        const despOfCtx = document.getElementById('chartDesempOficina');
        if (despOfCtx && <?= json_encode($despOfLabels) ?>.length) {
            new Chart(despOfCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($despOfLabels) ?>,
                    datasets: [{ label: '% Promedio', data: <?= json_encode($despOfData) ?>, backgroundColor: 'rgba(26,77,109,0.7)', borderRadius: 6 }]
                },
                options: { ...common, indexAxis: 'y', scales: { x: { max: 100, beginAtZero: true, ticks: { callback: v => v + '%' } } } }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildCharts);
    } else {
        buildCharts();
    }
})();
</script>
