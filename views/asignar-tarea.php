<?php
$assignable = $assignable ?? [];
$offices = $offices ?? [];
$prioridades = $prioridades ?? [];
$categorias = $categorias ?? [];
$modalidades = $modalidades ?? ['diaria', 'programada'];
$hoyMx = $hoyMx ?? date('Y-m-d');
$error = $error ?? '';
$success = $success ?? '';
$modalSel = $_POST['modalidad_asignacion'] ?? 'diaria';
if (!in_array($modalSel, $modalidades, true)) {
    $modalSel = 'diaria';
}
?>
<?php sigtae_page_header('Asignar tarea', 'Sólo puede asignar a colaboradores de su ámbito según permisos jerárquicos.'); ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (empty($assignable)): ?>
    <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>No tiene permisos para asignar tareas o no hay colaboradores asignables en su ámbito.</div>
<?php else: ?>
<div class="card">
    <div class="card-header card-header-accent"><i class="bi bi-plus-square me-1"></i> Nueva tarea</div>
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
                        <?php
                        $catLabels = ['operativa' => 'Operativa', 'administrativo' => 'Administrativo', 'aplicativo' => 'Aplicativo'];
                        foreach ($categorias as $c):
                            $lab = $catLabels[$c] ?? ucfirst(str_replace('_', ' ', $c));
                        ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['categoria'] ?? 'operativa') === $c ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Modalidad</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modalidad_asignacion" id="modDiaria" value="diaria" <?= $modalSel === 'diaria' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modDiaria">Tarea diaria (fecha límite: hoy, <?= htmlspecialchars($hoyMx) ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modalidad_asignacion" id="modProg" value="programada" <?= $modalSel === 'programada' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modProg">Programada (elija fecha límite futura)</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="wrapFechaProg">
                    <label class="form-label fw-semibold">Fecha límite *</label>
                    <input type="date" name="fecha_limite" id="inputFechaLimite" class="form-control" min="<?= htmlspecialchars($hoyMx) ?>" value="<?= htmlspecialchars($_POST['fecha_limite'] ?? $hoyMx) ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Asignar tarea</button>
                    <a href="<?= htmlspecialchars($basePath ?? '') ?>/dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
(function() {
    const diaria = document.getElementById('modDiaria');
    const prog = document.getElementById('modProg');
    const wrap = document.getElementById('wrapFechaProg');
    const inp = document.getElementById('inputFechaLimite');
    function sync() {
        const on = prog && prog.checked;
        if (wrap) wrap.style.display = on ? '' : 'none';
        if (inp) {
            inp.required = !!on;
            if (on) inp.setAttribute('name', 'fecha_limite');
            else inp.removeAttribute('name');
        }
    }
    if (diaria) diaria.addEventListener('change', sync);
    if (prog) prog.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>
