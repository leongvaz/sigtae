<?php
$catalogos = $catalogos ?? [];
$anio = $anio ?? (int)date('Y');
$mes = $mes ?? 0;
$zona = $zona ?? '';
$estado = $estado ?? '';
$tecnico = $tecnico ?? '';
$k = $k ?? [];
$expedientes = $expedientes ?? [];

$zonas = $catalogos['zonas'] ?? [];
$estados = $catalogos['estados_expediente'] ?? [];

$meses = [
    0 => 'Todos',
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$actions = ''
    . '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-solicitudes.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-inbox"></i> Solicitudes</a> '
    . '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-expedientes.php" class="btn btn-sm btn-primary"><i class="bi bi-folder2-open"></i> Expedientes</a>';
sigtae_page_header('Metrología', 'Dashboard operativo del ciclo de calibración (Fase 1)', $actions);
?>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-1"></i> Filtros</div>
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Año</label>
                <input class="form-control form-control-sm" type="number" name="anio" value="<?= (int)$anio ?>" min="2020" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Mes</label>
                <select class="form-select form-select-sm" name="mes">
                    <?php foreach ($meses as $n => $lab): ?>
                        <option value="<?= $n ?>" <?= (int)$mes === (int)$n ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Zona</label>
                <select class="form-select form-select-sm" name="zona_id">
                    <option value="">Todas</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= htmlspecialchars($z['id']) ?>" <?= $zona === ($z['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($z['nombre'] ?? $z['id']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select class="form-select form-select-sm" name="estado_expediente">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $st): ?>
                        <option value="<?= htmlspecialchars($st) ?>" <?= $estado === $st ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('_',' ', $st)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <?php
    $kpis = [
        ['label' => 'Programados', 'value' => (int)($k['programados'] ?? 0), 'icon' => 'bi-calendar2-week', 'color' => '#1d4ed8'],
        ['label' => 'Recibidos', 'value' => (int)($k['recibidos'] ?? 0), 'icon' => 'bi-box-arrow-in-down', 'color' => '#0ea5e9'],
        ['label' => 'En proceso', 'value' => (int)($k['en_proceso'] ?? 0), 'icon' => 'bi-arrow-repeat', 'color' => '#c17d0a'],
        ['label' => 'Pend. autorización', 'value' => (int)($k['pendiente_autorizacion'] ?? 0), 'icon' => 'bi-shield-exclamation', 'color' => '#b91c1c'],
        ['label' => 'Listo/Autorizado', 'value' => (int)($k['listo_entrega'] ?? 0), 'icon' => 'bi-shield-check', 'color' => '#1a4d6d'],
        ['label' => 'Entregados', 'value' => (int)($k['entregados'] ?? 0), 'icon' => 'bi-box-arrow-up-right', 'color' => '#047857'],
        ['label' => 'Pend. zona', 'value' => (int)($k['pendientes_zona'] ?? 0), 'icon' => 'bi-inbox', 'color' => '#64748b'],
        ['label' => 'Atrasados', 'value' => (int)($k['atrasados'] ?? 0), 'icon' => 'bi-exclamation-triangle', 'color' => '#a16207'],
    ];
    foreach ($kpis as $kk):
    ?>
        <div class="col-6 col-md-3 col-xl-2">
            <?php sigtae_kpi_card($kk); ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-list-ul me-1"></i> Bandeja rápida</span>
        <span class="text-muted small">Mostrando <?= count($expedientes) ?> expedientes</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($expedientes)): ?>
            <?php sigtae_empty_state('Sin expedientes con esos filtros.', 'bi-inbox'); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaMetDashboard">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Descripción</th>
                            <th>No. serie</th>
                            <th>Zona</th>
                            <th>Estado</th>
                            <th>Prog.</th>
                            <th>Fecha prog.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expedientes as $e): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($e['folio'] ?? '') ?></td>
                                <td><?= htmlspecialchars(mb_substr($e['descripcion'] ?? '', 0, 60)) ?></td>
                                <td><?= htmlspecialchars($e['no_serie'] ?? '') ?></td>
                                <td><?= htmlspecialchars($e['zona_id'] ?? '') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars(str_replace('_',' ', $e['estado_expediente'] ?? '')) ?></span></td>
                                <td><?= !empty($e['programa_anual']) ? '<span class="badge bg-primary">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($e['fecha_programada'] ?? '—') ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/metrologia-expediente.php?id=<?= urlencode($e['id'] ?? '') ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
            $(function() {
                $('#tablaMetDashboard').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    order: [[0,'desc']],
                    pageLength: 10
                });
            });
            </script>
        <?php endif; ?>
    </div>
</div>

