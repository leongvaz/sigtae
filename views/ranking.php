<?php
$ranking = $ranking ?? [];
?>

<?php sigtae_page_header('Ranking de desempeño', 'Porcentaje de cumplimiento por colaborador (promedio ponderado de sus tareas evaluadas)'); ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-trophy me-1"></i> Top colaboradores</div>
            <div class="card-body p-0">
                <?php if (empty($ranking)): ?>
                    <?php sigtae_empty_state('No hay datos de desempeño aún.', 'bi-trophy'); ?>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaRanking">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Colaborador</th>
                                <th>% Desempeño</th>
                                <th class="text-center">Total</th>
                                <th class="text-center"><span data-bs-toggle="tooltip" title="Atendidas en tiempo">En tiempo</span></th>
                                <th class="text-center"><span data-bs-toggle="tooltip" title="Atendidas fuera de tiempo">Fuera</span></th>
                                <th class="text-center">Incumplidas</th>
                                <th class="text-center">Activas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $i => $r):
                                $pct = (int)($r['porcentaje_desempeno'] ?? 0);
                                $badge = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                                $medalla = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ''));
                            ?>
                                <tr>
                                    <td class="fw-semibold"><?= $medalla !== '' ? $medalla : ('#' . ($i + 1)) ?></td>
                                    <td><?= htmlspecialchars($r['nombre'] ?? '') ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px; min-width: 80px;">
                                                <div class="progress-bar <?= $badge ?>" style="width: <?= min(100, max(0, $pct)) ?>%"></div>
                                            </div>
                                            <span class="badge <?= $badge ?>"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= $r['total_tareas'] ?? 0 ?></td>
                                    <td class="text-center text-success"><?= $r['tareas_atendidas_tiempo'] ?? 0 ?></td>
                                    <td class="text-center text-warning"><?= $r['tareas_atendidas_fuera_tiempo'] ?? 0 ?></td>
                                    <td class="text-center text-danger"><?= $r['tareas_incumplidas'] ?? 0 ?></td>
                                    <td class="text-center text-muted"><?= $r['tareas_activas'] ?? 0 ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i> Cómo se calcula</div>
            <div class="card-body">
                <p class="small mb-2"><strong>Colaboradores con tareas:</strong> <?= count($ranking) ?></p>
                <hr>
                <p class="small text-muted mb-2">El desempeño se calcula como el promedio ponderado de las tareas evaluadas:</p>
                <ul class="small text-muted ps-3 mb-0">
                    <li><strong class="text-success">100%</strong> — presentada en tiempo</li>
                    <li><strong class="text-warning">50%</strong> — presentada fuera de tiempo</li>
                    <li><strong class="text-danger">0%</strong> — no presentada (incumplimiento)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($ranking)): ?>
<script>
$(function() {
    $('#tablaRanking').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[2,'desc']],
        paging: false,
        drawCallback: function() { if (window.sigtaeInitTooltips) window.sigtaeInitTooltips(); }
    });
});
</script>
<?php endif; ?>
