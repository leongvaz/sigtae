<?php
$zonaUser = $editUser ?? null;
$esZonaChecked = !empty($zonaUser['es_usuario_zona']);
$zonaSuffix = isset($editUser) ? 'Edit' : 'Create';
?>
<div class="col-12">
    <hr class="my-2">
    <div class="form-check">
        <input class="form-check-input js-zona-user-toggle" type="checkbox" name="es_usuario_zona" id="esZona<?= $zonaSuffix ?>" value="1" <?= $esZonaChecked ? 'checked' : '' ?>>
        <label class="form-check-label fw-semibold" for="esZona<?= $zonaSuffix ?>">Usuario de zona (solicitudes de calibración)</label>
    </div>
    <div class="form-text">Solo verá Solicitudes en Metrología. La oficina se asigna automáticamente a Zona.</div>
</div>
<div class="col-12 js-zona-user-panel" style="<?= $esZonaChecked ? '' : 'display:none;' ?>">
    <div class="row g-3 p-3 rounded border bg-light">
        <div class="col-md-8">
            <label class="form-label">Zona</label>
            <select name="zona_id" class="form-select">
                <option value="">— Seleccione —</option>
                <?php foreach ($metZonas as $z): ?>
                    <?php if (isset($z['activa']) && $z['activa'] === false) continue; ?>
                    <option value="<?= htmlspecialchars($z['id'] ?? '') ?>"
                        <?= ($zonaUser['zona_id'] ?? '') === ($z['id'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z['nombre'] ?? $z['id'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
