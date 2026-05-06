<?php
$bitacora = $bitacora ?? [];
$anio = $anio ?? (int)date('Y');
$fZona = $fZona ?? '';
$fArea = $fArea ?? '';
$fFolio = $fFolio ?? '';
$fSerie = $fSerie ?? '';
$fEstado = $fEstado ?? '';
$zonasEntrega = $zonasEntrega ?? [];

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Bitácora', 'Concentrado por equipo (Recepción / Programa anual)', $actions);
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-journal-text me-1"></i> Bitácora (concentrado por equipo)</span>
        <span class="text-muted small"><?= count($bitacora) ?> registros</span>
    </div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end mb-2">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Año</label>
                <input type="number" class="form-control form-control-sm" name="anio" value="<?= (int)$anio ?>" min="2020" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Zona</label>
                <input type="text" class="form-control form-control-sm" name="zona" value="<?= htmlspecialchars($fZona) ?>" placeholder="Ej. DM210">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Área (texto)</label>
                <input type="text" class="form-control form-control-sm" name="area" value="<?= htmlspecialchars($fArea) ?>" placeholder="Ej. MEDICION">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Folio</label>
                <input type="text" class="form-control form-control-sm" name="folio" value="<?= htmlspecialchars($fFolio) ?>" placeholder="2026-0281">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Serie</label>
                <input type="text" class="form-control form-control-sm" name="serie" value="<?= htmlspecialchars($fSerie) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select class="form-select form-select-sm" name="estado">
                    <option value="">Todos</option>
                    <option value="programado" <?= $fEstado === 'programado' ? 'selected' : '' ?>>Programado</option>
                    <option value="no_programado" <?= $fEstado === 'no_programado' ? 'selected' : '' ?>>No Programado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>

        <?php if (empty($bitacora)): ?>
            <?php sigtae_empty_state('Sin registros con esos filtros.', 'bi-journal-text'); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaBitacora">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Folio</th>
                            <th>Serie</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Descripción</th>
                            <th>Zona</th>
                            <th>Área</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bitacora as $b): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($b['folio'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['no_serie'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['marca'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['modelo'] ?? '') ?></td>
                                <td><?= htmlspecialchars(mb_substr($b['descripcion'] ?? '', 0, 60)) ?></td>
                                <td><?= htmlspecialchars($b['zona'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['area'] ?? '') ?></td>
                                <?php $estadoLabel = (($b['estado'] ?? '') === 'programado') ? 'Programado' : 'No Programado'; ?>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($estadoLabel) ?></span></td>
                                <td><?= htmlspecialchars(mb_substr($b['observaciones'] ?? '', 0, 40)) ?></td>
                                <td class="text-end">
                                    <?php if (!empty($b['recepcion_id'])): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/metrologia-recepcion.php?rid=<?= urlencode((string)$b['recepcion_id']) ?>" title="Ver recepción">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
            $(function() {
                $('#tablaBitacora').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    order: [[0,'desc']],
                    pageLength: 15
                });
            });
            </script>
        <?php endif; ?>
    </div>
</div>

