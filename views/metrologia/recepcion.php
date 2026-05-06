<?php
$catalogos = $catalogos ?? [];
$detalleRecepcion = $detalleRecepcion ?? null;
$error = $error ?? '';
$warning = $warning ?? '';
$success = $success ?? '';
$canManage = $canManage ?? false;

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
                                        <th style="width:210px;">Inspección final</th>
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
                const gid = 'ins_' + Math.random().toString(16).slice(2);
                tr.innerHTML = `
                    <td class="fw-semibold text-muted" data-col="numero"></td>
                    <td><input name="equipo_marca[]" class="form-control form-control-sm" required value="${esc(prefill.marca)}"></td>
                    <td><input name="equipo_modelo[]" class="form-control form-control-sm" required value="${esc(prefill.modelo)}"></td>
                    <td><input name="equipo_serie[]" class="form-control form-control-sm" required value="${esc(prefill.serie)}"></td>
                    <td><input name="equipo_descripcion[]" class="form-control form-control-sm" required value="${esc(prefill.descripcion)}"></td>
                    <td><input name="equipo_observaciones[]" class="form-control form-control-sm" value="${esc(prefill.observaciones)}"></td>
                    <td class="text-muted small">Auto</td>
                    <td>
                        <div class="d-flex flex-column gap-1 small">
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="${gid}" value="conforme" checked>
                                Conforme
                            </label>
                            <label class="d-flex align-items-center gap-1">
                                <input type="radio" name="${gid}" value="no_conforme">
                                No conforme
                            </label>
                        </div>
                        <input type="hidden" name="equipo_inspeccion[]" value="conforme" data-ins>
                    </td>
                `;
                // Para el backend, mantenemos un campo plano equipo_inspeccion[]; sincronizamos al cambiar radios.
                const radios = tr.querySelectorAll('input[type="radio"]');
                const hid = tr.querySelector('input[data-ins]');
                radios.forEach(r => r.addEventListener('change', () => { if (r.checked) hid.value = r.value; }));
                body.appendChild(tr);
                renumber();
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

