<?php
$withState = $withState ?? [];
$userRepo = $userRepo ?? null;
function estadoBadge($estado) {
    $map = ['asignada' => 'primary', 'en_proceso' => 'warning', 'incumplimiento' => 'danger', 'vencida' => 'warning', 'atendida' => 'success'];
    return $map[$estado] ?? 'secondary';
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold">Mis tareas</h1>
        <p class="text-muted mb-0">Tareas asignadas a usted</p>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaMisTareas">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Título</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Límite</th>
                        <th>Días</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withState as $t): ?>
                        <tr>
                            <td><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 50)) ?></td>
                            <td><span class="badge bg-<?= $t['prioridad'] === 'alta' ? 'danger' : ($t['prioridad'] === 'media' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($t['prioridad'] ?? '') ?></span></td>
                            <td><span class="badge bg-<?= estadoBadge($t['estado'] ?? '') ?>"><?= htmlspecialchars($t['estado'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($t['fecha_limite'] ?? '-') ?></td>
                            <td>
                                <?php
                                $d = $t['dias_restantes'] ?? null;
                                if ($d !== null) {
                                    if ($d < 0) echo '<span class="text-danger">' . $d . '</span>';
                                    elseif ($d <= 3) echo '<span class="text-warning">' . $d . '</span>';
                                    else echo $d;
                                } else echo '-';
                                ?>
                            </td>
                            <td><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>" class="btn btn-sm btn-outline-primary">Ver / Evidencia</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($withState)): ?>
            <p class="text-muted p-4 mb-0 text-center">No tiene tareas asignadas.</p>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($withState)): ?>
<script>
$(function() { $('#tablaMisTareas').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[4,'asc']] }); });
</script>
<?php endif; ?>
