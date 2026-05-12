<?php
$bitacora = $bitacora ?? [];
$anio = $anio ?? (int)date('Y');
$fZona = $fZona ?? '';
$fArea = $fArea ?? '';
$fFolio = $fFolio ?? '';
$fSerie = $fSerie ?? '';
$fQuery = $fQuery ?? '';
$fEstado = $fEstado ?? '';
$zonasEntrega = $zonasEntrega ?? [];
$canEditBitacora = !empty($currentUser['es_super_admin']) || in_array(strtoupper(trim((string)($currentUser['rpe'] ?? ''))), ['G46B8','9L3DR'], true);

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Bitácora', 'Concentrado por equipo (Recepción / Programa anual)', $actions);
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-journal-text me-1"></i> Bitácora (concentrado por equipo)</span>
        <span class="text-muted small"><?= count($bitacora) ?> registros</span>
    </div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end mb-2">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Año</label>
                <input type="number" class="form-control form-control-sm" name="anio" value="<?= (int)$anio ?>" min="2020" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Zona</label>
                <input type="text" class="form-control form-control-sm" name="zona" value="<?= htmlspecialchars($fZona) ?>" placeholder="Ej. DM210">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Área (texto)</label>
                <input type="text" class="form-control form-control-sm" name="area" value="<?= htmlspecialchars($fArea) ?>" placeholder="Ej. MEDICION">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Folio o Serie</label>
                <input type="text" class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($fQuery) ?>" placeholder="Ej. 2026-0281 o No. serie">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select class="form-select form-select-sm" name="estado">
                    <option value="">Todos</option>
                    <option value="programado" <?= $fEstado === 'programado' ? 'selected' : '' ?>>Programado</option>
                    <option value="no_programado" <?= $fEstado === 'no_programado' ? 'selected' : '' ?>>No Programado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>

        <?php if (empty($bitacora)): ?>
            <?php sigtae_empty_state('Sin registros con esos filtros.', 'bi-journal-text'); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaBitacora">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Folio</th>
                            <th>Serie</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Descripción</th>
                            <th>Zona</th>
                            <th>Área</th>
                            <th>Oficina</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bitacora as $b): ?>
                            <tr data-id="<?= htmlspecialchars((string)($b['id'] ?? '')) ?>"
                                data-folio="<?= htmlspecialchars((string)($b['folio'] ?? '')) ?>"
                                data-no_serie="<?= htmlspecialchars((string)($b['no_serie'] ?? '')) ?>"
                                data-marca="<?= htmlspecialchars((string)($b['marca'] ?? '')) ?>"
                                data-modelo="<?= htmlspecialchars((string)($b['modelo'] ?? '')) ?>"
                                data-descripcion="<?= htmlspecialchars((string)($b['descripcion'] ?? '')) ?>"
                                data-zona="<?= htmlspecialchars((string)($b['zona'] ?? '')) ?>"
                                data-area="<?= htmlspecialchars((string)($b['area'] ?? '')) ?>"
                                data-oficina="<?= htmlspecialchars((string)($b['oficina'] ?? '')) ?>"
                                data-observaciones="<?= htmlspecialchars((string)($b['observaciones'] ?? '')) ?>">
                                <td class="fw-semibold"><?= htmlspecialchars($b['folio'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['no_serie'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['marca'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['modelo'] ?? '') ?></td>
                                <td><?= htmlspecialchars(mb_substr($b['descripcion'] ?? '', 0, 60)) ?></td>
                                <td><?= htmlspecialchars($b['zona'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['area'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['oficina'] ?? '') ?></td>
                                <?php $estadoLabel = (($b['estado'] ?? '') === 'programado') ? 'Programado' : 'No Programado'; ?>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($estadoLabel) ?></span></td>
                                <td><?= htmlspecialchars(mb_substr($b['observaciones'] ?? '', 0, 40)) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btnViewBit" title="Ver completo"><i class="bi bi-eye"></i></button>
                                    <?php if ($canEditBitacora): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btnEditBit" title="Editar"><i class="bi bi-pencil"></i></button>
                                    <?php endif; ?>
                                    <?php if (!empty($b['recepcion_id'])): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($basePath ?? '') ?>/metrologia-recepcion.php?rid=<?= urlencode((string)$b['recepcion_id']) ?>" title="Abrir recepción (página)">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                           href="<?= htmlspecialchars($basePath ?? '') ?>/metrologia-recepcion-formato.php?rid=<?= urlencode((string)$b['recepcion_id']) ?>"
                                           title="Imprimir formato de recepción">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
            (function(){
                function initBitacoraDataTable() {
                    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) return;
                    const $tbl = jQuery('#tablaBitacora');
                    if (!$tbl.length) return;
                    if (jQuery.fn.DataTable.isDataTable($tbl[0])) return;
                    $tbl.DataTable({
                        language: { url: (window.SIGTAE_BASE_PATH || '') + '/vendor/datatables/i18n/es-ES.json' },
                        order: [[0,'desc']],
                        pageLength: 15
                    });
                }
                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBitacoraDataTable);
                else initBitacoraDataTable();
                window.addEventListener('sigtae:pageLoaded', initBitacoraDataTable);
            })();
            </script>

            <?php if ($canEditBitacora): ?>
            <!-- Modal edición Bitácora -->
            <div class="modal fade" id="modalBitEdit" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar equipo (Bitácora)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" id="bitErr"></div>
                            <input type="hidden" id="bitId">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Folio</label>
                                    <input class="form-control form-control-sm" id="bitFolio">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">No. serie</label>
                                    <input class="form-control form-control-sm" id="bitSerie">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Oficina</label>
                                    <input class="form-control form-control-sm" id="bitOficina">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Marca *</label>
                                    <input class="form-control form-control-sm" id="bitMarca" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Modelo *</label>
                                    <input class="form-control form-control-sm" id="bitModelo" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Zona</label>
                                    <input class="form-control form-control-sm" id="bitZona">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Área</label>
                                    <input class="form-control form-control-sm" id="bitArea">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">Descripción *</label>
                                    <input class="form-control form-control-sm" id="bitDesc" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Programa anual</label>
                                    <input class="form-control form-control-sm" id="bitProgramaAnual" placeholder="Ej. S">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Recibido</label>
                                    <input type="date" class="form-control form-control-sm" id="bitRecibido">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Técnico</label>
                                    <input class="form-control form-control-sm" id="bitTecnico" placeholder="RPE">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Fecha cal./baja</label>
                                    <input type="date" class="form-control form-control-sm" id="bitFechaCal">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Eval. conformidad</label>
                                    <input class="form-control form-control-sm" id="bitEvalConf" placeholder="Ej. 1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Fecha impresión</label>
                                    <input type="date" class="form-control form-control-sm" id="bitFechaImp">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Entrega informe esc.</label>
                                    <input type="date" class="form-control form-control-sm" id="bitFechaEntEsc">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Entregado</label>
                                    <input type="date" class="form-control form-control-sm" id="bitEntregado">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Nombre a quien se entrega</label>
                                    <input class="form-control form-control-sm" id="bitAQuien">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Nomenclatura GMCS</label>
                                    <input class="form-control form-control-sm" id="bitNomGmcs">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold mb-1">Jefe de área</label>
                                    <input class="form-control form-control-sm" id="bitJefeArea">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">RPE jefe área</label>
                                    <input class="form-control form-control-sm" id="bitRpeJefeArea">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Fecha programada</label>
                                    <input class="form-control form-control-sm" id="bitFechaProg" placeholder="Ej. may-26">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold mb-1">Tablero evolutivo</label>
                                    <input class="form-control form-control-sm" id="bitTablero" placeholder="Ej. S">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold mb-1">Observaciones</label>
                                    <textarea class="form-control form-control-sm" id="bitObs" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-primary" type="button" id="btnBitSave"><i class="bi bi-save me-1"></i>Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                function initBitacoraEditModal() {
                    if (!window.bootstrap || !bootstrap.Modal) return;
                    const base = window.SIGTAE_BASE_PATH || '';
                    const apiUpdate = base + '/api/metrologia-bitacora.php?action=update';
                    const apiDetail = base + '/api/metrologia-bitacora.php?action=detail&id=';
                    const modalEl = document.getElementById('modalBitEdit');
                    const errEl = document.getElementById('bitErr');
                    if (!modalEl || !errEl) return;

                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    function showErr(msg){ errEl.textContent = msg || ''; errEl.classList.toggle('d-none', !msg); }
                    function setv(id,v){ const el=document.getElementById(id); if(el) el.value = v ?? ''; }
                    function val(id){ const el=document.getElementById(id); return el ? el.value : ''; }
                    async function apiJson(url, opts){
                        const res = await fetch(url, Object.assign({ headers: { 'X-Requested-With': 'fetch' } }, opts||{}));
                        const data = await res.json().catch(()=>null);
                        if(!res.ok || !data || data.ok===false) throw new Error((data&&data.message)?data.message:('HTTP '+res.status));
                        return data;
                    }
                    async function loadToForm(id){
                        const data = await apiJson(apiDetail + encodeURIComponent(id), { method:'GET' });
                        const it = data.item || {};
                        setv('bitId', it.id||'');
                        setv('bitFolio', it.folio||'');
                        setv('bitSerie', it.no_serie||'');
                        setv('bitMarca', it.marca||'');
                        setv('bitModelo', it.modelo||'');
                        setv('bitDesc', it.descripcion||'');
                        setv('bitZona', it.zona||'');
                        setv('bitArea', it.area||'');
                        setv('bitOficina', it.oficina||'');
                        setv('bitProgramaAnual', it.programa_anual||'');
                        setv('bitRecibido', it.recibido||'');
                        setv('bitTecnico', it.tecnico||'');
                        setv('bitFechaCal', it.fecha_calibracion_baja||'');
                        setv('bitEvalConf', it.evaluacion_conformidad||'');
                        setv('bitFechaImp', it.fecha_impresion||'');
                        setv('bitFechaEntEsc', it.fecha_entrega_informe_escaneado||'');
                        setv('bitEntregado', it.entregado||'');
                        setv('bitAQuien', it.nombre_a_quien_se_entrega||'');
                        setv('bitNomGmcs', it.nomenclatura_gmcs||'');
                        setv('bitJefeArea', it.jefe_area||'');
                        setv('bitRpeJefeArea', it.rpe_jefe_area||'');
                        setv('bitFechaProg', it.fecha_programada||'');
                        setv('bitTablero', it.tablero_evolutivo||'');
                        setv('bitObs', it.observaciones||'');
                    }
                    async function save(){
                        showErr('');
                        try{
                            await apiJson(apiUpdate, {
                                method:'POST',
                                headers:{ 'Content-Type':'application/json', 'X-Requested-With':'fetch' },
                                body: JSON.stringify({
                                    id: val('bitId'),
                                    folio: val('bitFolio'),
                                    no_serie: val('bitSerie'),
                                    marca: val('bitMarca'),
                                    modelo: val('bitModelo'),
                                    descripcion: val('bitDesc'),
                                    zona: val('bitZona'),
                                    area: val('bitArea'),
                                    oficina: val('bitOficina'),
                                    programa_anual: val('bitProgramaAnual'),
                                    recibido: val('bitRecibido'),
                                    tecnico: val('bitTecnico'),
                                    fecha_calibracion_baja: val('bitFechaCal'),
                                    evaluacion_conformidad: val('bitEvalConf'),
                                    fecha_impresion: val('bitFechaImp'),
                                    fecha_entrega_informe_escaneado: val('bitFechaEntEsc'),
                                    entregado: val('bitEntregado'),
                                    nombre_a_quien_se_entrega: val('bitAQuien'),
                                    nomenclatura_gmcs: val('bitNomGmcs'),
                                    jefe_area: val('bitJefeArea'),
                                    rpe_jefe_area: val('bitRpeJefeArea'),
                                    fecha_programada: val('bitFechaProg'),
                                    tablero_evolutivo: val('bitTablero'),
                                    observaciones: val('bitObs'),
                                })
                            });
                            window.location.reload();
                        }catch(e){
                            showErr(e.message || String(e));
                        }
                    }

                    document.querySelectorAll('.btnEditBit').forEach(btn=>{
                        if (btn.__sigtaeBoundEdit) return;
                        btn.__sigtaeBoundEdit = true;
                        btn.addEventListener('click', async ()=>{
                            const tr = btn.closest('tr');
                            const id = tr ? (tr.getAttribute('data-id')||'') : '';
                            if(!id) return;
                            showErr('');
                            try{
                                await loadToForm(id);
                                modal.show();
                            }catch(e){
                                showErr(e.message || String(e));
                                modal.show();
                            }
                        });
                    });
                    const btnSave = document.getElementById('btnBitSave');
                    if(btnSave && !btnSave.__sigtaeBoundEditSave){
                        btnSave.__sigtaeBoundEditSave = true;
                        btnSave.addEventListener('click', save);
                    }
                }

                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBitacoraEditModal);
                else initBitacoraEditModal();
                window.addEventListener('sigtae:pageLoaded', initBitacoraEditModal);
            })();
            </script>
            <?php endif; ?>

            <!-- Modal ver completo Bitácora -->
            <div class="modal fade" id="modalBitView" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Equipo (Bitácora) — Información completa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" id="bitViewErr"></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <tbody id="bitViewBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            (function(){
                function initBitacoraViewModal() {
                    if (!window.bootstrap || !bootstrap.Modal) return;
                    const base = window.SIGTAE_BASE_PATH || '';
                    const apiDetail = base + '/api/metrologia-bitacora.php?action=detail&id=';
                    const apiAudit = base + '/api/metrologia-bitacora.php?action=audit&id=';
                    const modalEl = document.getElementById('modalBitView');
                    const errEl = document.getElementById('bitViewErr');
                    const bodyEl = document.getElementById('bitViewBody');
                    if (!modalEl || !errEl || !bodyEl) return;
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

                    function showErr(msg){ errEl.textContent = msg || ''; errEl.classList.toggle('d-none', !msg); }
                    function esc(s){
                        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c] || c));
                    }
                    function addRow(label, value){
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<th class="small text-muted" style="width: 280px;">${esc(label)}</th><td>${esc(value || '—')}</td>`;
                        bodyEl.appendChild(tr);
                    }
                    async function apiJson(url){
                        const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
                        const data = await res.json().catch(()=>null);
                        if(!res.ok || !data || data.ok===false) throw new Error((data&&data.message)?data.message:('HTTP '+res.status));
                        return data;
                    }
                    async function apiJsonAudit(url){
                        const urls = [url, url.replace('/api/', '/public/api/')];
                        for (const u of urls) {
                            try {
                                return await apiJson(u);
                            } catch (_) {}
                        }
                        return { items: [] };
                    }

                    async function open(id){
                        showErr('');
                        bodyEl.innerHTML = '';
                        try{
                            const data = await apiJson(apiDetail + encodeURIComponent(id));
                            const it = data.item || {};

                            addRow('DESCRIPCIÓN', it.descripcion);
                            addRow('FOLIO', it.folio);
                            addRow('No. SERIE', it.no_serie);
                            addRow('PROGRAMA ANUAL DE CALIBRACIÓN', it.programa_anual);
                            addRow('RECIBIDO', it.recibido);
                            addRow('TÉCNICO', it.tecnico);
                            addRow('FECHA DE CALIBRACIÓN/BAJA', it.fecha_calibracion_baja);
                            addRow('EVALUACIÓN DE CONFORMIDAD', it.evaluacion_conformidad);
                            addRow('FECHA DE IMPRESIÓN', it.fecha_impresion);
                            addRow('FECHA DE ENTREGA DE INFORME ESCANEADO', it.fecha_entrega_informe_escaneado);
                            addRow('ENTREGADO', it.entregado);
                            addRow('NOMBRE A QUIEN SE ENTREGA', it.nombre_a_quien_se_entrega);
                            addRow('MARCA', it.marca);
                            addRow('MODELO', it.modelo);
                            addRow('ZONA', it.zona);
                            addRow('ÁREA', it.area);
                            addRow('OFICINA', it.oficina);
                            addRow('NOMENCLATURA GMCS', it.nomenclatura_gmcs);
                            addRow('JEFE DE ÁREA', it.jefe_area);
                            addRow('RPE JEFE DE ÁREA', it.rpe_jefe_area);
                            addRow('FECHA PROGRAMADA', it.fecha_programada);
                            addRow('TABLERO EVOLUTIVO', it.tablero_evolutivo);
                            addRow('OBSERVACIONES', it.observaciones);

                            const aud = await apiJsonAudit(apiAudit + encodeURIComponent(id));
                            const auditItems = aud.items || [];
                            const trH = document.createElement('tr');
                            trH.innerHTML = '<th colspan="2" class="table-secondary small py-2">Historial de cambios (auditoría)</th>';
                            bodyEl.appendChild(trH);
                            if (!auditItems.length) {
                                addRow('Auditoría', 'Sin registros de edición en bitácora.');
                            } else {
                                for (const ev of auditItems) {
                                    const m = ev.metadata || {};
                                    const cambios = m.cambios || [];
                                    const who = ev.actor_label || m.actor_nombre || ev.actor_user_id || '—';
                                    const bullets = cambios.map(c =>
                                        `• ${esc(c.etiqueta)}: «${esc(String(c.anterior ?? ''))}» → «${esc(String(c.nuevo ?? ''))}»`
                                    ).join('<br>');
                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `<th class="small text-muted align-top">${esc(ev.fecha_hora || '')}</th><td class="small"><div class="fw-semibold mb-1">${esc(who)}</div><div>${bullets || '—'}</div></td>`;
                                    bodyEl.appendChild(tr);
                                }
                            }

                            modal.show();
                        } catch(e){
                            showErr(e.message || String(e));
                            modal.show();
                        }
                    }

                    document.querySelectorAll('.btnViewBit').forEach(btn=>{
                        if (btn.__sigtaeBoundView) return;
                        btn.__sigtaeBoundView = true;
                        btn.addEventListener('click', ()=>{
                            const tr = btn.closest('tr');
                            const id = tr ? (tr.getAttribute('data-id')||'') : '';
                            if(!id) return;
                            open(id);
                        });
                    });
                }

                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBitacoraViewModal);
                else initBitacoraViewModal();
                window.addEventListener('sigtae:pageLoaded', initBitacoraViewModal);
            })();
            </script>
        <?php endif; ?>
    </div>
</div>

