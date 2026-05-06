<?php
$error = $error ?? '';
$success = $success ?? '';
$programas = $programas ?? [];
$programaActual = $programaActual ?? null;
$actividades = $actividades ?? [];
$columnas = $columnas ?? [];
$avanceMap = $avanceMap ?? [];
$resumenBarras = $resumenBarras ?? [];
$resumenActividades = $resumenActividades ?? [];
$evidMap = $evidMap ?? [];
$avanceGlobal = $avanceGlobal ?? 0;
$totalAtrasadas = $totalAtrasadas ?? 0;
$totalCumplidas = $totalCumplidas ?? 0;
$totalEvidencias = $totalEvidencias ?? 0;
$canManage = $canManage ?? false;
$canEditProgramaActual = $canEditProgramaActual ?? false;
$feriados = $feriados ?? [];
$today = $today ?? date('Y-m-d');
$activityRanges = $activityRanges ?? [];
$users = $users ?? [];

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Programa de Trabajo', '', $actions);

$__meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
$__diasSem = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
$__fmtFechaCol = function ($ymd) use ($__meses) {
    $ymd = (string)$ymd;
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return $ymd;
    $d = (int)$dt->format('j');
    $m = (int)$dt->format('n');
    $mes = isset($__meses[$m]) ? $__meses[$m] : $dt->format('M');
    return $d . ' ' . $mes;
};
$__fmtFechaLarga = function ($ymd) use ($__meses, $__diasSem) {
    $ymd = (string)$ymd;
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$dt) return $ymd;
    $dia = (int)$dt->format('N');
    $d = (int)$dt->format('j');
    $m = (int)$dt->format('n');
    $mes = isset($__meses[$m]) ? $__meses[$m] : $dt->format('M');
    $diaTxt = isset($__diasSem[$dia]) ? $__diasSem[$dia] : '';
    return $diaTxt . ' ' . $d . ' ' . $mes;
};
$ptHoyIdx = isset($hoyIdx) ? (int)$hoyIdx : -1;
$ptWeekGroups = $weekGroups ?? [];
$ptPerResponsable = $perResponsableList ?? [];
?>

<style>
/* Programa de Trabajo - estilos del módulo */
.pt-kpi-row { margin-bottom: .75rem; }

