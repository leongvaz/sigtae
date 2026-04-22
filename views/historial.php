<?php
$historial = $historial ?? [];
$users = $users ?? [];
$tiposEvento = $tiposEvento ?? [];
$filtroTipo = $filtroTipo ?? '';
$filtroUsuario = $filtroUsuario ?? '';
$filtroTarea = $filtroTarea ?? '';
$taskRepo = $taskRepo ?? null;
$userById = [];
foreach ($users as $u) { $userById[$u['id']] = $u; }
?>

<?php sigtae_page_header('Historial del sistema', 'Auditoría de todos los eventos registrados'); ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Tipo de evento</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($tiposEvento as $te): ?>
                        <option value="<?= htmlspecialchars($te) ?>" <?= $filtroTipo === $te ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('_', ' ', $te)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Usuario</label>
                <select name="usuario" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= $filtroUsuario === $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Tarea ID</label>
                <input type="text" name="tarea" class="form-control form-control-sm" value="<?= htmlspecialchars($filtroTarea) ?>" placeholder="task-001">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath ?? '') ?>/historial.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($historial)): ?>
            <?php sigtae_empty_state('No hay registros con los filtros seleccionados.', 'bi-clock-history'); ?>
        <?php else: ?>
        <?php
        $iconoEvento = [
            'tarea_creada'               => 'bi-plus-circle text-primary',
            'tarea_editada'              => 'bi-pencil text-info',
            'cambio_fecha_limite'        => 'bi-calendar-event text-warning',
            'cambio_responsable'         => 'bi-person-gear text-info',
            'cambio_estado'              => 'bi-arrow-repeat text-secondary',
            'evidencia_subida'           => 'bi-cloud-upload text-success',
            'evidencia_eliminada'        => 'bi-trash text-danger',
            'comentario_agregado'        => 'bi-chat-left-text text-muted',
            'evaluacion_registrada'      => 'bi-check2-square text-success',
            'evaluacion_insatisfactoria' => 'bi-x-circle text-danger',
            'delegacion_aplicada'        => 'bi-person-gear text-info',
            'tarea_cancelada'            => 'bi-x-octagon text-danger',
            'tarea_reasignada'           => 'bi-arrow-left-right text-warning',
        ];
        ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaHistorial">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Usuario</th>
                        <th>Tarea</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h):
                        $u = $userById[$h['usuario_id'] ?? ''] ?? null;
                        $task = $taskRepo && !empty($h['tarea_id']) ? $taskRepo->find($h['tarea_id']) : null;
                        $tipo = (string)($h['tipo_evento'] ?? '');
                        $ico = $iconoEvento[$tipo] ?? 'bi-circle text-muted';
                    ?>
                        <tr>
                            <td class="small text-muted text-nowrap"><?= htmlspecialchars($h['fecha_hora'] ?? '') ?></td>
                            <td class="small"><i class="bi <?= $ico ?> me-1"></i><?= htmlspecialchars(str_replace('_', ' ', $tipo)) ?></td>
                            <td class="small"><?= $u ? htmlspecialchars($u['nombre']) : ($h['usuario_id'] ?? '—') ?></td>
                            <td class="small">
                                <?php if ($task): ?>
                                    <a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($task['id']) ?>" class="fw-semibold"><?= htmlspecialchars($task['folio'] ?? $h['tarea_id']) ?></a>
                                <?php else: ?>
                                    <?= htmlspecialchars($h['tarea_id'] ?? '—') ?>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars(mb_substr($h['descripcion'] ?? '', 0, 100)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($historial)): ?>
<script>
$(function() { $('#tablaHistorial').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[0,'desc']] }); });
</script>
<?php endif; ?>
