<?php
$offices = $offices ?? [];
$departments = $departments ?? [];
$deptById = [];
foreach ($departments as $d) {
    $deptById[$d['id']] = $d;
}
$error = $error ?? '';
$success = $success ?? '';
$editOffice = $editOffice ?? null;
?>
<?php sigtae_page_header('Oficinas', 'Solo super administradores'); ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (!$editOffice): ?>
<div class="card mb-4">
    <div class="card-header">Nueva oficina</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="create">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Departamento *</label>
                <select name="departamento_id" class="form-select" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['id']) ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card mb-4">
    <div class="card-header">Editar oficina</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editOffice['id'] ?? '') ?>">
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($editOffice['nombre'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['id']) ?>" <?= ($editOffice['departamento_id'] ?? '') === $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?= htmlspecialchars($basePath ?? '') ?>/admin-oficinas.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Departamento</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offices as $o): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($o['id'] ?? '') ?></code></td>
                        <td><?= htmlspecialchars($o['nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars($deptById[$o['departamento_id'] ?? '']['nombre'] ?? ($o['departamento_id'] ?? '')) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/admin-oficinas.php?edit=<?= urlencode($o['id'] ?? '') ?>">Editar</a>
                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar oficina?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($o['id'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
