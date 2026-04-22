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
$puedeCancelar = $puedeCancelar ?? false;
$candidatosReasignar = $candidatosReasignar ?? [];

$dictamenLabels = [
    'satisfactoria' => 'Satisfactoria (en tiempo)',
    'satisfactoria_fuera_tiempo' => 'Satisfactoria (fuera de tiempo)',
    'insatisfactoria' => 'Insatisfactoria',
    'no_presentada' => 'No presentada',
    'requiere_correccion' => 'Requiere corrección',
];

// Fecha en que se presentó la tarea (primera evidencia o fecha_entrega ya registrada)
$fechaPresentacion = $task['fecha_entrega'] ?? null;
if (!$fechaPresentacion && !empty($task['evidencias'])) {
    $fechaPresentacion = substr((string)($task['evidencias'][0]['fecha_subida'] ?? ''), 0, 10);
}
?>

<!-- ================= Header ================= -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h4 fw-bold mb-0" style="color: var(--sigtae-navy)"><?= htmlspecialchars($task['folio'] ?? '') ?></h1>
                    <?= sigtae_status_badge((string)($task['estado'] ?? '')) ?>
                    <?= sigtae_prioridad_badge((string)($task['prioridad'] ?? '')) ?>
                </div>
                <p class="text-muted mb-0"><?= htmlspecialchars($task['titulo'] ?? '') ?></p>
            </div>
            <a href="<?= htmlspecialchars($basePath ?? '') ?>/mis-tareas.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
        <?= sigtae_timeline($task) ?>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="row g-3">
    <!-- ========== Columna principal ========== -->
    <div class="col-lg-8">
        <!-- Detalle -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i> Detalle</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Asignador</dt>
                    <dd class="col-sm-8"><?= sigtae_user_chip($asignador) ?></dd>
                    <dt class="col-sm-4">Responsable</dt>
                    <dd class="col-sm-8"><?= sigtae_user_chip($responsable) ?></dd>
                    <dt class="col-sm-4">Modalidad</dt>
                    <dd class="col-sm-8"><?php
                        $mod = $task['modalidad_asignacion'] ?? '';
                        echo $mod === 'diaria' ? 'Diaria (límite el día de asignación)' : ($mod === 'programada' ? 'Programada' : ($mod !== '' ? htmlspecialchars($mod) : '—'));
                    ?></dd>
                    <dt class="col-sm-4">Fecha límite</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($task['fecha_limite'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Días restantes</dt>
                    <dd class="col-sm-8"><?= sigtae_dias_pill(isset($task['dias_restantes']) ? (int)$task['dias_restantes'] : null, (string)($task['estado'] ?? '')) ?></dd>
                    <?php if ($fechaPresentacion): ?>
                    <dt class="col-sm-4">Fecha de presentación</dt>
                    <dd class="col-sm-8"><span class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i><?= htmlspecialchars((string)$fechaPresentacion) ?></span></dd>
                    <?php endif; ?>
                    <dt class="col-sm-4">Descripción</dt>
                    <dd class="col-sm-8"><?= nl2br(htmlspecialchars($task['descripcion'] ?? '—')) ?></dd>
                    <?php if (!empty($task['cancelada'])): ?>
                    <dt class="col-sm-4 text-danger">Cancelación</dt>
                    <dd class="col-sm-8 text-danger"><?= nl2br(htmlspecialchars($task['motivo_cancelacion'] ?? '')) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Presentar evidencia -->
        <?php if ($esResponsable && empty($task['cancelada'])): ?>
        <div class="card mb-3">
            <div class="card-header card-header-accent"><i class="bi bi-cloud-upload me-1"></i> Presentar evidencia</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="evidencia">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre / descripción</label>
                            <input type="text" name="ev_nombre" class="form-control form-control-sm" placeholder="Ej. Reporte de revisión" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Comentario</label>
                            <input type="text" name="ev_comentario" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adjuntos (foto, video o documento — puede seleccionar varios)</label>
                            <input type="file" name="ev_archivo[]" class="form-control form-control-sm" multiple
                                   accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.zip,.rar">
                            <div class="form-text">Formatos comunes: imágenes, MP4, PDF, Word, Excel, PowerPoint. Puede seleccionar varios archivos al mismo tiempo.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-cloud-upload me-1"></i> Presentar evidencia</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Evidencias -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-paperclip me-1"></i> Evidencias (<?= count($task['evidencias'] ?? []) ?>)</div>
            <div class="card-body p-0">
                <?php if (empty($task['evidencias'])): ?>
                    <?php sigtae_empty_state('Sin evidencias aún.', 'bi-inbox'); ?>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($task['evidencias'] as $ev): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <strong><i class="bi bi-file-earmark me-1"></i> v<?= $ev['version'] ?? 1 ?> — <?= htmlspecialchars($ev['nombre_archivo'] ?? '') ?></strong>
                                        <?php if (!empty($ev['ruta'])): ?>
                                            <div class="small mt-1">
                                                <a class="link-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/evidencia.php?task_id=<?= urlencode($task['id'] ?? '') ?>&ev_id=<?= urlencode($ev['id'] ?? '') ?>" target="_blank" rel="noopener">
                                                    <i class="bi bi-box-arrow-up-right"></i> Ver / descargar
                                                </a>
                                                <?php if (!empty($ev['tipo_mime'])): ?>
                                                    <span class="text-muted">— <?= htmlspecialchars($ev['tipo_mime']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ev['comentario'])): ?>
                                            <div class="small text-muted mt-1"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($ev['comentario']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted small text-nowrap"><i class="bi bi-clock"></i> <?= htmlspecialchars($ev['fecha_subida'] ?? '') ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluación -->
        <?php
        $evCount = count($task['evidencias'] ?? []);
        $evalAbierta = ($task['evaluacion'] ?? null) === null;
        $opcionesEval = ['satisfactoria', 'insatisfactoria'];
        $puedeMostrarEval = $puedeEvaluar && $evalAbierta && $evCount > 0 && empty($task['cancelada']);
        ?>
        <?php if ($puedeMostrarEval): ?>
        <div class="card mb-3">
            <div class="card-header card-header-accent"><i class="bi bi-check2-square me-1"></i> Evaluación</div>
            <div class="card-body">
                <?php if (!empty($task['tuvo_insatisfactoria']) || !empty($task['pendiente_mejora'])): ?>
                    <div class="alert alert-warning small py-2 mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Hubo una evaluación <strong>insatisfactoria</strong>. Si ahora elige <strong>Satisfactoria</strong>, el sistema registrará el cierre como <strong>fuera de tiempo</strong> (50%).
                    </div>
                <?php endif; ?>
                <p class="small text-muted mb-3">Si marca <strong>Satisfactoria</strong>, el sistema determina automáticamente si fue en tiempo o fuera de tiempo según la fecha límite y la primera evidencia.</p>
                <form method="post">
                    <input type="hidden" name="action" value="evaluar">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Resultado</label>
                            <select name="dictamen" class="form-select form-select-sm" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($opcionesEval as $d): ?>
                                    <option value="<?= htmlspecialchars($d) ?>"><?= $d === 'satisfactoria' ? 'Satisfactoria' : 'Insatisfactoria' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Comentarios del evaluador</label>
                            <input type="text" name="comentarios_evaluador" class="form-control form-control-sm" placeholder="Retroalimentación para el responsable">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Registrar evaluación</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php elseif ($puedeEvaluar && $evalAbierta && $evCount === 0 && empty($task['cancelada'])): ?>
        <div class="alert alert-secondary small py-2"><i class="bi bi-info-circle me-1"></i> La evaluación estará disponible cuando el responsable cargue al menos una evidencia.</div>
        <?php endif; ?>

        <!-- Gestión (reasignar / cancelar) -->
        <?php if (($puedeCancelar || count($candidatosReasignar) > 0) && empty($task['cancelada'])): ?>
        <div class="card mb-3 border-warning">
            <div class="card-header"><i class="bi bi-gear me-1"></i> Gestión de la tarea</div>
            <div class="card-body">
                <?php if (count($candidatosReasignar) > 0): ?>
                <form method="post" class="<?= $puedeCancelar ? 'mb-3 pb-3 border-bottom' : '' ?>">
                    <input type="hidden" name="action" value="reasignar_tarea">
                    <label class="form-label small fw-semibold">Reasignar a</label>
                    <div class="input-group input-group-sm">
                        <select name="nuevo_responsable_id" class="form-select" required>
                            <option value="">Seleccione colaborador...</option>
                            <?php foreach ($candidatosReasignar as $c): ?>
                                <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['rpe'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-left-right"></i> Reasignar</button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if ($puedeCancelar): ?>
                <form method="post" onsubmit="return confirm('¿Cancelar esta tarea? Quedará registrado el motivo.');">
                    <input type="hidden" name="action" value="cancelar_tarea">
                    <label class="form-label small fw-semibold text-danger">Cancelar tarea (motivo obligatorio)</label>
                    <textarea name="motivo_cancelacion" class="form-control form-control-sm mb-2" rows="2" required placeholder="Motivo de la cancelación"></textarea>
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i> Cancelar tarea</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========== Columna lateral ========== -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard-data me-1"></i> Resumen</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">Estado</dt>
                    <dd class="col-7"><?= sigtae_status_badge((string)($task['estado'] ?? '')) ?></dd>
                    <dt class="col-5">% cumplimiento</dt>
                    <dd class="col-7"><?= $task['porcentaje_cumplimiento'] !== null ? (int)$task['porcentaje_cumplimiento'] . '%' : '—' ?></dd>
                    <dt class="col-5">Dictamen</dt>
                    <dd class="col-7"><?php
                        $dm = $task['dictamen'] ?? '';
                        if ($dm !== '') {
                            echo htmlspecialchars($dictamenLabels[$dm] ?? ucfirst(str_replace('_', ' ', $dm)));
                        } elseif (!empty($task['pendiente_mejora'])) {
                            echo '<span class="text-warning">Pendiente de nuevo intento</span>';
                        } else {
                            echo '—';
                        }
                    ?></dd>
                    <?php if (($task['evaluacion'] ?? null) !== null): ?>
                        <dt class="col-5">Puntuación</dt>
                        <dd class="col-7"><?= htmlspecialchars((string)$task['evaluacion']) ?>%</dd>
                    <?php endif; ?>
                </dl>
                <?php if (!empty($task['comentarios_evaluador'])): ?>
                    <hr class="my-2">
                    <div class="small text-muted"><i class="bi bi-chat-quote me-1"></i><?= htmlspecialchars($task['comentarios_evaluador']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-1"></i> Historial</div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($historial)): ?>
                    <?php sigtae_empty_state('Sin eventos.', 'bi-clock-history'); ?>
                <?php else: ?>
                    <?php
                    $iconoEvento = [
                        'tarea_creada'            => 'bi-plus-circle text-primary',
                        'tarea_editada'           => 'bi-pencil text-info',
                        'cambio_fecha_limite'     => 'bi-calendar-event text-warning',
                        'cambio_responsable'      => 'bi-person-fill-gear text-info',
                        'cambio_estado'           => 'bi-arrow-repeat text-secondary',
                        'evidencia_subida'        => 'bi-cloud-upload text-success',
                        'evidencia_eliminada'     => 'bi-trash text-danger',
                        'comentario_agregado'     => 'bi-chat-left-text text-muted',
                        'evaluacion_registrada'   => 'bi-check2-square text-success',
                        'evaluacion_insatisfactoria' => 'bi-x-circle text-danger',
                        'delegacion_aplicada'     => 'bi-person-gear text-info',
                        'tarea_cancelada'         => 'bi-x-octagon text-danger',
                        'tarea_reasignada'        => 'bi-arrow-left-right text-warning',
                    ];
                    ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($historial as $h): ?>
                            <?php $tipo = (string)($h['tipo_evento'] ?? ''); $icoCls = $iconoEvento[$tipo] ?? 'bi-circle text-muted'; ?>
                            <li class="list-group-item py-2">
                                <div class="small d-flex align-items-start gap-2">
                                    <i class="bi <?= $icoCls ?> mt-1"></i>
                                    <div class="flex-grow-1" style="min-width: 0">
                                        <div class="fw-semibold"><?= htmlspecialchars(str_replace('_', ' ', $tipo)) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($h['descripcion'] ?? '') ?></div>
                                        <div class="text-muted" style="font-size: .72rem"><?= htmlspecialchars($h['fecha_hora'] ?? '') ?></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
