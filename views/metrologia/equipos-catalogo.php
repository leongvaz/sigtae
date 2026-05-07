<?php
$items = $items ?? [];
$q = trim((string)($_GET['q'] ?? ''));

$actions =
    '<div class="d-flex gap-2">'
    . '<button class="btn btn-sm btn-primary" type="button" id="btnNuevo"><i class="bi bi-plus-lg"></i> Nuevo</button>'
    . '<a class="btn btn-sm btn-outline-secondary" href="' . htmlspecialchars($basePath ?? '') . '/metrologia-dashboard.php"><i class="bi bi-arrow-left"></i> Dashboard</a>'
    . '</div>';
sigtae_page_header('Catálogo de equipos', 'Base maestra de equipos (marca/modelo/serie/descripción/zona/área/oficina)', $actions);
?>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-8">
                <label class="form-label small fw-semibold mb-1">Buscar</label>
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Serie, marca, modelo, descripción, zona, área, oficina, folio...">
            </div>
            <div class="col-md-4">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-box-seam me-1"></i> Equipos</span>
        <span class="text-muted small"><?= count($items) ?> registros</span>
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
            <?php sigtae_empty_state('Sin resultados.', 'bi-box-seam'); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaEquiposCat">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Serie</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Descripción</th>
                            <th>Zona</th>
                            <th>Área</th>
                            <th>Oficina</th>
                            <th>Folio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $e): ?>
                            <tr data-id="<?= htmlspecialchars((string)($e['id'] ?? '')) ?>">
                                <td class="fw-semibold"><?= htmlspecialchars((string)($e['no_serie'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($e['marca'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($e['modelo'] ?? '')) ?></td>
                                <td><?= htmlspecialchars(mb_substr((string)($e['descripcion'] ?? ''), 0, 70)) ?></td>
                                <td><?= htmlspecialchars((string)($e['zona'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($e['area'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($e['oficina'] ?? '')) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars((string)($e['folio'] ?? '')) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary btnEdit" title="Editar"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btnDel" title="Borrar"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal editar/crear -->
<div class="modal fade" id="modalEquipo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEquipoTitle">Equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="eqErr"></div>
                <form id="formEquipo" class="row g-2">
                    <input type="hidden" id="eqId">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">No. serie</label>
                        <input class="form-control form-control-sm" id="eqSerie" placeholder="Serie">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Marca *</label>
                        <input class="form-control form-control-sm" id="eqMarca" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Modelo *</label>
                        <input class="form-control form-control-sm" id="eqModelo" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Descripción *</label>
                        <input class="form-control form-control-sm" id="eqDesc" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Zona</label>
                        <input class="form-control form-control-sm" id="eqZona">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Área</label>
                        <input class="form-control form-control-sm" id="eqArea">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Oficina</label>
                        <input class="form-control form-control-sm" id="eqOficina">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Folio</label>
                        <input class="form-control form-control-sm" id="eqFolio">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
                <button class="btn btn-primary" id="btnGuardarEq" type="button"><i class="bi bi-save me-1"></i>Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const base = window.SIGTAE_BASE_PATH || '';
    const api = base + '/api/metrologia-equipos.php';
    const modalEl = document.getElementById('modalEquipo');
    const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    const errEl = document.getElementById('eqErr');

    function showErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || 'Error';
        errEl.classList.toggle('d-none', !msg);
    }
    function val(id) { const el = document.getElementById(id); return el ? el.value : ''; }
    function setv(id, v) { const el = document.getElementById(id); if (el) el.value = v ?? ''; }

    async function apiJson(url, opts) {
        const res = await fetch(url, Object.assign({ headers: { 'X-Requested-With': 'fetch' } }, opts || {}));
        const data = await res.json().catch(() => null);
        if (!res.ok || !data || data.ok === false) throw new Error((data && data.message) ? data.message : ('HTTP ' + res.status));
        return data;
    }

    async function openNew() {
        showErr('');
        document.getElementById('modalEquipoTitle').textContent = 'Nuevo equipo';
        setv('eqId',''); setv('eqSerie',''); setv('eqMarca',''); setv('eqModelo',''); setv('eqDesc','');
        setv('eqZona',''); setv('eqArea',''); setv('eqOficina',''); setv('eqFolio','');
        modal && modal.show();
    }

    async function openEdit(id) {
        showErr('');
        document.getElementById('modalEquipoTitle').textContent = 'Editar equipo';
        const data = await apiJson(api + '?action=get&id=' + encodeURIComponent(id));
        const it = data.item || {};
        setv('eqId', it.id || '');
        setv('eqSerie', it.no_serie || '');
        setv('eqMarca', it.marca || '');
        setv('eqModelo', it.modelo || '');
        setv('eqDesc', it.descripcion || '');
        setv('eqZona', it.zona || '');
        setv('eqArea', it.area || '');
        setv('eqOficina', it.oficina || '');
        setv('eqFolio', it.folio || '');
        modal && modal.show();
    }

    async function save() {
        showErr('');
        const payload = {
            id: val('eqId'),
            no_serie: val('eqSerie'),
            marca: val('eqMarca'),
            modelo: val('eqModelo'),
            descripcion: val('eqDesc'),
            zona: val('eqZona'),
            area: val('eqArea'),
            oficina: val('eqOficina'),
            folio: val('eqFolio'),
        };
        try {
            await apiJson(api + '?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
                body: JSON.stringify(payload),
            });
            window.location.reload();
        } catch (e) {
            showErr(e.message || String(e));
        }
    }

    async function del(id) {
        if (!confirm('¿Borrar este equipo del catálogo maestro?')) return;
        try {
            await apiJson(api + '?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
                body: JSON.stringify({ id }),
            });
            window.location.reload();
        } catch (e) {
            alert(e.message || String(e));
        }
    }

    const btnNuevo = document.getElementById('btnNuevo');
    if (btnNuevo) btnNuevo.addEventListener('click', openNew);
    const btnGuardar = document.getElementById('btnGuardarEq');
    if (btnGuardar) btnGuardar.addEventListener('click', save);

    document.querySelectorAll('#tablaEquiposCat .btnEdit').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = btn.closest('tr');
            const id = tr ? tr.getAttribute('data-id') : '';
            if (id) openEdit(id);
        });
    });
    document.querySelectorAll('#tablaEquiposCat .btnDel').forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = btn.closest('tr');
            const id = tr ? tr.getAttribute('data-id') : '';
            if (id) del(id);
        });
    });

    $(function() {
        const t = $('#tablaEquiposCat');
        if (t.length) {
            t.DataTable({
                language: { url: (window.SIGTAE_BASE_PATH || '') + '/vendor/datatables/i18n/es-ES.json' },
                order: [[0,'asc']],
                pageLength: 15
            });
        }
    });
})();
</script>

