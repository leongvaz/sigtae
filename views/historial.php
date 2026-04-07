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
<div class="mb-4">
    <h1 class="h3 fw-bold">Historial del sistema</h1>
    <p class="text-muted mb-0">Auditoría de eventos</p>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Tipo</label>
                <select name="tipo" class="form-select form-select-sm" style="width:auto">
                    <option value="">Todos</option>
                    <?php foreach ($tiposEvento as $te): ?>
                        <option value="<?= htmlspecialchars($te) ?>" <?= $filtroTipo === $te ? 'selected' : '' ?>><?= htmlspecialchars($te) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Usuario</label>
                <select name="usuario" class="form-select form-select-sm" style="width:auto">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= $filtroUsuario === $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Tarea ID</label>
                <input type="text" name="tarea" class="form-control form-control-sm" style="width:120px" value="<?= htmlspecialchars($filtroTarea) ?>" placeholder="task-001">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="<?= htmlspecialchars($basePath ?? '') ?>/historial.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
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
                ?>
                    <tr>
                        <td class="small"><?= htmlspecialchars($h['fecha_hora'] ?? '') ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($h['tipo_evento'] ?? '') ?></span></td>
                        <td><?= $u ? htmlspecialchars($u['nombre']) : ($h['usuario_id'] ?? '-') ?></td>
                        <td><?= $task ? htmlspecialchars($task['folio'] ?? $h['tarea_id']) : ($h['tarea_id'] ?? '-') ?></td>
                        <td class="small"><?= htmlspecialchars(mb_substr($h['descripcion'] ?? '', 0, 80)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($historial)): ?>
            <p class="text-muted p-4 mb-0 text-center">No hay registros con los filtros seleccionados.</p>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($historial)): ?>
<script>
$(function() { $('#tablaHistorial').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[0,'desc']] }); });
</script>
<?php endif; ?>
