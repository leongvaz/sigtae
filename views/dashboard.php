<?php
$activas = $activas ?? [];
$enProceso = $enProceso ?? [];
$vencidas = $vencidas ?? [];
$incumplidas = $incumplidas ?? [];
$atendidas = $atendidas ?? [];
$pendientesEvaluacion = $pendientesEvaluacion ?? [];
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
        ['label' => 'Pendientes de evaluación', 'value' => count($pendientesEvaluacion), 'icon' => 'bi-list-task', 'color' => '#1d4ed8', 'onClick' => "sigtaeDashboardOpenPendientesEvaluacion()"],
        ['label' => 'En proceso',     'value' => count($enProceso),   'icon' => 'bi-arrow-repeat',    'color' => '#c17d0a', 'onClick' => "sigtaeDashboardOpenEstado('en_proceso')"],
        ['label' => 'Atendidas fuera de tiempo', 'value' => count($vencidas), 'icon' => 'bi-exclamation-triangle', 'color' => '#a16207', 'onClick' => "sigtaeDashboardOpenEstado('vencida')"],
        ['label' => 'Incumplidas',    'value' => count($incumplidas), 'icon' => 'bi-x-octagon',       'color' => '#b91c1c', 'onClick' => "sigtaeDashboardOpenEstado('incumplimiento')"],
        ['label' => 'Atendidas dentro de tiempo', 'value' => count($atendidas), 'icon' => 'bi-check2-circle', 'color' => '#047857', 'onClick' => "sigtaeDashboardOpenEstado('atendida')"],
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
                <?php sigtae_chart_card_open('Cumplimiento por oficina', '% de tareas con evidencia presentada (atendidas) respecto al total de cada oficina.', 240); ?>
                    <canvas id="chartOficina"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-md-6">
                <?php sigtae_chart_card_open('Desempeño por oficina', 'Promedio del % de desempeño solo entre colaboradores con al menos una tarea evaluada (aprobadas sobre evaluadas, por persona).', 240); ?>
                    <canvas id="chartDesempOficina"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <?php /* Ranking horizontal oculto temporalmente (reactivar quitando d-none) */ ?>
            <div class="col-12 d-none">
                <?php sigtae_chart_card_open('Ranking de desempeño', '% de tareas APROBADAS sobre tareas EVALUADAS (Aprobada/Rechazada).', 280); ?>
                    <canvas id="chartRanking"></canvas>
                <?php sigtae_chart_card_close(); ?>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span>Avance por integrante <?= sigtae_info_icon('Desempeño por integrante: % de tareas aprobadas sobre tareas evaluadas (Aprobada/Rechazada).') ?></span>
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
                            <?php
                            $batch = 6;
                            $proximasInit = array_slice($proximasVencer, 0, $batch);
                            $proximasRest = array_slice($proximasVencer, $batch);
                            ?>
                            <div class="sigtae-feed" id="feed-proximas" style="max-height: 430px; overflow: auto;">
                                <ul class="list-group list-group-flush" id="feed-proximas-list">
                                    <?php foreach ($proximasInit as $t): ?>
                                        <?php $nombreResp = $t['responsable_nombre'] ?? ($t['responsable_id'] ?? ''); ?>
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
                                <div class="py-2 px-3 small text-muted border-top d-none" id="feed-proximas-loading">Cargando…</div>
                            </div>
                            <script type="application/json" id="feed-proximas-data"><?= json_encode(array_values($proximasRest), JSON_UNESCAPED_UNICODE) ?></script>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="pane-evidencias">
                        <?php if (empty($ultimasEvidencias)): ?>
                            <?php sigtae_empty_state('Sin evidencias recientes.', 'bi-paperclip'); ?>
                        <?php else: ?>
                            <?php
                            $batch = 6;
                            $evInit = array_slice($ultimasEvidencias, 0, $batch);
                            $evRest = array_slice($ultimasEvidencias, $batch);
                            ?>
                            <div class="sigtae-feed" id="feed-evidencias" style="max-height: 430px; overflow: auto;">
                                <ul class="list-group list-group-flush" id="feed-evidencias-list">
                                    <?php foreach ($evInit as $ev): ?>
                                        <li class="list-group-item py-2">
                                            <div class="small fw-semibold"><?= htmlspecialchars($ev['tarea_folio']) ?></div>
                                            <div class="small text-muted text-truncate"><?= htmlspecialchars(mb_substr($ev['titulo'], 0, 45)) ?></div>
                                            <div class="text-muted" style="font-size: 0.72rem"><?= htmlspecialchars($ev['fecha']) ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="py-2 px-3 small text-muted border-top d-none" id="feed-evidencias-loading">Cargando…</div>
                            </div>
                            <script type="application/json" id="feed-evidencias-data"><?= json_encode(array_values($evRest), JSON_UNESCAPED_UNICODE) ?></script>
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
$rankingIds = array_slice(array_column($perfArea, 'responsable_id'), 0, 10);
$despOfLabels = array_column($desempenoPorOficina, 'nombre');
$despOfData = array_map('floatval', array_column($desempenoPorOficina, 'promedio'));
$despOfIds = array_column($desempenoPorOficina, 'id');
?>
<script>
(function() {
    function initFeed(opts) {
        const root = document.getElementById(opts.rootId);
        const list = document.getElementById(opts.listId);
        const dataEl = document.getElementById(opts.dataId);
        const loadingEl = document.getElementById(opts.loadingId);
        if (!root || !list || !dataEl) return;
        if (root.__sigtaeFeedInit) return;
        root.__sigtaeFeedInit = true;

        let queue = [];
        try { queue = JSON.parse(dataEl.textContent || '[]') || []; } catch (_) { queue = []; }
        let loading = false;
        const BATCH = opts.batch || 6;

        function appendBatch() {
            if (loading) return;
            if (!queue.length) return;
            loading = true;
            if (loadingEl) loadingEl.classList.remove('d-none');
            const chunk = queue.splice(0, BATCH);
            for (const item of chunk) {
                const li = document.createElement('li');
                li.className = opts.liClass;
                li.innerHTML = opts.render(item);
                list.appendChild(li);
            }
            if (loadingEl) loadingEl.classList.add('d-none');
            loading = false;
        }

        root.addEventListener('scroll', function() {
            const nearBottom = (root.scrollTop + root.clientHeight) >= (root.scrollHeight - 40);
            if (nearBottom) appendBatch();
        });
    }

    function initDashboardFeeds() {
        initFeed({
            rootId: 'feed-proximas',
            listId: 'feed-proximas-list',
            dataId: 'feed-proximas-data',
            loadingId: 'feed-proximas-loading',
            liClass: 'list-group-item d-flex justify-content-between align-items-start py-2',
            batch: 6,
            render: function(t) {
                const base = (window.SIGTAE_BASE_PATH || '');
                const href = base + '/tarea.php?id=' + encodeURIComponent(t.id || '');
                const folio = (t.folio || '');
                const titulo = (t.titulo || '');
                const resp = (t.responsable_nombre || t.responsable_id || '');
                const fecha = (t.fecha_limite || '');
                return ''
                    + '<div class="pe-2" style="min-width: 0">'
                    +   '<a class="fw-semibold" href="' + href + '">' + String(folio).replace(/</g,'&lt;') + '</a>'
                    +   '<div class="small text-muted text-truncate">' + String(titulo).slice(0, 50).replace(/</g,'&lt;') + '</div>'
                    +   '<div class="small text-muted">' + String(resp).replace(/</g,'&lt;') + '</div>'
                    + '</div>'
                    + '<span class="badge bg-warning text-dark">' + String(fecha).replace(/</g,'&lt;') + '</span>';
            }
        });
        initFeed({
            rootId: 'feed-evidencias',
            listId: 'feed-evidencias-list',
            dataId: 'feed-evidencias-data',
            loadingId: 'feed-evidencias-loading',
            liClass: 'list-group-item py-2',
            batch: 6,
            render: function(ev) {
                const folio = (ev.tarea_folio || '');
                const titulo = (ev.titulo || '');
                const fecha = (ev.fecha || '');
                return ''
                    + '<div class="small fw-semibold">' + String(folio).replace(/</g,'&lt;') + '</div>'
                    + '<div class="small text-muted text-truncate">' + String(titulo).slice(0, 45).replace(/</g,'&lt;') + '</div>'
                    + '<div class="text-muted" style="font-size: 0.72rem">' + String(fecha).replace(/</g,'&lt;') + '</div>';
            }
        });
    }

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
                options: {
                    ...common,
                    cutout: '58%',
                    plugins: { ...common.plugins, legend: { position: 'right', labels: { boxWidth: 10, font: { size: 11 } } } },
                    onClick: (evt, active, chart) => {
                        if (!active || !active.length) return;
                        const idx = active[0].index;
                        const label = chart.data.labels[idx] || '';
                        const map = {
                            'Asignada': 'asignada',
                            'En proceso': 'en_proceso',
                            'Incumplimiento': 'incumplimiento',
                            'Vencida': 'vencida',
                            'Atendida': 'atendida',
                        };
                        const estado = map[label] || '';
                        if (estado) window.sigtaeDashboardOpenEstado(estado);
                    }
                }
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
                options: {
                    ...common,
                    plugins: { ...common.plugins, legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    onClick: (evt, active, chart) => {
                        if (!active || !active.length) return;
                        const idx = active[0].index;
                        const label = chart.data.labels[idx] || '';
                        const map = { 'Alta': 'alta', 'Media': 'media', 'Baja': 'baja' };
                        const p = map[label] || '';
                        if (p) window.sigtaeDashboardOpenPrioridad(p);
                    }
                }
            });
        }
        const oficinaCtx = document.getElementById('chartOficina');
        if (oficinaCtx && <?= json_encode($oficinaLabels) ?>.length) {
            const oficinaIds = <?= json_encode(array_column($porOficina, 'id')) ?>;
            new Chart(oficinaCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($oficinaLabels) ?>,
                    datasets: [{ label: '% Cumplimiento', data: <?= json_encode($oficinaData) ?>, backgroundColor: 'rgba(74,159,184,0.8)', borderRadius: 6 }]
                },
                options: {
                    ...common,
                    indexAxis: 'y',
                    scales: { x: { max: 100, beginAtZero: true, ticks: { callback: v => v + '%' } } },
                    onClick: (evt, active, chart) => {
                        if (!active || !active.length) return;
                        const idx = active[0].index;
                        const oficinaNombre = chart.data.labels[idx] || '';
                        const oficinaId = (oficinaIds && oficinaIds[idx]) ? oficinaIds[idx] : '';
                        if (oficinaId) window.sigtaeDashboardOpenOficina(oficinaId, oficinaNombre);
                    }
                }
            });
        }
        const rankingCtx = document.getElementById('chartRanking');
        if (rankingCtx && <?= json_encode($rankingLabels) ?>.length) {
            const rankingIds = <?= json_encode($rankingIds) ?>;
            new Chart(rankingCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($rankingLabels) ?>,
                    datasets: [{ label: '% Desempeño', data: <?= json_encode($rankingData) ?>, backgroundColor: 'rgba(13,125,92,0.75)', borderRadius: 6 }]
                },
                options: {
                    ...common,
                    indexAxis: 'y',
                    scales: { x: { max: 100, beginAtZero: true, ticks: { callback: v => v + '%' } } },
                    onClick: (evt, active, chart) => {
                        if (!active || !active.length) return;
                        const idx = active[0].index;
                        const rid = (rankingIds && rankingIds[idx]) ? rankingIds[idx] : '';
                        const nombre = chart.data.labels[idx] || '';
                        if (rid) window.sigtaeDashboardOpenResponsable(rid, nombre);
                    }
                }
            });
        }
        const despOfCtx = document.getElementById('chartDesempOficina');
        if (despOfCtx && <?= json_encode($despOfLabels) ?>.length) {
            const despOfIds = <?= json_encode($despOfIds) ?>;
            new Chart(despOfCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($despOfLabels) ?>,
                    datasets: [{ label: '% Promedio', data: <?= json_encode($despOfData) ?>, backgroundColor: 'rgba(26,77,109,0.7)', borderRadius: 6 }]
                },
                options: {
                    ...common,
                    indexAxis: 'y',
                    scales: { x: { max: 100, beginAtZero: true, ticks: { callback: v => v + '%' } } },
                    onClick: (evt, active, chart) => {
                        if (!active || !active.length) return;
                        const idx = active[0].index;
                        const oid = (despOfIds && despOfIds[idx]) ? despOfIds[idx] : '';
                        const nombre = chart.data.labels[idx] || '';
                        if (oid && oid !== 'sin-oficina') window.sigtaeDashboardOpenOficina(oid, nombre);
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { initDashboardFeeds(); buildCharts(); });
    } else {
        initDashboardFeeds();
        buildCharts();
    }
    window.addEventListener('sigtae:pageLoaded', function() { initDashboardFeeds(); });
})();
</script>
