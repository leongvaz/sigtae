<?php
$pendientesEvaluar = $pendientesEvaluar ?? [];
$userRepo = $userRepo ?? null;
?>

<?php sigtae_page_header('Evaluación de tareas', 'Tareas con evidencia cargada pendientes de su evaluación'); ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($pendientesEvaluar)): ?>
            <?php sigtae_empty_state('No hay tareas pendientes de evaluar por usted.', 'bi-check2-all'); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Título</th>
                            <th>Responsable</th>
                            <th>Límite</th>
                            <th class="text-center">Evidencias</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientesEvaluar as $t):
                            $resp = $userRepo ? $userRepo->find($t['responsable_id'] ?? '') : null;
                        ?>
                            <tr>
                                <td><a class="fw-semibold" href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a></td>
                                <td><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 55)) ?></td>
                                <td><?= $resp ? htmlspecialchars($resp['nombre']) : '—' ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($t['fecha_limite'] ?? '—') ?></td>
                                <td class="text-center"><span class="badge bg-info"><i class="bi bi-paperclip"></i> <?= count($t['evidencias'] ?? []) ?></span></td>
                                <td class="text-end"><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>#evaluar" class="btn btn-sm btn-primary"><i class="bi bi-check2-square"></i> Evaluar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
