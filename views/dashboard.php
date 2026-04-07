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
?>
<div class="mb-4">
    <?php if (!empty($successSeed)): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($successSeed) ?> <a href="<?= htmlspecialchars($basePath ?? '') ?>/seguimiento.php" class="alert-link">Ver seguimiento</a></div>
    <?php endif; ?>
    <h1 class="h3 fw-bold" style="color: var(--sigtae-navy)">Dashboard</h1>
    <p class="text-muted mb-0">Resumen operativo y cumplimiento</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Tareas activas</div>
                <div class="fs-4 fw-bold text-primary"><?= count($activas) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">En proceso</div>
                <div class="fs-4 fw-bold" style="color: var(--sigtae-warning)"><?= count($enProceso) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Vencidas</div>
                <div class="fs-4 fw-bold" style="color: var(--sigtae-warning)"><?= count($vencidas) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Incumplidas</div>
                <div class="fs-4 fw-bold text-danger"><?= count($incumplidas) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Atendidas</div>
                <div class="fs-4 fw-bold text-success"><?= count($atendidas) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card kpi-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Desempeño área</div>
                <div class="fs-4 fw-bold" style="color: var(--sigtae-petrol)"><?= $promedioArea ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Tareas por estado</div>
                    <div class="card-body">
                        <canvas id="chartEstado" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Tareas por prioridad</div>
                    <div class="card-body">
                        <canvas id="chartPrioridad" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Cumplimiento por oficina</div>
                    <div class="card-body">
                        <canvas id="chartOficina" height="180"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Ranking de desempeño</div>
                    <div class="card-body">
                        <canvas id="chartRanking" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Próximas por vencer</div>
            <div class="card-body p-0">
                <?php if (empty($proximasVencer)): ?>
                    <p class="text-muted small p-3 mb-0">No hay tareas próximas a vencer.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($proximasVencer as $t): ?>
                            <?php
                            $resp = $userRepo ? $userRepo->find($t['responsable_id'] ?? '') : null;
                            $nombreResp = $resp ? $resp['nombre'] : ($t['responsable_id'] ?? '');
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id'] ?? '') ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a>
                                    <div class="small text-muted"><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 40)) ?>…</div>
                                </div>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($t['fecha_limite'] ?? '') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Últimas evidencias</div>
            <div class="card-body p-0">
                <?php if (empty($ultimasEvidencias)): ?>
                    <p class="text-muted small p-3 mb-0">Sin evidencias recientes.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($ultimasEvidencias as $ev): ?>
                            <li class="list-group-item">
                                <div class="small"><?= htmlspecialchars($ev['tarea_folio']) ?> — <?= htmlspecialchars(mb_substr($ev['titulo'], 0, 30)) ?>…</div>
                                <div class="text-muted" style="font-size: 0.75rem"><?= htmlspecialchars($ev['fecha']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Top desempeño</div>
            <div class="card-body p-0">
                <?php
                $top = array_slice($perfArea, 0, 5);
                foreach ($top as $p):
                    $u = $userRepo ? $userRepo->find($p['responsable_id']) : null;
                    $nombre = $u ? $u['nombre'] : $p['responsable_id'];
                ?>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="small"><?= htmlspecialchars($nombre) ?></span>
                        <span class="badge bg-success"><?= $p['porcentaje_desempeno'] ?>%</span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($top)): ?>
                    <p class="text-muted small p-3 mb-0">Sin datos.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$estadoLabels = ['asignada' => 'Asignada', 'en_proceso' => 'En proceso', 'incumplimiento' => 'Incumplimiento', 'vencida' => 'Vencida', 'atendida' => 'Atendida'];
$estadoData = [];
$estadoColors = ['#0ea5e9', '#f59e0b', '#ef4444', '#eab308', '#22c55e'];
foreach (['asignada','en_proceso','incumplimiento','vencida','atendida'] as $i => $e) {
    $estadoData[] = $porEstado[$e] ?? 0;
}
$prioridadLabels = ['alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'];
$prioridadData = [ $porPrioridad['alta'] ?? 0, $porPrioridad['media'] ?? 0, $porPrioridad['baja'] ?? 0 ];
$oficinaLabels = array_column($porOficina, 'nombre');
$oficinaData = array_column($porOficina, 'porcentaje');
$rankingLabels = array_slice(array_map(function($p) use ($userRepo) {
    $u = $userRepo ? $userRepo->find($p['responsable_id']) : null;
    return $u ? mb_substr($u['nombre'], 0, 20) : $p['responsable_id'];
}, $perfArea), 0, 10);
$rankingData = array_slice(array_column($perfArea, 'porcentaje_desempeno'), 0, 10);
?>
<script>
(function() {
    const estadoCtx = document.getElementById('chartEstado');
    if (estadoCtx) {
        new Chart(estadoCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_values($estadoLabels)) ?>,
                datasets: [{ data: <?= json_encode($estadoData) ?>, backgroundColor: <?= json_encode($estadoColors) ?> }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
    const prioridadCtx = document.getElementById('chartPrioridad');
    if (prioridadCtx) {
        new Chart(prioridadCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_values($prioridadLabels)) ?>,
                datasets: [{ label: 'Tareas', data: <?= json_encode($prioridadData) ?>, backgroundColor: 'rgba(26,77,109,0.7)' }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }
    const oficinaCtx = document.getElementById('chartOficina');
    if (oficinaCtx && <?= json_encode($oficinaLabels) ?>.length) {
        new Chart(oficinaCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($oficinaLabels) ?>,
                datasets: [{ label: '% Cumplimiento', data: <?= json_encode($oficinaData) ?>, backgroundColor: 'rgba(74,159,184,0.7)' }]
            },
            options: { indexAxis: 'y', responsive: true, scales: { x: { max: 100, beginAtZero: true } } }
        });
    }
    const rankingCtx = document.getElementById('chartRanking');
    if (rankingCtx && <?= json_encode($rankingLabels) ?>.length) {
        new Chart(rankingCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($rankingLabels) ?>,
                datasets: [{ label: '% Desempeño', data: <?= json_encode($rankingData) ?>, backgroundColor: 'rgba(13,125,92,0.7)' }]
            },
            options: { indexAxis: 'y', responsive: true, scales: { x: { max: 100, beginAtZero: true } } }
        });
    }
})();
</script>
