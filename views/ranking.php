<?php
$ranking = $ranking ?? [];
?>
<div class="mb-4">
    <h1 class="h3 fw-bold">Ranking de desempeño</h1>
    <p class="text-muted mb-0">Porcentaje de cumplimiento por colaborador (promedio de tareas)</p>
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Top colaboradores</div>
            <div class="card-body p-0">
                <?php if (empty($ranking)): ?>
                    <p class="text-muted p-4 mb-0">No hay datos de desempeño aún.</p>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0" id="tablaRanking">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Colaborador</th>
                                <th>% Desempeño</th>
                                <th>Total tareas</th>
                                <th>Atendidas en tiempo</th>
                                <th>Atendidas fuera</th>
                                <th>Incumplidas</th>
                                <th>Activas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $i => $r): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($r['nombre'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($r['porcentaje_desempeno'] ?? 0) >= 80 ? 'success' : (($r['porcentaje_desempeno'] ?? 0) >= 50 ? 'warning' : 'danger') ?>">
                                            <?= $r['porcentaje_desempeno'] ?? 0 ?>%
                                        </span>
                                    </td>
                                    <td><?= $r['total_tareas'] ?? 0 ?></td>
                                    <td><?= $r['tareas_atendidas_tiempo'] ?? 0 ?></td>
                                    <td><?= $r['tareas_atendidas_fuera_tiempo'] ?? 0 ?></td>
                                    <td><?= $r['tareas_incumplidas'] ?? 0 ?></td>
                                    <td><?= $r['tareas_activas'] ?? 0 ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Resumen</div>
            <div class="card-body">
                <p class="mb-1"><strong>Colaboradores con tareas:</strong> <?= count($ranking) ?></p>
                <p class="mb-0">El desempeño se calcula como promedio: 100% en tiempo, 50% fuera de tiempo, 0% no atendida.</p>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($ranking)): ?>
<script>
$(function() { $('#tablaRanking').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[2,'desc']], paging: false }); });
</script>
<?php endif; ?>
