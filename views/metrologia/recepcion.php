<?php
$catalogos = $catalogos ?? [];
$detalleRecepcion = $detalleRecepcion ?? null;
$error = $error ?? '';
$warning = $warning ?? '';
$success = $success ?? '';
$canManage = $canManage ?? false;
$suggestNextEquipoFolio = $suggestNextEquipoFolio ?? '';

$zonasEntrega = $zonasEntrega ?? [
    'Zocalo',
    'Benito Juarez',
    'Polanco',
    'Tacuba',
    'Aeropuerto',
    'Nezahuacoyotl',
    'Chapingo',
];

$recibeNombre = trim((string)(($currentUser['nombre'] ?? '') ?: ''));
$recibeRpe = strtoupper(trim((string)(($currentUser['rpe'] ?? '') ?: '')));

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Recepción de equipos', 'RECEPCIÓN DE EQUIPOS POR PARTE DEL LABORATORIO DE MEDICIÓN DIVISIONAL', $actions);
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($warning): ?><div class="alert alert-warning"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($warning) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card mb-3">
    <div class="card-header card-header-accent"><i class="bi bi-plus-circle me-1"></i> Nueva recepción</div>
    <div class="card-body">
        <?php if (!$canManage): ?>
            <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-1"></i>Cuenta con acceso de lectura. Para registrar recepciones requiere permisos de gestión en Metrología.</div>
        <?php else: ?>
        <form method="post" id="formRecepcion" class="row g-3">
            <input type="hidden" name="action" value="guardar_recepcion">

            <div class="col-12">
                <label class="form-label small fw-semibold mb-1">Motivo por el que se reciben los equipos *</label>
                <input type="text" name="motivo_recepcion" class="form-control" required placeholder="Ej. Equipos para calibración">
            </div>

            <div class="col-12">
                <div class="row g-2">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header"><i class="bi bi-person-check me-1"></i> RECIBE (Laboratorio)</div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold mb-1">Nombre</label>
                                        <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($recibeNombre) ?>" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">RPE</label>
                                        <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($recibeRpe) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Área</label>
                                        <input type="text" class="form-control form-control-sm" value="Metrología" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Zona</label>
                                        <input type="text" class="form-control form-control-sm" value="DM-000" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold mb-1">Firma</label>
                                        <div class="border rounded p-3 text-muted bg-light" style="min-height: 90px;">
                                            Pendiente de integración con capturadora
                                        </div>
                                        <div class="form-text">Se guardará como pendiente: `firma_recibe_* = null` y `firma_recibe_pendiente = true`.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header"><i class="bi bi-person-up me-1"></i> ENTREGA (Zona/Área)</div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">RPE *</label>
                                        <input type="text" name="entrega_rpe" class="form-control form-control-sm" required placeholder="Ej. 9L7R4">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold mb-1">Nombre *</label>
                                        <input type="text" name="entrega_nombre" class="form-control form-control-sm" required placeholder="Nombre completo">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Zona *</label>
                                        <select class="form-select form-select-sm" name="entrega_zona" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($zonasEntrega as $z): ?>
                                                <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Área *</label>
                                        <input type="text" name="entrega_area" class="form-control form-control-sm" required placeholder="Ej. Medición, ISC, etc.">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold mb-1">Firma</label>
                                        <div class="border rounded p-3 text-muted bg-light" style="min-height: 90px;">
                                            Pendiente de integración con capturadora
                                        </div>
                                        <div class="form-text">Se guardará como pendiente: `firma_entrega_* = null` y `firma_entrega_pendiente = true`.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-table me-1"></i> Equipos recibidos</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddEquipo"><i class="bi bi-plus-lg"></i> Agregar equipo</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveEquipo"><i class="bi bi-dash-lg"></i> Quitar</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" id="tablaEquipos">
                                <thead class="table-light">
                                    <tr class="small">
                                        <th style="width:54px;">No.</th>
                                        <th style="min-width:160px;">Marca *</th>
                                        <th style="min-width:160px;">Modelo *</th>
                                        <th style="min-width:160px;">Serie *</th>
                                        <th style="min-width:220px;">Descripción *</th>
                                        <th style="min-width:220px;">Observaciones</th>
                                        <th style="width:120px;">Folio</th>
                                    </tr>
                                </thead>
                                <tbody id="equiposBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="chkDup" name="confirmar_series_duplicadas">
                            <label class="form-check-label small" for="chkDup">Confirmo que deseo continuar aunque existan series duplicadas en bitácora.</label>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Guardar recepción</button>
                    </div>
                </div>
            </div>
        </form>

        <script>
        (function() {
            const body = document.getElementById('equiposBody');
            const btnAdd = document.getElementById('btnAddEquipo');
            const btnRemove = document.getElementById('btnRemoveEquipo');
            const table = document.getElementById('tablaEquipos');
            const base = window.SIGTAE_BASE_PATH || '';
            const apiSuggest = base + '/api/metrologia-equipos.php?action=suggest';
            let nextFolio = <?= json_encode((string)$suggestNextEquipoFolio) ?>;

            function esc(s) {
                return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c] || c));
            }

            function renumber() {
                Array.from(body.querySelectorAll('tr')).forEach((tr, idx) => {
                    const n = idx + 1;
                    const cell = tr.querySelector('[data-col="numero"]');
                    if (cell) cell.textContent = String(n);
                });
            }

            function addRow(prefill = {}) {
                const tr = document.createElement('tr');
                const uid = 'eq_' + Math.random().toString(16).slice(2);
                tr.innerHTML = `
                    <td class="fw-semibold text-muted" data-col="numero"></td>
                    <td>
                        <input name="equipo_marca[]" class="form-control form-control-sm js-eq-marca" required
                               list="${uid}_marca_list" value="${esc(prefill.marca)}" autocomplete="off">
                        <datalist id="${uid}_marca_list"></datalist>
                    </td>
                    <td>
                        <input name="equipo_modelo[]" class="form-control form-control-sm js-eq-modelo" required
                               list="${uid}_modelo_list" value="${esc(prefill.modelo)}" autocomplete="off">
                        <datalist id="${uid}_modelo_list"></datalist>
                    </td>
                    <td>
                        <input name="equipo_serie[]" class="form-control form-control-sm js-eq-serie" required
                               list="${uid}_serie_list" value="${esc(prefill.serie)}" autocomplete="off">
                        <datalist id="${uid}_serie_list"></datalist>
                    </td>
                    <td>
                        <input name="equipo_descripcion[]" class="form-control form-control-sm js-eq-desc" required
                               list="${uid}_desc_list" value="${esc(prefill.descripcion)}" autocomplete="off">
                        <datalist id="${uid}_desc_list"></datalist>
                    </td>
                    <td><input name="equipo_observaciones[]" class="form-control form-control-sm" value="${esc(prefill.observaciones)}"></td>
                    <td><input name="equipo_folio[]" class="form-control form-control-sm js-eq-folio" value="${esc(prefill.folio || nextFolio)}" placeholder="${esc(nextFolio)}"></td>
                `;
                body.appendChild(tr);
                renumber();

                // Avanza sugerencia de folio localmente (AAAA-NNNN).
                (function bumpFolio(){
                    const m = String(nextFolio || '').match(/^(\d{4})-(\d{4})$/);
                    if (!m) return;
                    const y = m[1];
                    const n = parseInt(m[2], 10);
                    if (!isFinite(n)) return;
                    nextFolio = y + '-' + String(n + 1).padStart(4, '0');
                })();

                // Autocompletado + autollenado (como estaba funcionando)
                function debounce(fn, ms) {
                    let t = null;
                    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
                }
                async function suggest(field, q) {
                    q = String(q || '').trim();
                    if (q.length < 2) return [];
                    const qs = '&field=' + encodeURIComponent(field) + '&q=' + encodeURIComponent(q) + '&limit=12';
                    // En algunos entornos (localhost sin rewrite) el API queda bajo /public/api.
                    const urls = [
                        apiSuggest + qs,
                        (base + '/public/api/metrologia-equipos.php?action=suggest') + qs,
                    ];
                    for (const url of urls) {
                        try {
                            const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
                            if (!res.ok) continue;
                            const data = await res.json().catch(() => null);
                            if (!data || data.ok === false) continue;
                            return data.items || [];
                        } catch (_) {}
                    }
                    return [];
                }
                function fillFromItem(item) {
                    if (!item) return;
                    if (item.marca) marcaEl.value = item.marca;
                    if (item.modelo) modeloEl.value = item.modelo;
                    if (item.no_serie) serieEl.value = item.no_serie;
                    if (item.descripcion) descEl.value = item.descripcion;
                }
                function setDatalist(dl, items) {
                    if (!dl) return;
                    dl.innerHTML = '';
                    for (const it of (items || [])) {
                        const opt = document.createElement('option');
                        opt.value = it.value || '';
                        dl.appendChild(opt);
                    }
                }

                const marcaEl = tr.querySelector('.js-eq-marca');
                const modeloEl = tr.querySelector('.js-eq-modelo');
                const serieEl = tr.querySelector('.js-eq-serie');
                const descEl = tr.querySelector('.js-eq-desc');
                const dlMarca = tr.querySelector('#' + CSS.escape(uid + '_marca_list'));
                const dlModelo = tr.querySelector('#' + CSS.escape(uid + '_modelo_list'));
                const dlSerie = tr.querySelector('#' + CSS.escape(uid + '_serie_list'));
                const dlDesc = tr.querySelector('#' + CSS.escape(uid + '_desc_list'));
                const mapMarca = new Map(), mapModelo = new Map(), mapSerie = new Map(), mapDesc = new Map();

                const onMarca = debounce(async () => {
                    const items = await suggest('marca', marcaEl.value);
                    mapMarca.clear(); items.forEach(it => mapMarca.set(String(it.value||''), it));
                    setDatalist(dlMarca, items);
                }, 180);
                const onModelo = debounce(async () => {
                    const items = await suggest('modelo', modeloEl.value);
                    mapModelo.clear(); items.forEach(it => mapModelo.set(String(it.value||''), it));
                    setDatalist(dlModelo, items);
                }, 180);
                const onSerie = debounce(async () => {
                    const items = await suggest('no_serie', serieEl.value);
                    mapSerie.clear(); items.forEach(it => mapSerie.set(String(it.value||''), it));
                    setDatalist(dlSerie, items);
                }, 180);
                const onDesc = debounce(async () => {
                    const items = await suggest('descripcion', descEl.value);
                    mapDesc.clear(); items.forEach(it => mapDesc.set(String(it.value||''), it));
                    setDatalist(dlDesc, items);
                }, 180);

                marcaEl.addEventListener('input', onMarca);
                modeloEl.addEventListener('input', onModelo);
                serieEl.addEventListener('input', onSerie);
                descEl.addEventListener('input', onDesc);

                function maybeFill(map, el) {
                    const it = map.get(String(el.value || ''));
                    if (it) fillFromItem(it);
                }
                marcaEl.addEventListener('change', () => maybeFill(mapMarca, marcaEl));
                modeloEl.addEventListener('change', () => maybeFill(mapModelo, modeloEl));
                serieEl.addEventListener('change', () => maybeFill(mapSerie, serieEl));
                descEl.addEventListener('change', () => maybeFill(mapDesc, descEl));
            }

            function removeRow() {
                const rows = body.querySelectorAll('tr');
                if (rows.length > 0) rows[rows.length - 1].remove();
                renumber();
            }

            btnAdd.addEventListener('click', () => addRow({}));
            btnRemove.addEventListener('click', () => removeRow());

            // Inicial: 1 fila
            addRow({});

            // Tooltip reinits si aplica
            if (window.sigtaeInitTooltips) window.sigtaeInitTooltips();
        })();
        </script>
        <?php endif; ?>
    </div>
