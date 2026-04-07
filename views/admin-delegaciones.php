<?php
$delegations = $delegations ?? [];
$userById = $userById ?? [];
?>
<div class="mb-4">
    <h1 class="h3 fw-bold">Delegaciones temporales</h1>
    <p class="text-muted mb-0">Encargos temporales con permisos equivalentes a un escalón superior</p>
</div>
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($delegations)): ?>
            <p class="text-muted p-4 mb-0">No hay delegaciones registradas.</p>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</div>
