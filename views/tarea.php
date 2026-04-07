<?php
$task = $task ?? [];
$asignador = $asignador ?? null;
$responsable = $responsable ?? null;
$puedeEvaluar = $puedeEvaluar ?? false;
$esResponsable = $esResponsable ?? false;
$esAsignador = $esAsignador ?? false;
$error = $error ?? '';
$success = $success ?? '';
$dictamenOpts = $dictamenOpts ?? [];
$historial = $historial ?? [];
function estadoBadge($estado) {
    $map = ['asignada' => 'primary', 'en_proceso' => 'warning', 'incumplimiento' => 'danger', 'vencida' => 'warning', 'atendida' => 'success'];
    return $map[$estado] ?? 'secondary';
}
?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 fw-bold"><?= htmlspecialchars($task['folio'] ?? '') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($task['titulo'] ?? '') ?></p>
        <span class="badge bg-<?= estadoBadge($task['estado'] ?? '') ?> mt-2"><?= htmlspecialchars($task['estado'] ?? '') ?></span>
        <span class="badge bg-secondary"><?= htmlspecialchars($task['prioridad'] ?? '') ?></span>
    </div>
    <a href="<?= htmlspecialchars($basePath ?? '') ?>/mis-tareas.php" class="btn btn-outline-secondary">Volver</a>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">Detalle</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Asignador</dt>
                    <dd class="col-sm-9"><?= $asignador ? htmlspecialchars($asignador['nombre']) : '-' ?></dd>
                    <dt class="col-sm-3">Responsable</dt>
                    <dd class="col-sm-9"><?= $responsable ? htmlspecialchars($responsable['nombre']) : '-' ?></dd>
                    <dt class="col-sm-3">Fecha límite</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($task['fecha_limite'] ?? '-') ?></dd>
                    <dt class="col-sm-3">Días restantes</dt>
                    <dd class="col-sm-9"><?= $task['dias_restantes'] !== null ? $task['dias_restantes'] : '-' ?></dd>
                    <dt class="col-sm-3">Descripción</dt>
                    <dd class="col-sm-9"><?= nl2br(htmlspecialchars($task['descripcion'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>
        <?php if ($esResponsable): ?>
        <div class="card mb-4">
            <div class="card-header">Comentarios del responsable</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="comentario_responsable">
                    <textarea name="comentarios_responsable" class="form-control mb-2" rows="3"><?= htmlspecialchars($task['comentarios_responsable'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar comentario</button>
                </form>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Agregar evidencia</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="evidencia">
                    <div class="mb-2">
                        <label class="form-label small">Nombre / descripción</label>
                        <input type="text" name="ev_nombre" class="form-control" placeholder="Ej. Reporte de revisión" value="">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Comentario</label>
                        <input type="text" name="ev_comentario" class="form-control" placeholder="Opcional">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">Registrar evidencia</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Cada envío se versiona (v1, v2…). En producción se adjuntará archivo.</p>
            </div>
        </div>
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-header">Evidencias (<?= count($task['evidencias'] ?? []) ?>)</div>
            <div class="card-body p-0">
                <?php if (empty($task['evidencias'])): ?>
                    <p class="text-muted p-3 mb-0">Sin evidencias aún.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($task['evidencias'] as $ev): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>v<?= $ev['version'] ?? 1 ?> — <?= htmlspecialchars($ev['nombre_archivo'] ?? '') ?></strong>
                                    <span class="text-muted small"><?= htmlspecialchars($ev['fecha_subida'] ?? '') ?></span>
                                </div>
                                <?php if (!empty($ev['comentario'])): ?><div class="small text-muted"><?= htmlspecialchars($ev['comentario']) ?></div><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($puedeEvaluar): ?>
        <div class="card mb-4">
            <div class="card-header">Evaluación</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="evaluar">
                    <div class="mb-2">
                        <label class="form-label">Dictamen</label>
                        <select name="dictamen" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($dictamenOpts as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>"><?= ucfirst(str_replace('_', ' ', $d)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Comentarios del evaluador</label>
                        <textarea name="comentarios_evaluador" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar evaluación</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Resumen</div>
            <div class="card-body">
                <p class="mb-1"><strong>Estado:</strong> <?= htmlspecialchars($task['estado'] ?? '') ?></p>
                <p class="mb-1"><strong>% cumplimiento:</strong> <?= $task['porcentaje_cumplimiento'] !== null ? $task['porcentaje_cumplimiento'] . '%' : '-' ?></p>
                <p class="mb-1"><strong>Dictamen:</strong> <?= $task['dictamen'] ? ucfirst(str_replace('_', ' ', $task['dictamen'])) : '-' ?></p>
                <?php if (!empty($task['comentarios_evaluador'])): ?>
                    <p class="mb-0 small text-muted"><?= htmlspecialchars($task['comentarios_evaluador']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Historial</div>
            <div class="card-body p-0" style="max-height: 320px; overflow-y: auto;">
                <?php if (empty($historial)): ?>
                    <p class="text-muted p-3 mb-0 small">Sin eventos.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($historial as $h): ?>
                            <li class="list-group-item py-2">
                                <div class="small fw-semibold"><?= htmlspecialchars($h['tipo_evento'] ?? '') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($h['descripcion'] ?? '') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($h['fecha_hora'] ?? '') ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
