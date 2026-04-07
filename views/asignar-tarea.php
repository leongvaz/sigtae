<?php
$assignable = $assignable ?? [];
$offices = $offices ?? [];
$prioridades = $prioridades ?? [];
$categorias = $categorias ?? [];
$error = $error ?? '';
$success = $success ?? '';
?>
<div class="mb-4">
    <h1 class="h3 fw-bold">Asignar tarea</h1>
    <p class="text-muted mb-0">Solo puede asignar a colaboradores de su ámbito según permisos jerárquicos.</p>
</div>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (empty($assignable)): ?>
    <div class="alert alert-info">No tiene permisos para asignar tareas o no hay colaboradores asignables en su ámbito.</div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Responsable *</label>
                    <select name="responsable_id" class="form-select" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($assignable as $u): ?>
                            <option value="<?= htmlspecialchars($u['id']) ?>" <?= ($_POST['responsable_id'] ?? '') === $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['cargo'] ?? '') ?> — <?= htmlspecialchars($u['rpe'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Título *</label>
                    <input type="text" name="titulo" class="form-control" required maxlength="255" value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" placeholder="Ej. Revisión de equipos de medición">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Detalle de la tarea"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Prioridad</label>
                    <select name="prioridad" class="form-select">
                        <?php foreach ($prioridades as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= ($_POST['prioridad'] ?? 'media') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Categoría</label>
                    <select name="categoria" class="form-select">
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['categoria'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $c)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fecha límite *</label>
                    <input type="date" name="fecha_limite" class="form-control" required value="<?= htmlspecialchars($_POST['fecha_limite'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Asignar tarea</button>
                    <a href="<?= htmlspecialchars($basePath ?? '') ?>/dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
