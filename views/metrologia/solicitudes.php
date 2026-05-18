<?php
/** @var array $catalogos */
/** @var array $zonas */
/** @var array $areas */
/** @var array $solicitudes */
/** @var string $error */
/** @var string $success */
/** @var bool $isZonaUser */
/** @var string $userZonaId */
/** @var string $userZonaNombre */
/** @var string $userZonaPrefijo */
/** @var string $userAreaId */
/** @var string $nextFolioSugerido */
/** @var bool $canManage */
$canManage = !empty($metPerm) ? $metPerm->canManage($currentUser ?? null) : false;
$zonaNombreDisplay = $isZonaUser
    ? htmlspecialchars($userZonaNombre)
    : 'Todas las zonas';
$today = date('Y-m-d');
$anioActual = (int) date('Y');

$estadoLabels = [
    'por_entregar_a_laboratorio' => ['label' => 'Por entregar',    'bg' => 'bg-warning text-dark'],
    'solicitud_recibida'         => ['label' => 'Recibida',        'bg' => 'bg-info text-dark'],
    'solicitud_validada'         => ['label' => 'Validada',        'bg' => 'bg-primary'],
    'convertida_a_expediente'    => ['label' => 'En expediente',   'bg' => 'bg-success'],
    'cancelado'                  => ['label' => 'Cancelada',       'bg' => 'bg-secondary'],
];

