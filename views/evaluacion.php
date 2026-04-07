<?php
$pendientesEvaluar = $pendientesEvaluar ?? [];
$userRepo = $userRepo ?? null;
?>
<div class="mb-4">
    <h1 class="h3 fw-bold">Evaluación de tareas</h1>
    <p class="text-muted mb-0">Tareas con evidencia cargada pendientes de su evaluación</p>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($pendientesEvaluar)): ?>
            <p class="text-muted p-4 mb-0">No hay tareas pendientes de evaluar por usted.</p>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Título</th>
                        <th>Responsable</th>
                        <th>Límite</th>
                        <th>Evidencias</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientesEvaluar as $t):
                        $resp = $userRepo ? $userRepo->find($t['responsable_id'] ?? '') : null;
                    ?>
                        <tr>
                            <td><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 50)) ?></td>
                            <td><?= $resp ? htmlspecialchars($resp['nombre']) : '-' ?></td>
                            <td><?= htmlspecialchars($t['fecha_limite'] ?? '-') ?></td>
                            <td><?= count($t['evidencias'] ?? []) ?></td>
                            <td><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>#evaluar" class="btn btn-sm btn-primary">Evaluar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
