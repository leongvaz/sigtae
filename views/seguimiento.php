<?php
$withState = $withState ?? [];
$offices = $offices ?? [];
$users = $users ?? [];
$filtroEstado = $filtroEstado ?? '';
$filtroOficina = $filtroOficina ?? '';
$filtroResponsable = $filtroResponsable ?? '';
$roid = $reportOficinaId ?? 'of-metro';
$hoy = date('Y-m-d');
?>

<?php sigtae_page_header('Seguimiento de tareas', 'Vista global de todas las tareas con filtros y exportes'); ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="asignada"       <?= $filtroEstado === 'asignada' ? 'selected' : '' ?>>Asignada</option>
                    <option value="en_proceso"     <?= $filtroEstado === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                    <option value="vencida"        <?= $filtroEstado === 'vencida' ? 'selected' : '' ?>>Vencida</option>
                    <option value="incumplimiento" <?= $filtroEstado === 'incumplimiento' ? 'selected' : '' ?>>Incumplimiento</option>
                    <option value="atendida"       <?= $filtroEstado === 'atendida' ? 'selected' : '' ?>>Atendida</option>
                    <option value="cancelada"      <?= $filtroEstado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Oficina</label>
                <select name="oficina" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?= htmlspecialchars($o['id']) ?>" <?= $filtroOficina === $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Responsable</label>
                <select name="responsable" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= $filtroResponsable === $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath ?? '') ?>/seguimiento.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
            <span class="small text-muted me-1"><i class="bi bi-file-earmark-arrow-down"></i> Exportar:</span>
            <a href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?oficina=<?= urlencode($roid) ?>" class="btn btn-sm btn-outline-primary">Abrir reportes</a>
            <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?export=1&amp;format=csv&amp;oficina=<?= urlencode($roid) ?>&amp;periodo=diario&amp;fecha=<?= htmlspecialchars($hoy) ?>"><i class="bi bi-filetype-csv"></i> Día</a>
            <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?export=1&amp;format=csv&amp;oficina=<?= urlencode($roid) ?>&amp;periodo=semanal&amp;fecha=<?= htmlspecialchars($hoy) ?>"><i class="bi bi-filetype-csv"></i> Semana</a>
            <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?export=1&amp;format=csv&amp;oficina=<?= urlencode($roid) ?>&amp;periodo=mensual&amp;fecha=<?= htmlspecialchars($hoy) ?>"><i class="bi bi-filetype-csv"></i> Mes</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($basePath ?? '') ?>/reportes.php?export=1&amp;format=pdf&amp;oficina=<?= urlencode($roid) ?>&amp;periodo=semanal&amp;fecha=<?= htmlspecialchars($hoy) ?>"><i class="bi bi-filetype-pdf"></i> PDF</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($withState)): ?>
            <?php sigtae_empty_state('No hay tareas con los filtros seleccionados.', 'bi-search'); ?>
        <?php else: ?>
        <div class="table-responsive">
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
                            <td><a class="fw-semibold" href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 55)) ?></td>
                            <td><?= $resp ? htmlspecialchars($resp['nombre']) : '—' ?></td>
                            <td class="small"><?= $of ? htmlspecialchars($of['nombre']) : '—' ?></td>
                            <td><?= sigtae_status_badge((string)($t['estado'] ?? '')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($t['fecha_limite'] ?? '—') ?></td>
                            <td class="text-end"><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($withState)): ?>
<script>
$(function() {
    $('#tablaSeguimiento').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[5,'asc']]
    });
});
</script>
<?php endif; ?>