$actions = '';
if (!$isZonaUser) {
    $actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/metrologia-dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
}
sigtae_page_header(
    $isZonaUser ? 'Solicitud de calibración' : 'Solicitudes recibidas',
    $isZonaUser
        ? 'Envía equipos de tu zona para calibración en el Laboratorio'
        : 'Registro y seguimiento de solicitudes de las zonas',
    $actions
);
?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <span><?= $error ?></span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <span><?= $success ?></span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($isZonaUser): ?>
<!-- ===== Vista zona: banner informativo ===== -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0" style="background: linear-gradient(135deg, #052e16 0%, #15803d 100%); color:#ecfdf5;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,.15);display:grid;place-items:center;flex-shrink:0;">
                    <i class="bi bi-geo-alt-fill fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($userZonaNombre) ?></div>
                    <div class="small opacity-75">Área: <?= htmlspecialchars($userAreaId !== '' ? $userAreaId : '—') ?> · Solo ves equipos de tu zona (<?= htmlspecialchars(strtoupper($userZonaPrefijo)) ?>)</div>
                </div>
                <div class="ms-auto text-end">
                    <div class="fw-bold fs-4"><?= count($solicitudes) ?></div>
                    <div class="small opacity-75">solicitudes <?= $anioActual ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== Formulario nueva solicitud ===== -->
<div class="card mb-4">
    <div class="card-header card-header-accent d-flex align-items-center gap-2">
        <i class="bi bi-plus-circle"></i>
        <span class="fw-semibold">Nueva solicitud</span>
        <span class="ms-auto badge bg-primary"><?= htmlspecialchars($nextFolioSugerido) ?></span>
    </div>
    <div class="card-body">
        <form method="post" id="formNuevaSolicitud" class="row g-3" novalidate>
            <input type="hidden" name="action" value="crear_solicitud">
            <input type="hidden" name="folio" id="hiddenFolio" value="">

            <!-- Búsqueda de equipo (autocomplete) -->
            <div class="col-12">
                <label class="form-label fw-semibold mb-1">
                    <i class="bi bi-search me-1 text-muted"></i>Buscar equipo por No. de serie *
                </label>
                <div class="input-group">
                    <input type="text" id="buscarSerie" class="form-control"
                           placeholder="Escribe el No. de serie (ej. DM21A-0001)..."
                           autocomplete="off" spellcheck="false">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                </div>
                <div id="sugerenciasList" class="list-group shadow-sm mt-1" style="display:none; position:absolute; z-index:1050; width:calc(100% - var(--bs-gutter-x));"></div>
                <div class="form-text">Solo se muestran equipos registrados en la bitácora de tu zona.</div>
            </div>

            <!-- Equipo seleccionado (readonly, se llena automáticamente) -->
            <div class="col-12">
                <div id="equipoSeleccionado" class="card border-primary bg-primary bg-opacity-10" style="display:none;">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                        <i class="bi bi-device-hdd fs-3 text-primary"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" id="eqDescripcion">—</div>
                            <div class="small text-muted">
                                Serie: <strong id="eqSerie">—</strong> &nbsp;|&nbsp;
                                Marca: <span id="eqMarca">—</span> &nbsp;|&nbsp;
                                Modelo: <span id="eqModelo">—</span>
                            </div>
                            <div class="small text-muted">
                                Zona: <span id="eqZona">—</span> &nbsp;|&nbsp;
                                Área: <span id="eqArea">—</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearEquipo" title="Quitar selección">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Campos ocultos que se rellenan al seleccionar equipo -->
            <input type="hidden" name="no_serie"           id="hiddenSerie">
            <input type="hidden" name="marca"              id="hiddenMarca">
            <input type="hidden" name="modelo"             id="hiddenModelo">
            <input type="hidden" name="descripcion_equipo" id="hiddenDesc">

            <?php if ($isZonaUser): ?>
            <!-- Usuario de zona: zona y área fijas -->
            <input type="hidden" name="zona_id" value="<?= htmlspecialchars($userZonaId) ?>">
            <input type="hidden" name="area_id" value="<?= htmlspecialchars($userAreaId) ?>">
            <?php else: ?>
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1">Zona *</label>
                <select class="form-select" name="zona_id" required>
                    <option value="">Seleccione zona…</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= htmlspecialchars($z['id']) ?>"><?= htmlspecialchars($z['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1">Área *</label>
                <select class="form-select" name="area_id" required>
                    <option value="">Seleccione área…</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= htmlspecialchars($a['id']) ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Observaciones -->
            <div class="col-12">
                <label class="form-label fw-semibold mb-1"><i class="bi bi-chat-text me-1 text-muted"></i>Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="2"
                          placeholder="Información adicional para el laboratorio (opcional)…"></textarea>
            </div>

            <!-- Folio (solo visual, siempre auto) -->
            <div class="col-12">
                <div class="d-flex align-items-center gap-2 p-2 rounded bg-light border">
                    <i class="bi bi-hash text-muted"></i>
                    <span class="small text-muted">Folio que se asignará automáticamente:</span>
                    <strong class="text-primary"><?= htmlspecialchars($nextFolioSugerido) ?></strong>
                    <i class="bi bi-lock-fill text-muted ms-auto" title="El folio es generado automáticamente"></i>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary px-4" id="btnEnviarSolicitud" disabled>
                    <i class="bi bi-send me-2"></i>Enviar solicitud al laboratorio
                </button>
                <button type="button" class="btn btn-outline-secondary ms-2" id="btnReset">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Listado de solicitudes ===== -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-inbox"></i>
            <span class="fw-semibold"><?= $isZonaUser ? 'Mis solicitudes' : 'Bandeja de solicitudes' ?></span>
            <span class="badge bg-secondary"><?= count($solicitudes) ?></span>
        </div>
        <?php if (!$isZonaUser): ?>
        <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="zona_id" class="form-select form-select-sm" style="width:auto">
                <option value="">Todas las zonas</option>
                <?php foreach ($zonas as $z): ?>
                    <option value="<?= htmlspecialchars($z['id']) ?>"
                        <?= ($zonaFiltro ?? '') === $z['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="estado" class="form-select form-select-sm" style="width:auto">
                <option value="">Todos los estados</option>
                <?php foreach ($estadoLabels as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($estadoFiltro ?? '') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($solicitudes)): ?>
            <?php sigtae_empty_state('Sin solicitudes para este período.', 'bi-inbox'); ?>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaSolicitudes">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Equipo</th>
                        <th>Serie</th>
                        <?php if (!$isZonaUser): ?><th>Zona</th><?php endif; ?>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitudes as $s):
                    $estadoKey = $s['estado'] ?? '';
                    $estadoInfo = $estadoLabels[$estadoKey] ?? ['label' => ucfirst(str_replace('_', ' ', $estadoKey)), 'bg' => 'bg-light text-dark border'];
                ?>
                    <tr>
                        <td class="fw-semibold text-nowrap"><?= htmlspecialchars($s['folio'] ?? '') ?></td>
                        <td>
                            <div class="fw-semibold small"><?= htmlspecialchars(mb_substr($s['descripcion'] ?? '—', 0, 45)) ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($s['marca'] ?? '') ?><?= !empty($s['modelo']) ? ' · ' . htmlspecialchars($s['modelo']) : '' ?></div>
                        </td>
                        <td class="small text-muted text-nowrap"><?= htmlspecialchars($s['no_serie'] ?? '') ?></td>
                        <?php if (!$isZonaUser): ?>
                        <td class="small"><?= htmlspecialchars($s['zona_id'] ?? '') ?></td>
                        <?php endif; ?>
                        <td class="small text-muted text-nowrap"><?= htmlspecialchars($s['fecha_solicitud'] ?? '') ?></td>
                        <td><span class="badge <?= $estadoInfo['bg'] ?>"><?= $estadoInfo['label'] ?></span></td>
                        <td class="text-end text-nowrap">
                            <?php if ($canManage && empty($s['expediente_id'])): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="crear_expediente_desde_solicitud">
                                <input type="hidden" name="solicitud_id" value="<?= htmlspecialchars($s['id'] ?? '') ?>">
                                <input type="hidden" name="validada" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Crear expediente">
                                    <i class="bi bi-folder-plus"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (!empty($s['expediente_id'])): ?>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= htmlspecialchars($basePath ?? '') ?>/metrologia-expediente.php?id=<?= urlencode($s['expediente_id']) ?>"
                               title="Ver expediente">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        $(function() {
            $('#tablaSolicitudes').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[<?= $isZonaUser ? '4' : '5' ?>,'desc']],
                pageLength: 10,
                columnDefs: [{ orderable: false, targets: -1 }]
            });
        });
        </script>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    'use strict';

    const BASE    = window.SIGTAE_BASE_PATH || '';
    const ZONA_PREFIJO = <?= json_encode($userZonaPrefijo) ?>;
    const IS_ZONA = <?= json_encode($isZonaUser) ?>;

    const buscarInput    = document.getElementById('buscarSerie');
    const sugerenciasList = document.getElementById('sugerenciasList');
    const equipoCard     = document.getElementById('equipoSeleccionado');
    const btnClear       = document.getElementById('btnClearEquipo');
    const btnEnviar      = document.getElementById('btnEnviarSolicitud');
    const btnReset       = document.getElementById('btnReset');
    const form           = document.getElementById('formNuevaSolicitud');

    let searchTimer = null;
    let selectedEquipo = null;

    function setEquipo(eq) {
        selectedEquipo = eq;
        document.getElementById('hiddenSerie').value  = eq.no_serie   || '';
        document.getElementById('hiddenMarca').value  = eq.marca      || '';
        document.getElementById('hiddenModelo').value = eq.modelo     || '';
        document.getElementById('hiddenDesc').value   = eq.descripcion || '';

        document.getElementById('eqDescripcion').textContent = eq.descripcion || '(sin descripción)';
        document.getElementById('eqSerie').textContent       = eq.no_serie    || '—';
        document.getElementById('eqMarca').textContent       = eq.marca       || '—';
        document.getElementById('eqModelo').textContent      = eq.modelo      || '—';
        document.getElementById('eqZona').textContent        = eq.zona        || '—';
        document.getElementById('eqArea').textContent        = eq.area        || '—';

        equipoCard.style.display = '';
        sugerenciasList.style.display = 'none';
        buscarInput.value = eq.no_serie || '';
        buscarInput.readOnly = true;
        btnEnviar.disabled = false;
    }

    function clearEquipo() {
        selectedEquipo = null;
        document.getElementById('hiddenSerie').value  = '';
        document.getElementById('hiddenMarca').value  = '';
        document.getElementById('hiddenModelo').value = '';
        document.getElementById('hiddenDesc').value   = '';
        equipoCard.style.display = 'none';
        buscarInput.value    = '';
        buscarInput.readOnly = false;
        buscarInput.focus();
        btnEnviar.disabled = true;
    }

    function renderSugerencias(equipos) {
        sugerenciasList.innerHTML = '';
        if (!equipos.length) {
            sugerenciasList.innerHTML = '<div class="list-group-item text-muted small py-2">Sin resultados en bitácora de tu zona.</div>';
            sugerenciasList.style.display = '';
            return;
        }
        equipos.forEach(function (eq) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action py-2';
            item.innerHTML =
                '<div class="d-flex align-items-start gap-2">' +
                '<i class="bi bi-device-hdd text-muted mt-1"></i>' +
                '<div>' +
                '<div class="fw-semibold small">' + escHtml(eq.no_serie) + '</div>' +
                '<div class="text-muted" style="font-size:.75rem;">' + escHtml(eq.descripcion || '(sin descripción)') + '</div>' +
                '<div class="text-muted" style="font-size:.72rem;">' +
                escHtml(eq.marca || '') + (eq.modelo ? ' · ' + escHtml(eq.modelo) : '') +
                ' &nbsp;|&nbsp; ' + escHtml(eq.zona || '') +
                '</div></div></div>';
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                setEquipo(eq);
            });
            sugerenciasList.appendChild(item);
        });
        sugerenciasList.style.display = '';
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str || '');
        return d.innerHTML;
    }

    buscarInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(searchTimer);
        if (q.length < 2) {
            sugerenciasList.style.display = 'none';
            return;
        }
        searchTimer = setTimeout(function () {
            const url = BASE + '/api/metrologia-solicitud-equipos.php?q=' +
                encodeURIComponent(q) + '&zona_prefijo=' + encodeURIComponent(ZONA_PREFIJO) + '&limit=15';
            fetch(url, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) { renderSugerencias(data.equipos || []); })
                .catch(function () {
                    sugerenciasList.innerHTML = '<div class="list-group-item text-danger small">Error al buscar equipos.</div>';
                    sugerenciasList.style.display = '';
                });
        }, 250);
    });

    buscarInput.addEventListener('blur', function () {
        setTimeout(function () { sugerenciasList.style.display = 'none'; }, 150);
    });
    buscarInput.addEventListener('focus', function () {
        if (this.value.trim().length >= 2 && sugerenciasList.children.length) {
            sugerenciasList.style.display = '';
        }
    });

    if (btnClear) btnClear.addEventListener('click', clearEquipo);
    if (btnReset) btnReset.addEventListener('click', function () {
        clearEquipo();
        form.reset();
        document.getElementById('hiddenFolio').value = '';
    });

    // Validación: serie es obligatoria
    form.addEventListener('submit', function (e) {
        if (!document.getElementById('hiddenSerie').value) {
            e.preventDefault();
            buscarInput.focus();
            buscarInput.classList.add('is-invalid');
            setTimeout(function () { buscarInput.classList.remove('is-invalid'); }, 2500);
        }
    });
})();
</script>
