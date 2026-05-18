<?php
$users = $users ?? [];
$offices = $offices ?? [];
$departments = $departments ?? [];
$isSuper = $isSuper ?? false;
$error = $error ?? '';
$success = $success ?? '';
$editUser = $editUser ?? null;
$usersForSupervisor = $usersForSupervisor ?? [];
$ofById = [];
foreach ($offices as $o) {
    $ofById[$o['id']] = $o;
}
$deptById = [];
foreach ($departments as $d) {
    $deptById[$d['id']] = $d;
}
$showLocalPasswordFields = $showLocalPasswordFields ?? true;
$adLoginEnabled = $adLoginEnabled ?? false;
$metZonas = $metZonasForView ?? [];
$metAreas = $metAreasForView ?? [];
?>
<?php sigtae_page_header('Usuarios', $isSuper ? 'Administración completa (super admin)' : 'Solo supervisores de su oficina'); ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (!$editUser): ?>
<div class="card mb-4">
    <div class="card-header">Nuevo usuario</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="create">
            <div class="col-md-3">
                <label class="form-label">RPE *</label>
                <div class="input-group">
                    <input type="text" name="rpe" class="form-control" required maxlength="8" pattern="[A-Za-z0-9]{1,8}" id="rpeInputCreate" style="text-transform: uppercase;">
                    <button class="btn btn-outline-secondary" type="button" id="btnLookupRpeCreate" onclick="return window.sigtaeLookupRpeCreate ? window.sigtaeLookupRpeCreate() : false;">
                        <span class="d-inline">Buscar RPE</span>
                    </button>
                </div>
                <div class="form-text" id="rpeLookupMsgCreate"></div>
            </div>
            <div class="col-md-5">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required id="nombreInputCreate">
            </div>
            <div class="col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" placeholder="correo@cfe.mx" id="emailInputCreate">
            </div>
            <?php if (!empty($showLocalPasswordFields)): ?>
            <div class="col-md-4">
                <label class="form-label">Contraseña inicial (solo acceso local)</label>
                <input type="password" name="password" class="form-control" placeholder="Vacío = Sigtae2026!" autocomplete="new-password">
                <div class="form-text">Solo aplica si el login local está habilitado (sin DA o modo respaldo).</div>
            </div>
            <?php endif; ?>
            <?php if ($isSuper): ?>
            <div class="col-md-2">
                <label class="form-label">Nivel</label>
                <select name="nivel_jerarquico" class="form-select">
                    <option value="1">1 Jefe Depto.</option>
                    <option value="2">2 Jefe Oficina</option>
                    <option value="3" selected>3 Supervisor</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cargo</label>
                <input type="text" name="cargo" class="form-control" placeholder="Cargo" id="cargoInputCreate">
            </div>
            <div class="col-md-3">
                <label class="form-label">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['id']) ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Oficina</label>
                <select name="oficina_id" class="form-select">
                    <option value="">— Sin oficina —</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?= htmlspecialchars($o['id']) ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Supervisor inmediato (jerárquico)</label>
                <select name="supervisor_id" class="form-select" aria-describedby="helpSup">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($usersForSupervisor as $su): ?>
                        <option value="<?= htmlspecialchars($su['id']) ?>"><?= htmlspecialchars($su['nombre'] . ' (' . ($su['rpe'] ?? '') . ')') ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="helpSup" class="form-text">Jefe de departamento o jefe de oficina al que reporta esta persona. Sirve para permisos y para que cada jefe administre solo a quien tiene a cargo.</div>
            </div>
            <div class="col-12">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar" id="ca"><label class="form-check-label" for="ca">Puede asignar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_evaluar" id="ce"><label class="form-check-label" for="ce">Puede evaluar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_delegar" id="cd"><label class="form-check-label" for="cd">Puede delegar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar_pares" id="cp"><label class="form-check-label" for="cp">Asignar a pares</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="asignable_por_jefes_oficina" id="cj"><label class="form-check-label" for="cj">Asignable por jefes oficina</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="es_super_admin" id="cs"><label class="form-check-label" for="cs">Super admin</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="activo" id="cact" checked><label class="form-check-label" for="cact">Activo</label></div>
            </div>
            <div class="col-12">
                <label class="form-label small">
                    Alcance asignación
                    <i class="bi bi-question-circle ms-1 text-muted"
                       data-bs-toggle="tooltip"
                       data-bs-title="Define a qué niveles jerárquicos puede asignar tareas este usuario (p. ej. nivel_2 = jefes de oficina, nivel_3 = supervisores)."></i>
                </label>
                <div>
                    <label class="me-2"><input type="checkbox" name="alcance[]" value="nivel_2"> nivel_2</label>
                    <label><input type="checkbox" name="alcance[]" value="nivel_3" checked> nivel_3</label>
                </div>
            </div>
            <?php include __DIR__ . '/partials/admin-usuario-zona-fields.php'; ?>
            <?php else: ?>
            <div class="col-md-4">
                <label class="form-label">Cargo</label>
                <input type="text" name="cargo" class="form-control" placeholder="Supervisor">
            </div>
            <div class="col-12">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar" id="ca2"><label class="form-check-label" for="ca2">Puede asignar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_evaluar" id="ce2"><label class="form-check-label" for="ce2">Puede evaluar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar_pares" id="cp2"><label class="form-check-label" for="cp2">Asignar a pares (misma oficina)</label></div>
            </div>
            <?php endif; ?>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Crear usuario</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($editUser): ?>
