<?php
$catalogos = $catalogos ?? [];
$zonas = $zonas ?? ($catalogos['zonas'] ?? []);
$areas = $areas ?? ($catalogos['areas'] ?? []);
$anio = $anio ?? (int)date('Y');
$zonaId = $zonaId ?? '';
$estado = $estado ?? '';
$solicitudes = $solicitudes ?? [];
$error = $error ?? '';
$success = $success ?? '';

$canManage = !empty($metPerm) ? $metPerm->canManage($currentUser ?? null) : true;

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Solicitudes recibidas', 'Registro y control de solicitudes (entrada de zonas)', $actions);
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card mb-3">
    <div class="card-header card-header-accent"><i class="bi bi-plus-circle me-1"></i> Nueva solicitud</div>
    <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="action" value="crear_solicitud">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Año</label>
                <input type="number" class="form-control form-control-sm" name="anio" value="<?= (int)$anio ?>" min="2020" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Folio</label>
                <input type="text" class="form-control form-control-sm" name="folio" placeholder="MET-2026-0001 (opcional)">
                <div class="form-text">Puede corregirse; el sistema valida duplicados y registra bitácora.</div>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="auto_folio" id="autoFolio" checked>
                    <label class="form-check-label small" for="autoFolio">Auto-folio</label>
                </div>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Descripción *</label>
                <input type="text" class="form-control form-control-sm" name="descripcion" required placeholder="Ej. Medidor monofásico ...">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">No. serie *</label>
                <input type="text" class="form-control form-control-sm" name="no_serie" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Marca</label>
                <input type="text" class="form-control form-control-sm" name="marca">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Modelo</label>
                <input type="text" class="form-control form-control-sm" name="modelo">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Zona *</label>
                <select class="form-select form-select-sm" name="zona_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= htmlspecialchars($z['id']) ?>"><?= htmlspecialchars($z['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Área *</label>
                <select class="form-select form-select-sm" name="area_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= htmlspecialchars($a['id']) ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Fecha solicitud</label>
                <input type="date" class="form-control form-control-sm" name="fecha_solicitud" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Fecha programada</label>
                <input type="date" class="form-control form-control-sm" name="fecha_programada">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Vigencia esperada</label>
                <input type="date" class="form-control form-control-sm" name="vigencia_esperada">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="programa_anual" id="pa">
                    <label class="form-check-label small" for="pa">Programa anual</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold mb-1">Observaciones</label>
                <input type="text" class="form-control form-control-sm" name="observaciones" placeholder="Opcional">
            </div>
            <div class="col-12">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save me-1"></i> Registrar solicitud</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-inbox me-1"></i> Bandeja</span>
        <span class="text-muted small"><?= count($solicitudes) ?> solicitudes</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($solicitudes)): ?>
            <?php sigtae_empty_state('Sin solicitudes con esos filtros.', 'bi-inbox'); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaMetSolicitudes">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Descripción</th>
                            <th>Serie</th>
                            <th>Zona</th>
                            <th>Área</th>
                            <th>Programa</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $s): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($s['folio'] ?? '') ?></td>
                                <td><?= htmlspecialchars(mb_substr($s['descripcion'] ?? '', 0, 55)) ?></td>
                                <td><?= htmlspecialchars($s['no_serie'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['zona_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['area_id'] ?? '') ?></td>
                                <td><?= !empty($s['programa_anual']) ? '<span class="badge bg-primary">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($s['fecha_solicitud'] ?? '') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars(str_replace('_',' ', $s['estado'] ?? '')) ?></span></td>
                                <td class="text-end">
                                    <?php if ($canManage && empty($s['expediente_id'])): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="crear_expediente_desde_solicitud">
                                        <input type="hidden" name="solicitud_id" value="<?= htmlspecialchars($s['id'] ?? '') ?>">
                                        <input type="hidden" name="validada" value="1">
                                        <button type="submit" class="btn btn-sm btn-success" title="Crear expediente">
                                            <i class="bi bi-folder-plus"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (!empty($s['expediente_id'])): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/metrologia-expediente.php?id=<?= urlencode($s['expediente_id']) ?>" title="Ver expediente">
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
                $('#tablaMetSolicitudes').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    order: [[6,'desc']],
                    pageLength: 10
                });
            });
            </script>
        <?php endif; ?>
    </div>
</div>