.pt-matrix-wrapper { position: relative; }
.pt-matrix-wrapper table { min-width: 1400px; border-collapse: separate; border-spacing: 0; }
.pt-matrix-wrapper td { transition: background-color .12s ease; }
.pt-matrix-wrapper td.pt-blank { background: #fff !important; }
.pt-matrix-wrapper td.pt-p-active { background: rgba(13,110,253,0.22) !important; box-shadow: inset 0 0 0 1px rgba(13,110,253,.18); }  /* azul P */
.pt-matrix-wrapper td.pt-e-in-time { background: rgba(253,126,20,0.24) !important; box-shadow: inset 0 0 0 1px rgba(253,126,20,.18); } /* naranja E en tiempo */
.pt-matrix-wrapper td.pt-e-late { background: rgba(220,53,69,0.24) !important; box-shadow: inset 0 0 0 1px rgba(220,53,69,.18); }     /* rojo E vencido */
.pt-matrix-wrapper td.pt-e-done { background: rgba(25,135,84,0.24) !important; box-shadow: inset 0 0 0 1px rgba(25,135,84,.18); }     /* verde E presentado */
.pt-matrix-wrapper td:hover { background-color: rgba(74,159,184,.07); }

/* Evidencias (solo entregables): ocultar hasta hover para que no estorbe */
.pt-ev-btn { opacity: .25; }
td:hover .pt-ev-btn, .pt-ev-btn:focus { opacity: 1; }

/* Sticky header */
.pt-matrix-wrapper thead tr.pt-week-row th,
.pt-matrix-wrapper thead tr.pt-day-row th { position: sticky; top: 0; z-index: 4; background: #f8f9fa; }
.pt-matrix-wrapper thead tr.pt-week-row th { top: 0; z-index: 5; }
.pt-matrix-wrapper thead tr.pt-day-row th { top: 28px; z-index: 4; }
.pt-matrix-wrapper thead tr.pt-week-row th {
    height: 28px;
    line-height: 28px;
    padding-top: 0;
    padding-bottom: 0;
}
.pt-matrix-wrapper thead tr.pt-day-row th { height: 38px; }

/* Sticky cols (No, Actividad) */
.pt-matrix-wrapper td.pt-sticky-1, .pt-matrix-wrapper th.pt-sticky-1 {
    position: sticky; left: 0; background: #ffffff; z-index: 3;
    box-shadow: 1px 0 0 #e9ecef;
}
.pt-matrix-wrapper td.pt-sticky-2, .pt-matrix-wrapper th.pt-sticky-2 {
    position: sticky; left: 40px; background: #ffffff; z-index: 3;
    box-shadow: 1px 0 0 #e9ecef;
}
.pt-matrix-wrapper thead tr th.pt-sticky-1,
.pt-matrix-wrapper thead tr th.pt-sticky-2 { z-index: 6; background: #f8f9fa; }

/* Hoy */
.pt-col-today { background: rgba(255, 193, 7, 0.10) !important; }
.pt-col-today-header { background: #fff3cd !important; color: #664d03; font-weight: 600; }

/* Borde lateral por estado (en sticky-1 de ambas filas P y E) */
tr.pt-state-cumplido > td.pt-sticky-1 { box-shadow: inset 4px 0 0 #198754, 1px 0 0 #e9ecef; }
tr.pt-state-atrasado > td.pt-sticky-1 { box-shadow: inset 4px 0 0 #dc3545, 1px 0 0 #e9ecef; }
tr.pt-state-en_proceso > td.pt-sticky-1 { box-shadow: inset 4px 0 0 #0d6efd, 1px 0 0 #e9ecef; }
tr.pt-state-excedido > td.pt-sticky-1 { box-shadow: inset 4px 0 0 #198754, 1px 0 0 #e9ecef; }
tr.pt-state-sin_iniciar > td.pt-sticky-1 { box-shadow: inset 4px 0 0 #adb5bd, 1px 0 0 #e9ecef; }
/* Pista visual extra del estado en la columna de Actividad */
tr.pt-state-cumplido td.pt-sticky-2 { background: linear-gradient(to right, rgba(25,135,84,.05), #fff 70%); }
tr.pt-state-atrasado td.pt-sticky-2 { background: linear-gradient(to right, rgba(220,53,69,.05), #fff 70%); }
tr.pt-state-en_proceso td.pt-sticky-2 { background: linear-gradient(to right, rgba(13,110,253,.05), #fff 70%); }
tr.pt-state-excedido td.pt-sticky-2 { background: linear-gradient(to right, rgba(25,135,84,.05), #fff 70%); }

/* Pills de medición */
.pt-pills .btn-check + .btn { font-size: .75rem; padding: .15rem .55rem; }
.pt-pills .btn-check:checked + .btn { box-shadow: 0 0 0 .15rem rgba(13,110,253,.20); }

/* Cards de programas */
.pt-prog-card { border: 1px solid #e9ecef; border-radius: .5rem; transition: box-shadow .15s ease, transform .15s ease; }
.pt-prog-card:hover { box-shadow: 0 .35rem .9rem rgba(0,0,0,.06); transform: translateY(-1px); }
.pt-prog-card.active { border-color: #4a9fb8; box-shadow: 0 0 0 .15rem rgba(74,159,184,.15); }

/* Mini-gantt timeline */
.pt-gantt-row { display: flex; align-items: center; gap: .5rem; padding: .35rem 0; border-bottom: 1px dashed #e9ecef; }
.pt-gantt-row:last-child { border-bottom: 0; }
.pt-gantt-track { position: relative; flex: 1; height: 14px; background: #f1f3f5; border-radius: 7px; overflow: hidden; }
.pt-gantt-bar { position: absolute; top: 0; bottom: 0; background: linear-gradient(90deg, #4a9fb8, #1a4d6d); border-radius: 7px; }
.pt-gantt-progress { position: absolute; top: 0; bottom: 0; background: rgba(25,135,84,.65); border-radius: 7px; }
.pt-gantt-today { position: absolute; top: -2px; bottom: -2px; width: 2px; background: #ffc107; }

/* Validación viva */
.is-pt-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 .12rem rgba(220,53,69,.18) !important; }
</style>

<script>
// Forzar dígitos (sin espacios ni caracteres) en campos de cantidad
(function () {
  function onlyDigits(v) { return String(v || '').replace(/[^\d]/g, ''); }
  document.addEventListener('input', function (e) {
    var t = e && e.target ? e.target : null;
    if (!t || !t.classList || !t.classList.contains('pt-digit-only')) return;
    var cleaned = onlyDigits(t.value);
    if (t.value !== cleaned) t.value = cleaned;
  }, true);
})();
</script>

<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= empty($programaActual) ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabNuevo" type="button" role="tab">
      <i class="bi bi-plus-square me-1"></i> Nuevo programa
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= !empty($programaActual) ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabProgramas" type="button" role="tab">
      <i class="bi bi-list-check me-1"></i> Programas
    </button>
  </li>
  <?php if ($programaActual): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabGantt" type="button" role="tab">
      <i class="bi bi-bar-chart-steps me-1"></i> Vista Gantt
    </button>
  </li>
  <?php endif; ?>
</ul>

<div class="tab-content">
  <div class="tab-pane fade <?= empty($programaActual) ? 'show active' : '' ?>" id="tabNuevo" role="tabpanel">
    <div class="card">
      <div class="card-header card-header-accent"><i class="bi bi-plus-square me-1"></i> Nuevo programa</div>
      <div class="card-body">
        <?php if (!$canManage): ?>
          <div class="alert alert-info mb-0 small">Solo usuarios con permisos de gestión pueden crear/editar programas.</div>
        <?php else: ?>
        <form method="post" id="frmNuevoPrograma" class="row g-2">
          <input type="hidden" name="action" value="crear_programa">
          <div class="col-md-8">
            <label class="form-label small fw-semibold mb-1">Nombre del programa *</label>
            <input type="text" class="form-control form-control-sm" name="nombre_programa" required placeholder="Ej. Pruebas">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Frecuencia *</label>
            <select name="frecuencia" class="form-select form-select-sm" required id="selFrecuenciaPrograma">
              <option value="diario">Diario</option>
              <option value="semanal" selected>Semanal</option>
              <option value="catorcenal">Catorcenal</option>
              <option value="mensual">Mensual</option>
            </select>
          </div>

          <div class="col-12 mt-2">
            <div class="d-flex align-items-center justify-content-between">
              <label class="form-label small fw-semibold mb-1">Actividades</label>
              <span class="text-muted small">Agrega cuantas actividades necesites; cada una con su rango.</span>
            </div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Actividad</th>
                    <th style="width: 90px;">Cantidad</th>
                    <th style="width: 240px;">Medición</th>
                    <th style="width: 135px;">Inicio</th>
                    <th style="width: 135px;">Fin</th>
                    <th>Tipo</th>
                    <th>Responsable</th>
                    <th style="width: 40px;"></th>
                  </tr>
                </thead>
                <tbody id="tbActividades"></tbody>
              </table>
            </div>
            <div class="mt-2">
              <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnAddActividad">
                <i class="bi bi-plus-lg"></i> Agregar actividad
              </button>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save me-1"></i> Crear programa</button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="tab-pane fade <?= !empty($programaActual) ? 'show active' : '' ?>" id="tabProgramas" role="tabpanel">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-list-check me-1"></i> Programas</span>
        <span class="text-muted small" id="ptProgCount"><?= count($programas) ?> registros</span>
      </div>
      <div class="card-body">
        <div class="row g-2 mb-2 align-items-end" id="ptProgFiltros">
          <div class="col-md-5">
            <label class="form-label small fw-semibold mb-1">Buscar</label>
            <input type="text" id="ptFiltroBuscar" class="form-control form-control-sm" placeholder="Nombre del programa...">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Frecuencia</label>
            <select id="ptFiltroFrecuencia" class="form-select form-select-sm">
              <option value="">Todas</option>
              <option value="diario">Diario</option>
              <option value="semanal">Semanal</option>
              <option value="catorcenal">Catorcenal</option>
              <option value="mensual">Mensual</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select id="ptFiltroEstado" class="form-select form-select-sm">
              <option value="">Todos</option>
              <option value="activo">Activo</option>
              <option value="cerrado">Cerrado</option>
            </select>
          </div>
          <div class="col-md-1 text-end">
            <button id="ptFiltroLimpiar" type="button" class="btn btn-sm btn-outline-secondary w-100" title="Limpiar"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>

        <div class="row g-2" id="ptProgGrid">
          <?php foreach ($programas as $p): $isAct = $programaActual && ($programaActual['id'] ?? '') === ($p['id'] ?? ''); ?>
            <div class="col-md-6 col-lg-4 pt-prog-item"
                 data-nombre="<?= htmlspecialchars(strtolower((string)($p['nombre'] ?? ''))) ?>"
                 data-frecuencia="<?= htmlspecialchars((string)($p['frecuencia'] ?? '')) ?>"
                 data-estado="<?= htmlspecialchars((string)($p['estado'] ?? 'activo')) ?>">
              <a href="<?= htmlspecialchars(($basePath ?? '') . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode((string)($p['id'] ?? ''))) ?>"
                 class="d-block text-decoration-none text-reset">
                <div class="pt-prog-card p-3 h-100 <?= $isAct ? 'active' : '' ?>">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="fw-semibold"><?= htmlspecialchars((string)($p['nombre'] ?? '')) ?></div>
                    <span class="badge bg-light text-dark text-capitalize"><?= htmlspecialchars((string)($p['frecuencia'] ?? '')) ?></span>
                  </div>
                  <div class="small text-muted mt-1">
                    <i class="bi bi-calendar-range me-1"></i>
                    <?= htmlspecialchars((string)($p['fecha_inicio'] ?? '')) ?> a <?= htmlspecialchars((string)($p['fecha_fin'] ?? '')) ?>
                  </div>
                  <div class="small mt-1">
                    <i class="bi bi-circle-fill me-1" style="color: <?= ($p['estado'] ?? 'activo') === 'cerrado' ? '#6c757d' : '#198754' ?>; font-size: .55rem;"></i>
                    <span class="text-capitalize"><?= htmlspecialchars((string)($p['estado'] ?? 'activo')) ?></span>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
          <?php if (count($programas) === 0): ?>
            <div class="col-12"><div class="alert alert-light border small mb-0">No hay programas creados todavía.</div></div>
          <?php endif; ?>
        </div>

        <?php if ($programaActual): ?>
          <hr class="my-3">
          <div class="row g-2 pt-kpi-row">
              <div class="col-md-3"><?php sigtae_kpi_card(['label' => 'Avance global', 'value' => $avanceGlobal . '%', 'icon' => 'bi-graph-up', 'color' => '#1a4d6d']); ?></div>
              <div class="col-md-3"><?php sigtae_kpi_card(['label' => 'Atrasadas', 'value' => (int)$totalAtrasadas, 'icon' => 'bi-exclamation-triangle', 'color' => '#b91c1c']); ?></div>
              <div class="col-md-3"><?php sigtae_kpi_card(['label' => 'Cumplidas', 'value' => (int)$totalCumplidas, 'icon' => 'bi-check2-circle', 'color' => '#047857']); ?></div>
              <div class="col-md-3"><?php sigtae_kpi_card(['label' => 'Evidencias', 'value' => (int)$totalEvidencias, 'icon' => 'bi-paperclip', 'color' => '#1d4ed8']); ?></div>
          </div>
        <?php endif; ?>

        <?php if ($programaActual && !empty($actividades) && !empty($columnas)): ?>
          <div class="card mt-2">
              <div class="card-header card-header-accent d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <span><i class="bi bi-table me-1"></i> Matriz Programado (P) vs Ejecutado (E)</span>
                  <div class="d-flex align-items-center gap-2">
                      <span class="small text-muted me-2"><?= htmlspecialchars($programaActual['nombre'] ?? '') ?></span>
                      <?php if ($canEditProgramaActual): ?>
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaActividad">
                          <i class="bi bi-plus-lg"></i> Agregar actividad
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarPrograma">
                          <i class="bi bi-pencil-square"></i> Editar programa
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPrograma">
                          <i class="bi bi-trash"></i> Eliminar programa
                      </button>
                      <?php endif; ?>
                  </div>
              </div>
              <div class="card-body p-0">
                  <form method="post">
                      <input type="hidden" name="action" value="guardar_avances">
                      <input type="hidden" name="programa_id" value="<?= htmlspecialchars($programaActual['id'] ?? '') ?>">
                      <div class="table-responsive pt-matrix-wrapper" style="max-height: 65vh; overflow: auto;">
                          <table class="table table-sm table-bordered align-middle mb-0">
                              <thead class="table-light">
                                  <?php if (!empty($ptWeekGroups)): ?>
                                  <tr class="pt-week-row">
                                      <th class="pt-sticky-1" rowspan="2" style="width:40px;">No.</th>
                                      <th class="pt-sticky-2" rowspan="2" style="min-width:220px;">Actividad</th>
                                      <th rowspan="2" style="width:90px;">Cantidad</th>
                                      <th rowspan="2" style="width:140px;">Medición</th>
                                      <th rowspan="2" style="min-width:130px;">Tipo</th>
                                      <th rowspan="2" style="min-width:180px;">Responsable</th>
                                      <th rowspan="2" style="width:30px;">L</th>
                                      <?php foreach ($ptWeekGroups as $g): ?>
                                          <th colspan="<?= (int)$g['count'] ?>" class="text-center small text-uppercase" style="background:#eef3f6;">
                                              Sem. <?= (int)$g['week'] ?>
                                          </th>
                                      <?php endforeach; ?>
                                      <th rowspan="2" style="width:85px;">Avance</th>
                                  </tr>
                                  <tr class="pt-day-row">
                                      <?php foreach ($columnas as $i => $f): $isHoy = ($i === $ptHoyIdx); ?>
                                          <th class="text-center small <?= $isHoy ? 'pt-col-today-header' : '' ?>" title="<?= htmlspecialchars($__fmtFechaLarga($f)) ?>">
                                              <?= htmlspecialchars($__fmtFechaCol($f)) ?>
                                          </th>
                                      <?php endforeach; ?>
                                  </tr>
                                  <?php else: ?>
                                  <tr class="pt-day-row">
                                      <th class="pt-sticky-1" style="width:40px;">No.</th>
                                      <th class="pt-sticky-2" style="min-width:220px;">Actividad</th>
                                      <th style="width:90px;">Cantidad</th>
                                      <th style="width:140px;">Medición</th>
                                      <th style="min-width:130px;">Tipo</th>
                                      <th style="min-width:180px;">Responsable</th>
                                      <th style="width:30px;">L</th>
                                      <?php foreach ($columnas as $i => $f): $isHoy = ($i === $ptHoyIdx); ?>
                                          <th class="text-center small <?= $isHoy ? 'pt-col-today-header' : '' ?>" title="<?= htmlspecialchars($__fmtFechaLarga($f)) ?>">
                                              <?= htmlspecialchars($__fmtFechaCol($f)) ?>
                                          </th>
                                      <?php endforeach; ?>
                                      <th style="width:85px;">Avance</th>
                                  </tr>
                                  <?php endif; ?>
                              </thead>
                              <tbody>
                                  <?php foreach ($actividades as $a): ?>
                                      <?php
                                      $aid = (string)($a['id'] ?? '');
                                      $r = $activityRanges[$aid] ?? null;
                                      $rIni = is_array($r) ? (string)($r['inicio'] ?? '') : '';
                                      $rFin = is_array($r) ? (string)($r['fin'] ?? '') : '';
                                      $medAct = (string)($a['tipo_medicion'] ?? 'cantidad');
                                      $ra = $resumenActividades[$aid] ?? ['sumP'=>0,'sumE'=>0,'diff'=>0,'avance'=>0,'estado'=>'sin_iniciar'];
                                      $avancePct = (float)($ra['avance'] ?? 0);
                                      $estado = (string)($ra['estado'] ?? 'sin_iniciar');
                                      $badge = $estado === 'cumplido' ? 'bg-success'
                                          : ($estado === 'atrasado' ? 'bg-danger'
                                          : ($estado === 'excedido' ? 'bg-success'
                                          : ($estado === 'en_proceso' ? 'bg-primary' : 'bg-secondary')));
                                      $stateClass = 'pt-state-' . $estado;
                                      $terminada = !empty($a['terminada']);
                                      ?>
                                      <tr class="<?= $stateClass ?>">
                                          <td rowspan="2" class="text-center fw-semibold pt-sticky-1"><?= (int)($a['numero'] ?? 0) ?></td>
                                          <td rowspan="2" class="pt-sticky-2">
                                              <div class="fw-semibold small"><?= htmlspecialchars($a['actividad'] ?? '') ?></div>
                                              <div class="small text-muted"><i class="bi bi-calendar-range me-1"></i><?= htmlspecialchars($__fmtFechaCol($rIni)) ?> &rarr; <?= htmlspecialchars($__fmtFechaCol($rFin)) ?></div>
                                              <?php if ($canEditProgramaActual): ?>
                                              <div class="d-flex flex-wrap gap-1 mt-1 pt-act-acciones">
                                                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1 btn-edit-actividad"
                                                          title="Editar actividad"
                                                          data-bs-toggle="modal" data-bs-target="#modalEditarActividad"
                                                          data-act-id="<?= htmlspecialchars($aid) ?>"
                                                          data-act-nombre="<?= htmlspecialchars((string)($a['actividad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-act-med="<?= htmlspecialchars($medAct, ENT_QUOTES, 'UTF-8') ?>"
                                                          data-act-cant="<?= htmlspecialchars((string)($a['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-act-ini="<?= htmlspecialchars((string)($rIni), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-act-fin="<?= htmlspecialchars((string)($rFin), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-act-tipo="<?= htmlspecialchars((string)($a['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-act-resp="<?= htmlspecialchars((string)($a['responsable_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                      <i class="bi bi-pencil"></i>
                                                  </button>
                                                  <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" title="Eliminar actividad"
                                                          onclick="ptEliminarActividad(this.dataset.actId)" data-act-id="<?= htmlspecialchars($aid) ?>">
                                                      <i class="bi bi-trash"></i>
                                                  </button>
                                              </div>
                                              <?php endif; ?>
                                          </td>
                                          <td rowspan="2" class="text-end"><?= htmlspecialchars((string)($a['cantidad'] ?? 0)) ?></td>
                                          <td rowspan="2" class="small">
                                              <span class="badge <?= $badge ?>"><?= htmlspecialchars(str_replace('_',' ', $estado)) ?></span><br>
                                              <span class="text-muted text-capitalize">
                                                <?= htmlspecialchars($medAct) ?>
                                              </span>
                                          </td>
                                          <td rowspan="2"><?= htmlspecialchars($a['tipo'] ?? '') ?></td>
                                          <td rowspan="2"><?= htmlspecialchars($a['responsable'] ?? '') ?></td>
                                          <td class="text-center fw-semibold small">P</td>
                                          <?php foreach ($columnas as $i => $f): ?>
                                              <?php
                                                $inRange = ($rIni === '' || $rFin === '') ? true : ($f >= $rIni && $f <= $rFin);
                                                $isHoy = ($i === $ptHoyIdx);
                                                $cellClass = $isHoy ? 'pt-col-today' : '';
                                              ?>
                                              <?php if (!$inRange): ?>
                                                <td class="<?= trim($cellClass . ' pt-blank') ?>" title="<?= htmlspecialchars($__fmtFechaLarga($f)) ?>"></td>
                                              <?php else: ?>
                                                <td class="<?= trim($cellClass . ' pt-p-active') ?>" title="<?= htmlspecialchars($__fmtFechaLarga($f)) ?>">
                                                    <?php if ($medAct === 'cantidad'): ?>
                                                        <div class="fw-semibold text-center" style="min-height: 30px; line-height: 30px;">
                                                          <?= htmlspecialchars((string)($avanceMap[$aid]['P'][$f] ?? '')) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="small text-muted text-center" style="min-height: 30px; line-height: 30px;"></div>
                                                    <?php endif; ?>
                                                </td>
                                              <?php endif; ?>
                                          <?php endforeach; ?>
                                          <td rowspan="2" class="text-center fw-semibold">
                                              <?= htmlspecialchars((string)$avancePct) ?>%<br>
                                              <?php if ($medAct === 'cantidad'): ?>
                                                <span class="small text-muted"><?= htmlspecialchars((string)round((float)($ra['sumE'] ?? 0), 1)) ?> / <?= htmlspecialchars((string)round((float)($a['cantidad'] ?? 0), 1)) ?></span>
                                              <?php else: ?>
                                                <span class="small text-muted"><?= (int)($ra['diasHechos'] ?? 0) ?> / <?= (int)($ra['diasActivos'] ?? 0) ?> días</span>
                                              <?php endif; ?>
                                          </td>
                                      </tr>
                                      <tr class="<?= $stateClass ?>">
                                          <td class="text-center fw-semibold small">E</td>
                                          <?php foreach ($columnas as $i => $f): ?>
                                              <?php
                                                $inRange = ($rIni === '' || $rFin === '') ? true : ($f >= $rIni && $f <= $rFin);
                                                $isHoy = ($i === $ptHoyIdx);
                                                $cellClass = $isHoy ? 'pt-col-today' : '';
                                                $isPast = ($today && $f < $today);
                                              ?>
                                              <?php if (!$inRange): ?>
                                                <td class="<?= trim($cellClass . ' pt-blank') ?>" title="<?= htmlspecialchars($__fmtFechaLarga($f)) ?>"></td>
                                              <?php else: ?>
                                                <?php
                                                  $done = false;
                                                  if ($medAct === 'cantidad') {
                                                    $done = ((float)($avanceMap[$aid]['E'][$f] ?? 0)) > 0;
                                                  } else {
                                                    $chk = ((float)($avanceMap[$aid]['E'][$f] ?? 0)) > 0;
                                                    if ($medAct === 'entregable') {
                                                      $hasEv = !empty($evidMap[$aid][$f] ?? []);
                                                      $done = ($chk || $hasEv);
                                                    } else {
                                                      $done = $chk;
                                                    }
                                                  }
                                                  $eClass = $done ? 'pt-e-done' : ($isPast ? 'pt-e-late' : 'pt-e-in-time');
                                                ?>
                                                <td class="<?= trim($cellClass . ' ' . $eClass) ?>" title="<?= htmlspecialchars($__fmtFechaLarga($f)) ?>">
                                                    <div class="d-flex align-items-center gap-1 justify-content-center">
                                                        <?php if ($medAct === 'cantidad'): ?>
                                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                                   class="form-control form-control-sm text-end pt-digit-only"
                                                                   name="ejecutado[<?= htmlspecialchars($aid) ?>][<?= htmlspecialchars($f) ?>]"
                                                                   value="<?= htmlspecialchars((string)($avanceMap[$aid]['E'][$f] ?? '')) ?>">
                                                        <?php else: ?>
                                                            <?php $checked = ((float)($avanceMap[$aid]['E'][$f] ?? 0)) > 0; ?>
                                                            <div class="form-check m-0" title="Marcar avance del día">
                                                                <input type="hidden"
                                                                       name="ejecutado_chk[<?= htmlspecialchars($aid) ?>][<?= htmlspecialchars($f) ?>]"
                                                                       value="0">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="ejecutado_chk[<?= htmlspecialchars($aid) ?>][<?= htmlspecialchars($f) ?>]"
                                                                       value="1" <?= $checked ? 'checked' : '' ?>>
                                                            </div>
                                                            <?php if ($medAct === 'entregable'): ?>
                                                              <?php $hasEv = !empty($evidMap[$aid][$f] ?? []); ?>
                                                              <button type="button"
                                                                      class="btn btn-sm <?= $hasEv ? 'btn-success' : 'btn-outline-secondary' ?> pt-ev-btn"
                                                                      style="padding: .10rem .30rem;"
                                                                      data-ev-btn="1"
                                                                      data-bs-toggle="modal"
                                                                      data-bs-target="#modalEvidencias"
                                                                      data-programa-id="<?= htmlspecialchars((string)($programaActual['id'] ?? '')) ?>"
                                                                      data-actividad-id="<?= htmlspecialchars($aid) ?>"
                                                                      data-fecha="<?= htmlspecialchars($f) ?>"
                                                                      title="<?= $hasEv ? 'Ver / agregar evidencia' : 'Agregar evidencia' ?>">
                                                                <i class="bi bi-paperclip"></i>
                                                              </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                              <?php endif; ?>
                                          <?php endforeach; ?>
                                      </tr>
                                  <?php endforeach; ?>
                              </tbody>
                          </table>
                      </div>
                      <?php if (true): ?>
                      <div class="p-2 border-top text-end">
                          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Guardar programación y ejecución</button>
                      </div>
                      <?php endif; ?>
                  </form>
                  <?php if ($canEditProgramaActual): ?>
                  <form method="post" id="ptFrmEliminarActividad" class="d-none" aria-hidden="true">
                      <input type="hidden" name="action" value="eliminar_actividad">
                      <input type="hidden" name="programa_id" id="ptDelActProgId" value="<?= htmlspecialchars((string)($programaActual['id'] ?? '')) ?>">
                      <input type="hidden" name="actividad_id" id="ptDelActId" value="">
                  </form>
                  <?php endif; ?>
              </div>
          </div>

          <?php if (!empty($ptPerResponsable)): ?>
          <div class="card mt-3">
              <div class="card-header card-header-accent">
                  <i class="bi bi-people me-1"></i> Avance por persona
              </div>
              <div class="card-body p-0">
                  <div class="table-responsive">
                      <table class="table table-sm align-middle mb-0">
                          <thead class="table-light">
                              <tr>
                                  <th>Responsable</th>
                                  <th class="text-center" style="width: 100px;">Actividades</th>
                                  <th class="text-center" style="width: 100px;">Cumplidas</th>
                                  <th class="text-center" style="width: 100px;">Atrasadas</th>
                                  <th style="min-width: 220px;">Avance promedio</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php foreach ($ptPerResponsable as $pr): ?>
                                  <tr>
                                      <td><?= htmlspecialchars((string)($pr['nombre'] ?? '')) ?></td>
                                      <td class="text-center"><?= (int)($pr['total'] ?? 0) ?></td>
                                      <td class="text-center text-success fw-semibold"><?= (int)($pr['cumplidas'] ?? 0) ?></td>
                                      <td class="text-center text-danger fw-semibold"><?= (int)($pr['atrasadas'] ?? 0) ?></td>
                                      <td>
                                          <div class="d-flex align-items-center gap-2">
                                              <div class="progress flex-grow-1" style="height: 8px;">
                                                  <div class="progress-bar bg-info" role="progressbar" style="width: <?= (float)($pr['avance'] ?? 0) ?>%"></div>
                                              </div>
                                              <span class="small fw-semibold" style="width: 56px; text-align: right;"><?= htmlspecialchars((string)($pr['avance'] ?? 0)) ?>%</span>
                                          </div>
                                      </td>
                                  </tr>
                              <?php endforeach; ?>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
          <?php endif; ?>
        <?php elseif ($programaActual): ?>
          <div class="alert alert-light border small mt-3 mb-0">Este programa no tiene actividades todavía.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($programaActual): ?>
  <div class="tab-pane fade" id="tabGantt" role="tabpanel">
    <div class="card">
      <div class="card-header card-header-accent d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bar-chart-steps me-1"></i> Vista Gantt</span>
        <span class="small text-muted"><?= htmlspecialchars($programaActual['nombre'] ?? '') ?></span>
      </div>
      <div class="card-body">
        <?php
          $rangeIni = (string)($programaActual['fecha_inicio'] ?? '');
          $rangeFin = (string)($programaActual['fecha_fin'] ?? '');
          $rangeIniDt = $rangeIni !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d', $rangeIni) : null;
          $rangeFinDt = $rangeFin !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d', $rangeFin) : null;
          $totalDias = ($rangeIniDt && $rangeFinDt) ? max(1, ($rangeFinDt->getTimestamp() - $rangeIniDt->getTimestamp()) / 86400 + 1) : 1;
        ?>
        <?php if ($rangeIniDt && $rangeFinDt): ?>
        <div class="small text-muted mb-2">
          <i class="bi bi-calendar3 me-1"></i>
          <?= htmlspecialchars($__fmtFechaCol($rangeIni)) ?> &mdash; <?= htmlspecialchars($__fmtFechaCol($rangeFin)) ?>
        </div>
        <?php foreach ($actividades as $a):
          $aid = (string)($a['id'] ?? '');
          $aIni = (string)($a['fecha_inicio'] ?? '');
          $aFin = (string)($a['fecha_fin'] ?? '');
          $aIniDt = $aIni !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d', $aIni) : null;
          $aFinDt = $aFin !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d', $aFin) : null;
          if (!$aIniDt || !$aFinDt) continue;
          $offsetDays = max(0, ($aIniDt->getTimestamp() - $rangeIniDt->getTimestamp()) / 86400);
          $lenDays = max(1, ($aFinDt->getTimestamp() - $aIniDt->getTimestamp()) / 86400 + 1);
          $offsetPct = ($offsetDays / $totalDias) * 100;
          $lenPct = ($lenDays / $totalDias) * 100;
          $ra = $resumenActividades[$aid] ?? ['avance' => 0, 'estado' => 'sin_iniciar'];
          $avancePct = (float)($ra['avance'] ?? 0);
          $progressPct = $lenPct * ($avancePct / 100);
          // Today line
          $todayDt = \DateTimeImmutable::createFromFormat('Y-m-d', (string)$today);
          $todayPct = null;
          if ($todayDt && $todayDt >= $rangeIniDt && $todayDt <= $rangeFinDt) {
              $todayDays = ($todayDt->getTimestamp() - $rangeIniDt->getTimestamp()) / 86400;
              $todayPct = ($todayDays / $totalDias) * 100;
          }
        ?>
        <div class="pt-gantt-row">
          <div style="width: 240px; min-width: 240px;" class="small text-truncate">
            <span class="fw-semibold me-1"><?= (int)($a['numero'] ?? 0) ?>.</span>
            <?= htmlspecialchars($a['actividad'] ?? '') ?>
          </div>
          <div class="pt-gantt-track">
            <div class="pt-gantt-bar" style="left: <?= number_format($offsetPct,2,'.','') ?>%; width: <?= number_format($lenPct,2,'.','') ?>%;"></div>
            <div class="pt-gantt-progress" style="left: <?= number_format($offsetPct,2,'.','') ?>%; width: <?= number_format($progressPct,2,'.','') ?>%;"></div>
            <?php if ($todayPct !== null): ?>
              <div class="pt-gantt-today" style="left: <?= number_format($todayPct,2,'.','') ?>%;"></div>
            <?php endif; ?>
          </div>
          <div class="small text-muted" style="width: 140px; min-width: 140px; text-align: right;">
            <?= htmlspecialchars($__fmtFechaCol($aIni)) ?> &rarr; <?= htmlspecialchars($__fmtFechaCol($aFin)) ?>
          </div>
          <div class="small fw-semibold" style="width: 56px; min-width: 56px; text-align: right;">
            <?= htmlspecialchars((string)$avancePct) ?>%
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
          <div class="alert alert-light border small mb-0">No hay actividades para mostrar.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php /* KPIs + Matriz + Avance por persona se muestran solo en pestaña Programas */ ?>

<?php if (!empty($programaActual) && $canEditProgramaActual): ?>
<!-- Modal Editar Programa -->
<div class="modal fade" id="modalEditarPrograma" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar programa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="action" value="actualizar_programa">
          <input type="hidden" name="programa_id" value="<?= htmlspecialchars((string)($programaActual['id'] ?? '')) ?>">
          <div class="col-12">
            <label class="form-label small fw-semibold mb-1">Nombre *</label>
            <input type="text" class="form-control form-control-sm" name="nombre_programa" required
                   value="<?= htmlspecialchars((string)($programaActual['nombre'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Frecuencia *</label>
            <select name="frecuencia" class="form-select form-select-sm" required>
              <?php $pf = (string)($programaActual['frecuencia'] ?? 'semanal'); ?>
              <option value="diario" <?= $pf === 'diario' ? 'selected' : '' ?>>Diario</option>
              <option value="semanal" <?= $pf === 'semanal' ? 'selected' : '' ?>>Semanal</option>
              <option value="catorcenal" <?= $pf === 'catorcenal' ? 'selected' : '' ?>>Catorcenal</option>
              <option value="mensual" <?= $pf === 'mensual' ? 'selected' : '' ?>>Mensual</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select name="estado" class="form-select form-select-sm">
              <?php $pe = (string)($programaActual['estado'] ?? 'activo'); ?>
              <option value="activo" <?= $pe === 'activo' ? 'selected' : '' ?>>Activo</option>
              <option value="cerrado" <?= $pe === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
            </select>
          </div>
          <div class="col-12">
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save me-1"></i> Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar Programa -->
<div class="modal fade" id="modalEliminarPrograma" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-danger">
        <h5 class="modal-title text-danger">Eliminar programa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="small mb-2">Se eliminarán todas las actividades, avances programados y ejecutados, evidencias y archivos asociados a <strong><?= htmlspecialchars((string)($programaActual['nombre'] ?? '')) ?></strong>. Esta acción no se puede deshacer.</p>
        <form method="post" id="frmEliminarPrograma" class="row g-2">
          <input type="hidden" name="action" value="eliminar_programa">
          <input type="hidden" name="programa_id" value="<?= htmlspecialchars((string)($programaActual['id'] ?? '')) ?>">
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="ptConfirmDelProg" required>
              <label class="form-check-label small" for="ptConfirmDelProg">Confirmo que deseo eliminar este programa por completo.</label>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash me-1"></i> Eliminar definitivamente</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Actividad -->
<div class="modal fade" id="modalEditarActividad" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar actividad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-2">Si cambia fechas, medición o cantidad, se regenera lo programado (P) y puede borrarse ejecución y evidencias fuera del nuevo rango.</p>
        <form method="post" class="row g-2" id="frmEditarActividad">
          <input type="hidden" name="action" value="editar_actividad">
          <input type="hidden" name="programa_id" value="<?= htmlspecialchars((string)($programaActual['id'] ?? '')) ?>">
          <input type="hidden" name="actividad_id" id="editActId" value="">
          <div class="col-12">
            <label class="form-label small fw-semibold mb-1">Actividad *</label>
            <input type="text" class="form-control form-control-sm" name="actividad" required id="editActNombre">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Medición *</label>
            <select class="form-select form-select-sm" name="tipo_medicion" id="editActMedicion">
              <option value="cantidad">Cantidad</option>
              <option value="entregable">Entregable</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Cantidad</label>
            <input type="number" min="1" class="form-control form-control-sm text-end" name="cantidad" id="editActCantidad" value="1">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Inicio *</label>
            <input type="date" class="form-control form-control-sm" name="actividad_inicio" required id="editActInicio">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Fin *</label>
            <input type="date" class="form-control form-control-sm" name="actividad_fin" required id="editActFin">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Tipo *</label>
            <input type="text" class="form-control form-control-sm" name="tipo" required id="editActTipo" placeholder="Ej. Desarrollo">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Responsable *</label>
            <?php if (!empty($currentUser['es_super_admin'])): ?>
              <select class="form-select form-select-sm" name="responsable_id" required id="editActResp">
                <option value="">Seleccione...</option>
                <?php foreach (($users ?? []) as $u): ?>
                  <option value="<?= htmlspecialchars((string)($u['id'] ?? '')) ?>">
                    <?= htmlspecialchars((string)($u['nombre'] ?? ($u['rpe'] ?? ($u['id'] ?? '')))) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="hidden" name="responsable_id" value="<?= htmlspecialchars((string)($currentUser['id'] ?? '')) ?>">
              <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($currentUser['nombre'] ?? '')) ?>" readonly>
            <?php endif; ?>
          </div>
          <div class="col-12">
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save me-1"></i> Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal Nueva Actividad -->
<div class="modal fade" id="modalNuevaActividad" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar actividad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="action" value="agregar_actividad">
          <input type="hidden" name="programa_id" value="<?= htmlspecialchars((string)($programaActual['id'] ?? '')) ?>">
          <div class="col-12">
            <label class="form-label small fw-semibold mb-1">Actividad *</label>
            <input type="text" class="form-control form-control-sm" name="actividad" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Medición *</label>
            <select class="form-select form-select-sm" name="tipo_medicion" id="modalActMedicion">
              <option value="cantidad">Cantidad</option>
              <option value="entregable">Entregable</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Cantidad</label>
            <input type="number" min="1" class="form-control form-control-sm text-end" name="cantidad" id="modalActCantidad" value="1">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Inicio *</label>
            <input type="date" class="form-control form-control-sm" name="actividad_inicio" required id="modalActInicio">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Fin *</label>
            <input type="date" class="form-control form-control-sm" name="actividad_fin" required id="modalActFin">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Tipo *</label>
            <input type="text" class="form-control form-control-sm" name="tipo" required placeholder="Ej. Desarrollo">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Responsable *</label>
            <?php if (!empty($currentUser['es_super_admin'])): ?>
              <select class="form-select form-select-sm" name="responsable_id" required>
                <option value="">Seleccione...</option>
                <?php foreach (($users ?? []) as $u): ?>
                  <option value="<?= htmlspecialchars((string)($u['id'] ?? '')) ?>" <?= (($u['id'] ?? '') === ($currentUser['id'] ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)($u['nombre'] ?? ($u['rpe'] ?? ($u['id'] ?? '')))) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="hidden" name="responsable_id" value="<?= htmlspecialchars((string)($currentUser['id'] ?? '')) ?>">
              <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($currentUser['nombre'] ?? '')) ?>" readonly>
            <?php endif; ?>
          </div>
          <div class="col-12">
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save me-1"></i> Agregar</button>
          </div>
        </form>
        <div class="form-text">Al agregarla, se generará automáticamente el rango de días activos para esta actividad según sus fechas.</div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    const tbody = document.getElementById('tbActividades');
    const btn = document.getElementById('btnAddActividad');
    const selFreq = document.getElementById('selFrecuenciaPrograma');
    if (!tbody || !btn) return;

    const IS_SUPER = <?= !empty($currentUser['es_super_admin']) ? 'true' : 'false' ?>;
    const CURRENT_USER_ID = <?= json_encode((string)($currentUser['id'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    const CURRENT_USER_NOMBRE = <?= json_encode((string)($currentUser['nombre'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    const USERS = <?= json_encode(array_values($users ?? []), JSON_UNESCAPED_UNICODE) ?>;

    function todayYmd() {
        const d = new Date();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    function addDays(ymd, days) {
        const dt = new Date(ymd + 'T00:00:00');
        dt.setDate(dt.getDate() + days);
        const mm = String(dt.getMonth() + 1).padStart(2, '0');
        const dd = String(dt.getDate()).padStart(2, '0');
        return dt.getFullYear() + '-' + mm + '-' + dd;
    }

    // Ajuste simple por frecuencia (como base): diario = libre, semanal = 7 días naturales, catorcenal = 14, mensual = 30.
    function normalizeFinByFrecuencia(inicio, fin, frecuencia) {
        if (!inicio) return fin;
        if (frecuencia === 'semanal') return addDays(inicio, 6);
        if (frecuencia === 'catorcenal') return addDays(inicio, 13);
        if (frecuencia === 'mensual') return addDays(inicio, 29);
        return fin;
    }

    let rowCounter = 0;

    function addRow(pref = {}) {
        rowCounter++;
        const rid = 'pt-row-' + rowCounter;
        const tr = document.createElement('tr');
        const med = pref.medicion || 'cantidad';
        const cant = (pref.cantidad != null) ? pref.cantidad : (med === 'porcentaje' ? 100 : 1);
        const inicio = (pref.inicio != null && pref.inicio !== '') ? pref.inicio : todayYmd();
        const freq = selFreq ? (selFreq.value || 'semanal') : 'semanal';
        const fin = (pref.fin != null && pref.fin !== '')
            ? pref.fin
            : normalizeFinByFrecuencia(inicio, '', freq);

        let responsableHtml = '';
        if (IS_SUPER) {
            let opts = '<option value=\"\">Seleccione...</option>';
            for (const u of (USERS || [])) {
                const uid = String(u.id || '');
                const nom = String(u.nombre || u.rpe || uid);
                const sel = (uid && (pref.responsable_id ? String(pref.responsable_id) === uid : uid === CURRENT_USER_ID)) ? ' selected' : '';
                opts += '<option value=\"' + uid.replace(/\"/g,'') + '\"' + sel + '>' + nom.replace(/</g,'&lt;') + '</option>';
            }
            responsableHtml = '<select class=\"form-select form-select-sm\" name=\"actividad_responsable_id[]\" required>' + opts + '</select>';
        } else {
            responsableHtml =
                '<input type=\"hidden\" name=\"actividad_responsable_id[]\" value=\"' + (CURRENT_USER_ID || '') + '\">' +
                '<input class=\"form-control form-control-sm\" value=\"' + (CURRENT_USER_NOMBRE || '') + '\" readonly>';
        }

        // Pills de Medición usando btn-check (radio group) (Porcentaje deshabilitado por el momento)
        const pillsHtml = ''
            + '<div class="btn-group btn-group-sm pt-pills" role="group">'
            +   '<input type="radio" class="btn-check" name="actividad_medicion[]" id="' + rid + '-mc" autocomplete="off" value="cantidad" ' + (med === 'cantidad' ? 'checked' : '') + ' data-row-pill="' + rid + '">'
            +   '<label class="btn btn-outline-primary" for="' + rid + '-mc">Cantidad</label>'
            +   '<input type="radio" class="btn-check" name="actividad_medicion[]" id="' + rid + '-me" autocomplete="off" value="entregable" ' + (med === 'entregable' ? 'checked' : '') + ' data-row-pill="' + rid + '">'
            +   '<label class="btn btn-outline-primary" for="' + rid + '-me">Entreg.</label>'
            + '</div>';

        tr.innerHTML = ''
            + '<td><input class="form-control form-control-sm" name="actividad_nombre[]" required value="' + (pref.actividad || '') + '"></td>'
            + '<td><input type="number" min="1" class="form-control form-control-sm text-end" name="actividad_cantidad[]" value="' + cant + '"></td>'
            + '<td>' + pillsHtml + '</td>'
            + '<td><input type="date" class="form-control form-control-sm" name="actividad_inicio[]" required value="' + inicio + '"></td>'
            + '<td><input type="date" class="form-control form-control-sm" name="actividad_fin[]" required value="' + fin + '"></td>'
            + '<td><input class="form-control form-control-sm" name="actividad_tipo[]" required value="' + (pref.tipo || '') + '"></td>'
            + '<td>' + responsableHtml + '</td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-row="1" title="Quitar"><i class="bi bi-x-lg"></i></button></td>';
        tbody.appendChild(tr);
        const rmBtn = tr.querySelector('button[data-remove-row="1"]');
        if (rmBtn) {
            rmBtn.addEventListener('click', function() {
                if (tbody.children.length > 1) {
                    tr.remove();
                } else {
                    tr.querySelectorAll('input').forEach(function(i){ if (!i.readOnly && i.type !== 'radio') i.value=''; });
                }
                validateForm();
            });
        }

        const pills = tr.querySelectorAll('input[data-row-pill="' + rid + '"]');
        const cantEl = tr.querySelector('input[name="actividad_cantidad[]"]');
        const iniEl = tr.querySelector('input[name="actividad_inicio[]"]');
        const finEl = tr.querySelector('input[name="actividad_fin[]"]');

        function getMedicion() {
            for (const p of pills) { if (p.checked) return p.value; }
            return 'cantidad';
        }

        function applyMedicion(v) {
            if (v === 'cantidad') {
                cantEl.disabled = false;
                cantEl.required = true;
                if (!cantEl.value || parseFloat(cantEl.value) <= 0) cantEl.value = 1;
            } else { // entregable
                cantEl.value = '';
                cantEl.disabled = true;
                cantEl.required = false;
            }
            validateForm();
        }

        function applyFrecuenciaToFin() {
            if (!iniEl || !finEl || !selFreq) return;
            finEl.value = normalizeFinByFrecuencia(iniEl.value, finEl.value, selFreq.value || 'diario');
            validateForm();
        }

        pills.forEach(function (p) {
            p.addEventListener('change', function () { applyMedicion(getMedicion()); });
        });
        applyMedicion(getMedicion());

        if (iniEl) iniEl.addEventListener('change', applyFrecuenciaToFin);
        if (finEl) finEl.addEventListener('change', validateForm);
        if (cantEl) cantEl.addEventListener('input', validateForm);

        validateForm();
    }

    function validateForm() {
        let okGlobal = true;
        Array.prototype.forEach.call(tbody.children, function (tr) {
            const cantEl = tr.querySelector('input[name="actividad_cantidad[]"]');
            const iniEl = tr.querySelector('input[name="actividad_inicio[]"]');
            const finEl = tr.querySelector('input[name="actividad_fin[]"]');
            if (!iniEl || !finEl) return;
            const ini = iniEl.value, fin = finEl.value;
            const dateOk = ini && fin && ini <= fin;
            iniEl.classList.toggle('is-pt-invalid', !!ini && !dateOk);
            finEl.classList.toggle('is-pt-invalid', !!fin && !dateOk);
            if (!dateOk) okGlobal = false;
            // cantidad si está visible
            if (cantEl && !cantEl.disabled) {
                const v = parseFloat(cantEl.value);
                const cantOk = !isNaN(v) && v > 0;
                cantEl.classList.toggle('is-pt-invalid', !cantOk);
                if (!cantOk) okGlobal = false;
            } else if (cantEl) {
                cantEl.classList.remove('is-pt-invalid');
            }
        });
        const submitBtn = document.querySelector('#frmNuevoPrograma button[type="submit"]');
        if (submitBtn) submitBtn.disabled = !okGlobal || tbody.children.length === 0;
    }

    btn.addEventListener('click', function () { addRow({}); });
    addRow({ medicion: 'cantidad' });

    if (selFreq) {
        selFreq.addEventListener('change', function () {
            // re-aplica fin según la frecuencia a todas las filas
            Array.prototype.forEach.call(tbody.children, function (tr) {
                const ini = tr.querySelector('input[name="actividad_inicio[]"]');
                const fin = tr.querySelector('input[name="actividad_fin[]"]');
                if (ini && fin) fin.value = normalizeFinByFrecuencia(ini.value, fin.value, selFreq.value || 'diario');
            });
            validateForm();
        });
    }
})();
</script>

<script>
// Filtros de pestaña Programas (búsqueda, frecuencia, estado)
(function () {
    const buscar = document.getElementById('ptFiltroBuscar');
    const frec = document.getElementById('ptFiltroFrecuencia');
    const est = document.getElementById('ptFiltroEstado');
    const limpiar = document.getElementById('ptFiltroLimpiar');
    const grid = document.getElementById('ptProgGrid');
    const cnt = document.getElementById('ptProgCount');
    if (!grid) return;

    function applyFilter() {
        const q = (buscar && buscar.value || '').trim().toLowerCase();
        const f = frec && frec.value || '';
        const e = est && est.value || '';
        let visible = 0;
        Array.prototype.forEach.call(grid.querySelectorAll('.pt-prog-item'), function (it) {
            const nm = (it.getAttribute('data-nombre') || '');
            const fr = (it.getAttribute('data-frecuencia') || '');
            const es = (it.getAttribute('data-estado') || '');
            const ok = (q === '' || nm.indexOf(q) !== -1) && (f === '' || fr === f) && (e === '' || es === e);
            it.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        if (cnt) cnt.textContent = visible + ' registros';
    }
    if (buscar) buscar.addEventListener('input', applyFilter);
    if (frec) frec.addEventListener('change', applyFilter);
    if (est) est.addEventListener('change', applyFilter);
    if (limpiar) limpiar.addEventListener('click', function () {
        if (buscar) buscar.value = '';
        if (frec) frec.value = '';
        if (est) est.value = '';
        applyFilter();
    });
})();
</script>

<script>
(function() {
  const med = document.getElementById('modalActMedicion');
  const cant = document.getElementById('modalActCantidad');
  const ini = document.getElementById('modalActInicio');
  const fin = document.getElementById('modalActFin');
  const selFreq = document.getElementById('selFrecuenciaPrograma');
  if (!med || !cant || !ini || !fin) return;

  function todayYmd() {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
  }
  function addDays(ymd, days) {
    const dt = new Date(ymd + 'T00:00:00');
    dt.setDate(dt.getDate() + days);
    const mm = String(dt.getMonth() + 1).padStart(2, '0');
    const dd = String(dt.getDate()).padStart(2, '0');
    return dt.getFullYear() + '-' + mm + '-' + dd;
  }
  function normalizeFinByFrecuencia(inicio, frecuencia) {
    if (!inicio) return '';
    if (frecuencia === 'semanal') return addDays(inicio, 6);
    if (frecuencia === 'catorcenal') return addDays(inicio, 13);
    if (frecuencia === 'mensual') return addDays(inicio, 29);
    return '';
  }

  function applyMed(v) {
    if (v === 'cantidad') {
      cant.disabled = false; cant.required = true;
    } else {
      cant.value = ''; cant.disabled = true; cant.required = false;
    }
  }

  function applyFreq() {
    const f = selFreq ? (selFreq.value || 'diario') : 'diario';
    const nf = normalizeFinByFrecuencia(ini.value, f);
    if (nf) fin.value = nf;
  }

  // defaults
  if (!ini.value) ini.value = todayYmd();
  applyFreq();
  applyMed(med.value);

  med.addEventListener('change', () => applyMed(med.value));
  ini.addEventListener('change', () => applyFreq());
  if (selFreq) selFreq.addEventListener('change', () => applyFreq());
})();
</script>

<?php if (!empty($programaActual) && !empty($canEditProgramaActual)): ?>
<script>
(function () {
  window.ptEliminarActividad = function (actId) {
    if (!actId || !confirm('¿Eliminar esta actividad y sus avances asociados?')) return;
    var f = document.getElementById('ptFrmEliminarActividad');
    var i = document.getElementById('ptDelActId');
    if (!f || !i) return;
    i.value = actId;
    f.submit();
  };

  var modalEdit = document.getElementById('modalEditarActividad');
  var progFreq = <?= json_encode((string)($programaActual['frecuencia'] ?? 'diario'), JSON_UNESCAPED_UNICODE) ?>;
  var eMed = document.getElementById('editActMedicion');
  var eCant = document.getElementById('editActCantidad');
  var eIni = document.getElementById('editActInicio');
  var eFin = document.getElementById('editActFin');
  if (!modalEdit || !eMed || !eCant || !eIni || !eFin) return;

  function addDays(ymd, days) {
    var dt = new Date(ymd + 'T00:00:00');
    dt.setDate(dt.getDate() + days);
    var mm = String(dt.getMonth() + 1).padStart(2, '0');
    var dd = String(dt.getDate()).padStart(2, '0');
    return dt.getFullYear() + '-' + mm + '-' + dd;
  }
  function normalizeFinByFrecuencia(inicio, frecuencia) {
    if (!inicio) return '';
    if (frecuencia === 'semanal') return addDays(inicio, 6);
    if (frecuencia === 'catorcenal') return addDays(inicio, 13);
    if (frecuencia === 'mensual') return addDays(inicio, 29);
    return '';
  }
  function applyEditMed(v) {
    if (v === 'cantidad') {
      eCant.disabled = false;
      eCant.required = true;
      if (!eCant.value || parseFloat(eCant.value) <= 0) eCant.value = '1';
    } else {
      eCant.value = '';
      eCant.disabled = true;
      eCant.required = false;
    }
  }
  function applyEditFreq() {
    var f = progFreq || 'diario';
    if (f === 'diario') return;
    var nf = normalizeFinByFrecuencia(eIni.value, f);
    if (nf) eFin.value = nf;
  }

  eMed.addEventListener('change', function () { applyEditMed(eMed.value); });
  eIni.addEventListener('change', applyEditFreq);

  modalEdit.addEventListener('show.bs.modal', function (ev) {
    var btn = ev.relatedTarget;
    if (!btn || !btn.dataset) return;
    document.getElementById('editActId').value = btn.dataset.actId || '';
    document.getElementById('editActNombre').value = btn.dataset.actNombre || '';
    eMed.value = (btn.dataset.actMed === 'entregable') ? 'entregable' : 'cantidad';
    eCant.value = btn.dataset.actCant || '1';
    eIni.value = btn.dataset.actIni || '';
    eFin.value = btn.dataset.actFin || '';
    document.getElementById('editActTipo').value = btn.dataset.actTipo || '';
    var sel = document.getElementById('editActResp');
    if (sel) sel.value = btn.dataset.actResp || '';
    applyEditMed(eMed.value);
  });
})();
</script>
<?php endif; ?>

<?php if (!empty($programaActual)): ?>
<!-- Modal Evidencias (solo Entregables) -->
<div class="modal fade" id="modalEvidencias" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEvidenciasTitle">Evidencias</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="small text-muted mb-2" id="modalEvidenciasMeta"></div>

        <?php if (true): ?>
        <div class="card mb-3">
          <div class="card-header">Cargar evidencia (Entregable)</div>
          <div class="card-body">
            <form method="post" enctype="multipart/form-data" id="frmEvidencia">
              <input type="hidden" name="action" value="subir_evidencia">
              <input type="hidden" name="programa_id" id="evProgramaId" value="">
              <input type="hidden" name="actividad_id" id="evActividadId" value="">
              <input type="hidden" name="fecha_columna" id="evFecha" value="">
              <div class="row g-2">
                <div class="col-md-8">
                  <label class="form-label small fw-semibold mb-1">Archivos *</label>
                  <div class="border rounded-3 p-3 bg-light" id="ptEvDrop" style="cursor:pointer;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="small">
                        <div class="fw-semibold">Arrastra archivos aquí o da clic para seleccionar</div>
                        <div class="text-muted">Se ligan a la celda (actividad + fecha)</div>
                      </div>
                      <span class="badge text-bg-secondary">Entregable</span>
                    </div>
                    <input type="file" class="form-control form-control-sm mt-2" name="ev_archivo[]" id="ptEvFiles" multiple required>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold mb-1">Comentario</label>
                  <input type="text" class="form-control form-control-sm" name="comentario" maxlength="180" placeholder="Opcional">
                </div>
                <div class="col-12">
                  <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-upload me-1"></i> Subir</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span>Evidencias existentes</span>
            <span class="text-muted small" id="evCount"></span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Archivo</th>
                    <th>Comentario</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="evList"></tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script type="application/json" id="evidenciasData"><?= json_encode($evidMap, JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

