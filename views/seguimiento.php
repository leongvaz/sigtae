<?php
$withState = $withState ?? [];
$offices = $offices ?? [];
$users = $users ?? [];
$filtroEstado = $filtroEstado ?? '';
$filtroOficina = $filtroOficina ?? '';
$filtroResponsable = $filtroResponsable ?? '';
function estadoBadge($e) { $m = ['asignada'=>'primary','en_proceso'=>'warning','incumplimiento'=>'danger','vencida'=>'warning','atendida'=>'success']; return $m[$e] ?? 'secondary'; }
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold">Seguimiento de tareas</h1>
        <p class="text-muted mb-0">Todas las tareas con filtros</p>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Estado</label>
                <select name="estado" class="form-select form-select-sm" style="width:auto">
                    <option value="">Todos</option>
                    <option value="asignada" <?= $filtroEstado === 'asignada' ? 'selected' : '' ?>>Asignada</option>
                    <option value="en_proceso" <?= $filtroEstado === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                    <option value="vencida" <?= $filtroEstado === 'vencida' ? 'selected' : '' ?>>Vencida</option>
                    <option value="incumplimiento" <?= $filtroEstado === 'incumplimiento' ? 'selected' : '' ?>>Incumplimiento</option>
                    <option value="atendida" <?= $filtroEstado === 'atendida' ? 'selected' : '' ?>>Atendida</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Oficina</label>
                <select name="oficina" class="form-select form-select-sm" style="width:auto">
                    <option value="">Todas</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?= htmlspecialchars($o['id']) ?>" <?= $filtroOficina === $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Responsable</label>
                <select name="responsable" class="form-select form-select-sm" style="width:auto">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= $filtroResponsable === $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="<?= htmlspecialchars($basePath ?? '') ?>/seguimiento.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0" id="tablaSeguimiento">
            <thead class="table-light">
                <tr>
                    <th>Folio</th>
                    <th>Título</th>
                    <th>Responsable</th>
                    <th>Oficina</th>
                    <th>Estado</th>
                    <th>Límite</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $userById = [];
                foreach ($users as $u) { $userById[$u['id']] = $u; }
                $ofById = [];
                foreach ($offices as $o) { $ofById[$o['id']] = $o; }
                foreach ($withState as $t):
                    $resp = $userById[$t['responsable_id'] ?? ''] ?? null;
                    $of = $ofById[$t['oficina_id'] ?? ''] ?? null;
                ?>
                    <tr>
                        <td><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a></td>
                        <td><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 45)) ?></td>
                        <td><?= $resp ? htmlspecialchars($resp['nombre']) : '-' ?></td>
                        <td><?= $of ? htmlspecialchars($of['nombre']) : '-' ?></td>
                        <td><span class="badge bg-<?= estadoBadge($t['estado'] ?? '') ?>"><?= htmlspecialchars($t['estado'] ?? '') ?></span></td>
                        <td><?= htmlspecialchars($t['fecha_limite'] ?? '-') ?></td>
                        <td><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($withState)): ?>
            <p class="text-muted p-4 mb-0 text-center">No hay tareas con los filtros seleccionados.</p>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($withState)): ?>
<script>
$(function() { $('#tablaSeguimiento').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[5,'asc']] }); });
</script>
<?php endif; ?>