</div>

<?php if ($detalleRecepcion): ?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-eye me-1"></i> Detalle de recepción</span>
        <span class="text-muted small"><?= htmlspecialchars($detalleRecepcion['folio_recepcion'] ?? '') ?></span>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="small text-muted">Fecha recepción</div>
                <div class="fw-semibold"><?= htmlspecialchars($detalleRecepcion['fecha_recepcion'] ?? '') ?></div>
            </div>
            <div class="col-md-8">
                <div class="small text-muted">Motivo</div>
                <div class="fw-semibold"><?= htmlspecialchars($detalleRecepcion['motivo_recepcion'] ?? '') ?></div>
            </div>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Folio</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Descripción</th>
                        <th>Observaciones</th>
                        <th>Inspección</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($detalleRecepcion['equipos'] ?? []) as $eq): ?>
                        <tr>
                            <td class="text-muted"><?= (int)($eq['numero'] ?? 0) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($eq['folio'] ?? '') ?></td>
                            <td><?= htmlspecialchars($eq['marca'] ?? '') ?></td>
                            <td><?= htmlspecialchars($eq['modelo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($eq['serie'] ?? '') ?></td>
                            <td><?= htmlspecialchars($eq['descripcion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($eq['observaciones'] ?? '') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($eq['inspeccion_final'] ?? 'conforme') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

