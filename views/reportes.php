<?php
$nombreOficina = $nombreOficina ?? '';
$oficinaIdReporte = $oficinaIdReporte ?? '';
$opcionesOficinaReporte = $opcionesOficinaReporte ?? [];
$periodo = $periodo ?? 'semanal';
$fechaRef = $fechaRef ?? date('Y-m-d');
$desde = $desde ?? '';
$hasta = $hasta ?? '';
$labelsDia = $labelsDia ?? [];
$dataDia = $dataDia ?? [];
$filtradas = $filtradas ?? [];

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/seguimiento.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Seguimiento</a>';
sigtae_page_header('Reportes de actividades', $nombreOficina !== '' ? $nombreOficina : 'Indicadores agregados por periodo', $actions);
?>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-1"></i> Filtros</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Oficina</label>
                <select name="oficina" class="form-select form-select-sm">
                    <?php foreach ($opcionesOficinaReporte as $o): ?>
                        <option value="<?= htmlspecialchars($o['id'] ?? '') ?>" <?= ($o['id'] ?? '') === $oficinaIdReporte ? 'selected' : '' ?>><?= htmlspecialchars($o['nombre'] ?? $o['id'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Periodo</label>
                <select name="periodo" class="form-select form-select-sm">
                    <option value="diario"  <?= $periodo === 'diario'  ? 'selected' : '' ?>>Diario</option>
                    <option value="semanal" <?= $periodo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                    <option value="mensual" <?= $periodo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Fecha de referencia</label>
                <input type="date" name="fecha" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaRef) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Rango aplicado</label>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($desde) ?> → <?= htmlspecialchars($hasta) ?></span>
                    <button type="submit" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                </div>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
            <span class="small text-muted align-self-center me-1"><i class="bi bi-file-earmark-arrow-down"></i> Exportar:</span>
            <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?export=1&amp;format=csv&amp;oficina=<?= urlencode($oficinaIdReporte) ?>&amp;periodo=<?= urlencode($periodo) ?>&amp;fecha=<?= urlencode($fechaRef) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?export=1&amp;format=pdf&amp;oficina=<?= urlencode($oficinaIdReporte) ?>&amp;periodo=<?= urlencode($periodo) ?>&amp;fecha=<?= urlencode($fechaRef) ?>"><i class="bi bi-filetype-pdf"></i> PDF</a>
        </div>
    </div>
</div>

<div class="card chart-card">
    <div class="card-header"><i class="bi bi-graph-up me-1"></i> Tareas asignadas por día <?= sigtae_info_icon('Cantidad de tareas nuevas creadas por día dentro del rango seleccionado.') ?></div>
    <div class="card-body p-3">
        <div class="chart-wrap" style="height: 260px;">
            <canvas id="chartReporteDia"></canvas>
        </div>
        <p class="small text-muted mb-0 mt-2">Tareas incluidas en el periodo: <strong><?= count($filtradas) ?></strong></p>
    </div>
</div>

<script>
(function() {
    function build() {
        const ctx = document.getElementById('chartReporteDia');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labelsDia) ?>,
                datasets: [{
                    label: 'Tareas asignadas',
                    data: <?= json_encode(array_map('intval', $dataDia)) ?>,
                    borderColor: 'rgba(26,77,109,0.9)',
                    backgroundColor: 'rgba(74,159,184,0.2)',
                    fill: true,
                    tension: 0.25,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } },
                plugins: { legend: { labels: { boxWidth: 12 } } }
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', build); else build();
})();
</script>
