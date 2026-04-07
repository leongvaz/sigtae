<?php
$users = $users ?? [];
$offices = $offices ?? [];
$ofById = [];
foreach ($offices as $o) { $ofById[$o['id']] = $o; }
?>
<div class="mb-4">
    <h1 class="h3 fw-bold">Usuarios</h1>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
            <thead class="table-light">
                <tr>
                    <th>RPE</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Oficina</th>
                    <th>Nivel</th>
                    <th>Puede asignar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $of = $ofById[$u['oficina_id'] ?? ''] ?? null;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($u['rpe'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['cargo'] ?? '') ?></td>
                        <td><?= $of ? htmlspecialchars($of['nombre']) : '-' ?></td>
                        <td><?= $u['nivel_jerarquico'] ?? '-' ?></td>
                        <td><?= !empty($u['puede_asignar']) ? 'Sí' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
$(function() { $('#tablaUsuarios').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' } }); });
</script>