<div class="card mb-4">
    <div class="card-header">Editar: <?= htmlspecialchars($editUser['nombre'] ?? '') ?></div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editUser['id'] ?? '') ?>">
            <div class="col-md-4">
                <label class="form-label">RPE</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?= htmlspecialchars($editUser['rpe'] ?? '') ?>" disabled id="rpeInputEdit">
                    <button class="btn btn-outline-secondary" type="button" id="btnLookupRpeEdit" onclick="return window.sigtaeLookupRpeEdit ? window.sigtaeLookupRpeEdit() : false;">Actualizar desde RPE</button>
                </div>
                <div class="form-text" id="rpeLookupMsgEdit"></div>
            </div>
            <div class="col-md-8">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($editUser['nombre'] ?? '') ?>" id="nombreInputEdit">
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" placeholder="correo@cfe.mx" id="emailInputEdit">
            </div>
            <?php if (!empty($showLocalPasswordFields)): ?>
            <div class="col-md-6">
                <label class="form-label">Nueva contraseña local (opcional)</label>
                <input type="password" name="password" class="form-control" placeholder="Solo si usa login local" autocomplete="new-password">
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label class="form-label">Cargo</label>
                <input type="text" name="cargo" class="form-control" value="<?= htmlspecialchars($editUser['cargo'] ?? '') ?>" id="cargoInputEdit">
            </div>
            <?php if ($isSuper): ?>
            <div class="col-md-2">
                <label class="form-label">Nivel</label>
                <select name="nivel_jerarquico" class="form-select">
                    <?php foreach ([1 => '1', 2 => '2', 3 => '3'] as $nv => $lab): ?>
                        <option value="<?= $nv ?>" <?= (int)($editUser['nivel_jerarquico'] ?? 3) === $nv ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['id']) ?>" <?= ($editUser['departamento_id'] ?? '') === $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Oficina</label>
                <select name="oficina_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?= htmlspecialchars($o['id']) ?>" <?= ($editUser['oficina_id'] ?? '') === $o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Supervisor inmediato</label>
                <select name="supervisor_id" class="form-select" aria-describedby="helpSupEd">
                    <option value="">—</option>
                    <?php foreach ($usersForSupervisor as $su): ?>
                        <?php if (($su['id'] ?? '') === ($editUser['id'] ?? '')) {
                            continue;
                        } ?>
                        <option value="<?= htmlspecialchars($su['id']) ?>" <?= ($editUser['supervisor_id'] ?? '') === $su['id'] ? 'selected' : '' ?>><?= htmlspecialchars($su['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="helpSupEd" class="form-text">Quién es su jefe directo en el organigrama (permisos y administración de usuarios).</div>
            </div>
            <div class="col-12">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar" id="ea" <?= !empty($editUser['puede_asignar']) ? 'checked' : '' ?>><label class="form-check-label" for="ea">Puede asignar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_evaluar" id="ee" <?= !empty($editUser['puede_evaluar']) ? 'checked' : '' ?>><label class="form-check-label" for="ee">Puede evaluar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_delegar" id="ed" <?= !empty($editUser['puede_delegar']) ? 'checked' : '' ?>><label class="form-check-label" for="ed">Puede delegar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar_pares" id="ep" <?= !empty($editUser['puede_asignar_pares']) ? 'checked' : '' ?>><label class="form-check-label" for="ep">Asignar a pares</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="asignable_por_jefes_oficina" id="ej" <?= !empty($editUser['asignable_por_jefes_oficina']) ? 'checked' : '' ?>><label class="form-check-label" for="ej">Asignable por jefes oficina</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="es_super_admin" id="es" <?= !empty($editUser['es_super_admin']) ? 'checked' : '' ?>><label class="form-check-label" for="es">Super admin</label></div>
            </div>
            <div class="col-12">
                <label class="form-label small">
                    Alcance asignación
                    <i class="bi bi-question-circle ms-1 text-muted"
                       data-bs-toggle="tooltip"
                       data-bs-title="Define a qué niveles jerárquicos puede asignar tareas este usuario (p. ej. nivel_2 = jefes de oficina, nivel_3 = supervisores)."></i>
                </label>
                <?php $alc = $editUser['alcance_asignacion'] ?? []; ?>
                <div>
                    <label class="me-2"><input type="checkbox" name="alcance[]" value="nivel_2" <?= in_array('nivel_2', $alc, true) ? 'checked' : '' ?>> nivel_2</label>
                    <label><input type="checkbox" name="alcance[]" value="nivel_3" <?= in_array('nivel_3', $alc, true) ? 'checked' : '' ?>> nivel_3</label>
                </div>
            </div>
            <?php include __DIR__ . '/partials/admin-usuario-zona-fields.php'; ?>
            <?php else: ?>
            <div class="col-12">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar" id="ea2" <?= !empty($editUser['puede_asignar']) ? 'checked' : '' ?>><label class="form-check-label" for="ea2">Puede asignar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_evaluar" id="ee2" <?= !empty($editUser['puede_evaluar']) ? 'checked' : '' ?>><label class="form-check-label" for="ee2">Puede evaluar</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="puede_asignar_pares" id="ep2" <?= !empty($editUser['puede_asignar_pares']) ? 'checked' : '' ?>><label class="form-check-label" for="ep2">Asignar a pares</label></div>
            </div>
            <?php endif; ?>
            <div class="col-12">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="activo" id="eact" value="1" <?= !empty($editUser['activo']) ? 'checked' : '' ?>><label class="form-check-label" for="eact">Activo</label></div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?= htmlspecialchars($basePath ?? '') ?>/admin-usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script>
(function() {
    function setMsg(el, text, kind) {
        if (!el) return;
        el.textContent = text || '';
        el.className = 'form-text ' + (kind === 'ok' ? 'text-success' : (kind === 'warn' ? 'text-warning' : (kind === 'err' ? 'text-danger' : 'text-muted')));
    }
    function log() {
        try { console.debug.apply(console, ['[usuarios-rpe]'].concat(Array.from(arguments))); } catch (_) {}
    }
    async function fetchJsonSafe(url) {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (_) {}
        if (!res.ok || !data || data.ok === false) {
            const msg = (data && data.message) ? data.message : (text ? String(text).slice(0, 180) : ('HTTP ' + res.status));
            throw new Error(msg);
        }
        return data;
    }
    async function lookupRpe(rpe, targets, msgEl, btnEl) {
        const base = window.SIGTAE_BASE_PATH || '';
        const q = String(rpe || '').trim().toUpperCase();
        if (!q) { setMsg(msgEl, 'Indica un RPE para buscar.', 'warn'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.classList.add('disabled'); }
        setMsg(msgEl, 'Buscando…', '');
        log('lookup start', { q });
        try {
            const url = base + '/api/usuarios.php?action=lookup_rpe&rpe=' + encodeURIComponent(q);
            const data = await fetchJsonSafe(url);
            const item = (data && data.item) ? data.item : null;
            if (!item) throw new Error('Sin datos.');
            if (targets.nombre && item.nombre) targets.nombre.value = item.nombre;
            if (targets.email && item.email) targets.email.value = item.email;
            if (targets.cargo && item.cargo) targets.cargo.value = item.cargo;
            setMsg(msgEl, 'Datos cargados desde el servicio.', 'ok');
            log('lookup ok', item);
            if (window.sigtaeInitTooltips) window.sigtaeInitTooltips();
        } catch (e) {
            log('lookup err', e);
            setMsg(msgEl, 'No se pudo cargar: ' + (e && e.message ? e.message : 'error'), 'err');
        } finally {
            if (btnEl) { btnEl.disabled = false; btnEl.classList.remove('disabled'); }
        }
    }

    function bind() {
        // Event delegation: robusto con PJAX/re-render
        if (document.__sigtaeUsersRpeDelegated) return;
        document.__sigtaeUsersRpeDelegated = true;
        log('bind delegated');
        document.addEventListener('click', function(e) {
            const t = e.target;
            const btn = t && t.closest ? t.closest('#btnLookupRpeCreate, #btnLookupRpeEdit') : null;
            if (!btn) return;
            e.preventDefault();
            if (btn.id === 'btnLookupRpeCreate') {
                const rpeC = document.getElementById('rpeInputCreate');
                setMsg(document.getElementById('rpeLookupMsgCreate'), 'Clic detectado…', '');
                lookupRpe(rpeC ? rpeC.value : '', {
                    nombre: document.getElementById('nombreInputCreate'),
                    email: document.getElementById('emailInputCreate'),
                    cargo: document.getElementById('cargoInputCreate'),
                }, document.getElementById('rpeLookupMsgCreate'), btn);
            } else {
                const rpeE = document.getElementById('rpeInputEdit');
                setMsg(document.getElementById('rpeLookupMsgEdit'), 'Clic detectado…', '');
                lookupRpe(rpeE ? rpeE.value : '', {
                    nombre: document.getElementById('nombreInputEdit'),
                    email: document.getElementById('emailInputEdit'),
                    cargo: document.getElementById('cargoInputEdit'),
                }, document.getElementById('rpeLookupMsgEdit'), btn);
            }
        });
    }

    function bindZonaUserPanels() {
        document.querySelectorAll('.js-zona-user-toggle').forEach(function(cb) {
            if (cb.dataset.zonaBound) return;
            cb.dataset.zonaBound = '1';
            var root = cb.closest('form');
            var panel = root ? root.querySelector('.js-zona-user-panel') : null;
            var sync = function() { if (panel) panel.style.display = cb.checked ? '' : 'none'; };
            cb.addEventListener('change', sync);
            sync();
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
    else bind();
    window.addEventListener('sigtae:pageLoaded', bind);
    bindZonaUserPanels();
    window.addEventListener('sigtae:pageLoaded', bindZonaUserPanels);

    // Fallback directo por onclick (para depurar cuando el binding no corre)
    window.sigtaeLookupRpeCreate = function() {
        log('onclick create');
        setMsg(document.getElementById('rpeLookupMsgCreate'), 'Clic detectado…', '');
        const btn = document.getElementById('btnLookupRpeCreate');
        const rpe = document.getElementById('rpeInputCreate');
        return lookupRpe(rpe ? rpe.value : '', {
            nombre: document.getElementById('nombreInputCreate'),
            email: document.getElementById('emailInputCreate'),
            cargo: document.getElementById('cargoInputCreate'),
        }, document.getElementById('rpeLookupMsgCreate'), btn), false;
    };
    window.sigtaeLookupRpeEdit = function() {
        log('onclick edit');
        setMsg(document.getElementById('rpeLookupMsgEdit'), 'Clic detectado…', '');
        const btn = document.getElementById('btnLookupRpeEdit');
        const rpe = document.getElementById('rpeInputEdit');
        return lookupRpe(rpe ? rpe.value : '', {
            nombre: document.getElementById('nombreInputEdit'),
            email: document.getElementById('emailInputEdit'),
            cargo: document.getElementById('cargoInputEdit'),
        }, document.getElementById('rpeLookupMsgEdit'), btn), false;
    };
})();
</script>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
            <thead class="table-light">
                <tr>
                    <th>RPE</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Oficina</th>
                    <th>Zona</th>
                    <th>Nivel</th>
                    <th>Activo</th>
                    <th></th>
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
                        <td><?= $of ? htmlspecialchars($of['nombre']) : '—' ?></td>
                        <td><?= !empty($u['es_usuario_zona']) ? htmlspecialchars($u['zona_nombre'] ?? $u['zona_id'] ?? '—') : '—' ?></td>
                        <td><?= (int)($u['nivel_jerarquico'] ?? '-') ?></td>
                        <td><?= !empty($u['activo']) ? 'Sí' : 'No' ?></td>
                        <td class="text-nowrap">
                            <?php
                            $canE = \App\Services\UserAdminGuard::canEditUser($currentUser ?? [], $u);
                            ?>
                            <?php if ($canE): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/admin-usuarios.php?edit=<?= urlencode($u['id'] ?? '') ?>">Editar</a>
                            <?php endif; ?>
                            <?php if ($canE && ($u['id'] ?? '') !== ($currentUser['id'] ?? '')): ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Desactivar usuario?');">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($u['id'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Desactivar</button>
                                </form>
                            <?php endif; ?>
                            <?php if (\App\Services\UserAdminGuard::canDeleteUser($currentUser ?? [], $u)): ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($u['id'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
$(function() { $('#tablaUsuarios').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[1,'asc']] }); });
</script>
