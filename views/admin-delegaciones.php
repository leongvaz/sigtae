<?php
$delegations = $delegations ?? [];
$userById = $userById ?? [];
?>
<?php sigtae_page_header('Delegaciones temporales', 'Encargos temporales con permisos equivalentes a un escalón superior'); ?>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($delegations)): ?>
            <?php sigtae_empty_state('No hay delegaciones registradas.', 'bi-person-gear'); ?>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Quién delega</th>
                        <th>Encargado temporal</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delegations as $d):
                        $delega = $userById[$d['delegado_id'] ?? ''] ?? null;
                        $encargado = $userById[$d['encargado_temporal_id'] ?? ''] ?? null;
                    ?>
                        <tr>
                            <td><?= $delega ? htmlspecialchars($delega['nombre']) : '-' ?></td>
                            <td><?= $encargado ? htmlspecialchars($encargado['nombre']) : '-' ?></td>
                            <td><?= htmlspecialchars($d['fecha_inicio'] ?? '') ?></td>
                            <td><?= htmlspecialchars($d['fecha_fin'] ?? '') ?></td>
                            <td><?= htmlspecialchars($d['motivo'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>
