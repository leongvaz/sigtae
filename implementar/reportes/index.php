<?php
// Archivo principal de la interfaz del sistema de reportes.
// Nota: aqui no hay metodos PHP ejecutables; la logica vive en js/app.js.
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LABORATORIO DE MEDICIÓN DIVISIONAL</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
</head>
<body>

<!-- Pantalla de autenticacion inicial -->
<section class="login-screen" id="loginScreen">
  <div class="login-card">
    <h2>LABORATORIO DE MEDICION</h2>
    <p>Ingresa tu RPE y contraseña para continuar.</p>
    <form id="loginForm" class="login-form" novalidate>
      <div class="field-group">
        <label for="loginRpe">RPE</label>
        <input type="text" id="loginRpe" class="field-input" placeholder="Ej. G44BR" autocomplete="username" maxlength="5" required>
      </div>
      <div class="field-group">
        <label for="loginPassword">Contraseña</label>
        <input type="password" id="loginPassword" class="field-input" placeholder="Tu contraseña" autocomplete="current-password" required>
      </div>
      <button type="submit" id="loginSubmit" class="btn-login">Iniciar sesión</button>
      <p class="login-message" id="loginMessage"></p>
    </form>
  </div>
</section>

<!-- Contenedor principal de la aplicacion (se muestra tras login) -->
<div class="app-shell">
  <!-- SIDEBAR -->

  <!-- MAIN CONTENT -->
  <!-- Contenido principal con pasos del formulario -->
  <main class="main-content">
    <!-- Encabezado principal del sistema -->
    <header class="top-bar">
      <div class="top-bar-title">
        <h1>LABORATORIO DE MEDICIÓN DIVISIONAL<span class="badge"></span></h1>
        <p class="subtitle">Transformadores de Corriente TIM</p>
        <p class="subtitle">EQUIPO COMBINADO DE MEDICION (AEREO)</p>
        <p class="subtitle">TIPO DE EQUIPO</p>
      </div>
      <div class="top-bar-actions">
        <button type="button" id="backToHubBtn" class="btn-logout">Menú principal</button>
        <button type="button" id="logoutBtn" class="btn-logout">Cerrar sesión</button>
      </div>
    </header>

    <!-- PROGRESS BAR -->
    <!-- Texto de avance de llenado del formulario -->
    <div class="progress-meta">
      <span class="progress-fill-label" id="fillProgressLabel">Formulario: 0%</span>
    </div>
    <!-- Barra visual de progreso de captura -->
    <div class="progress-bar">
      <div class="progress-fill progress-fill-form" id="progressFill" style="width: 0%"></div>
    </div>

    <!-- Hub inicial para crear/cargar REPORTES guardados -->
    <div class="card hub-module hub-module-saved" id="evaluationHub">
      <p class="hub-welcome">BIENVENIDO, <span id="currentUserName">-</span></p>
      <h3 class="subsection-title">Reportes guardados</h3>
      <div class="card-grid">
        <div class="field-group">
          <label for="savedZoneFilter">Filtrar guardados por zona</label>
          <select id="savedZoneFilter" class="field-input">
            <option value="">Todas las zonas</option>
          </select>
        </div>
      </div>
      <div class="card-grid">
        <div class="field-group">
          <label for="savedEvaluations">Selecciona un reporte</label>
          <select id="savedEvaluations" class="field-input">
            <option value="">Sin reportes guardados</option>
          </select>
        </div>
        <div class="field-group">
          <label>&nbsp;</label>
          <button class="btn-nav" id="loadEvaluationBtn" type="button">Cargar reporte</button>
        </div>
        <div class="field-group">
          <label>&nbsp;</label>
          <button class="btn-nav" id="newEvaluationBtn" type="button">Nuevo reporte</button>
        </div>
      </div>
      <p class="calc-note" id="evaluationStatus"></p>

      <div class="card hub-module hub-module-review mt-20" id="adminReviewPanel" style="display:none;">
        <h3 class="subsection-title">Bandeja de reportes pendientes</h3>
        <div class="card-grid">
          <div class="field-group">
            <label for="adminZoneFilter">Filtrar por zona</label>
            <select id="adminZoneFilter" class="field-input">
              <option value="">Todas las zonas</option>
            </select>
          </div>
        </div>
        <div class="card-grid mt-20">
          <div class="field-group">
            <label for="pendingEvaluations">Pendientes por aprobar</label>
            <select id="pendingEvaluations" class="field-input">
              <option value="">Sin pendientes</option>
            </select>
          </div>
          <div class="field-group">
            <label>&nbsp;</label>
            <button class="btn-nav" id="loadPendingBtn" type="button">Ver pendiente</button>
          </div>
          <div class="field-group">
            <label>&nbsp;</label>
            <button class="btn-nav" id="approvePendingBtn" type="button">Aprobar reporte</button>
          </div>
        </div>

        <h3 class="subsection-title mt-20">Reportes aprobados</h3>
        <div class="card-grid">
          <div class="field-group">
            <label for="approvedEvaluations">Aprobadas</label>
            <select id="approvedEvaluations" class="field-input">
              <option value="">Sin aprobadas</option>
            </select>
          </div>
          <div class="field-group">
            <label>&nbsp;</label>
            <button class="btn-nav" id="loadApprovedBtn" type="button">Ver aprobada</button>
          </div>
        </div>
      </div>

      <div class="card hub-module hub-module-admin mt-20" id="masterAdminPanel" style="display:none;">
        <div class="admin-section" id="adminReviewersSection">
          <h3 class="subsection-title">Alta de administradores revisores</h3>
          <div class="card-grid">
            <div class="field-group">
              <label for="newAdminRpe">RPE administrador</label>
              <input type="text" id="newAdminRpe" class="field-input" maxlength="5" placeholder="Ej. A12BC">
            </div>
            <div class="field-group">
              <label>&nbsp;</label>
              <button class="btn-nav" id="validateAdminRpeBtn" type="button">Validar en directorio</button>
            </div>
            <div class="field-group">
              <label>&nbsp;</label>
              <button class="btn-nav" id="addAdminRpeBtn" type="button" disabled>Dar de alta administrador</button>
            </div>
            <div class="field-group">
              <label for="adminRpeList">Administradores activos</label>
              <select id="adminRpeList" class="field-input">
                <option value="">Sin administradores</option>
              </select>
            </div>
          </div>
          <small class="rpe-feedback admin-rpe-feedback" id="newAdminRpeFeedback"></small>
        </div>

        <div class="admin-section" id="normalUsersSection">
          <h3 class="subsection-title">Alta de usuarios normales</h3>
          <div class="card-grid">
            <div class="field-group">
              <label for="newNormalRpe">RPE usuario normal</label>
              <input type="text" id="newNormalRpe" class="field-input" maxlength="5" placeholder="Ej. B45CD">
            </div>
            <div class="field-group">
              <label>&nbsp;</label>
              <button class="btn-nav" id="validateNormalRpeBtn" type="button">Validar en directorio</button>
            </div>
            <div class="field-group">
              <label>&nbsp;</label>
              <button class="btn-nav" id="addNormalRpeBtn" type="button" disabled>Dar de alta usuario normal</button>
            </div>
            <div class="field-group">
              <label for="normalRpeList">Usuarios normales activos</label>
              <select id="normalRpeList" class="field-input">
                <option value="">Sin usuarios normales</option>
              </select>
            </div>
          </div>
          <small class="rpe-feedback admin-rpe-feedback" id="newNormalRpeFeedback"></small>
        </div>
      </div>
    </div>

    <!-- SECTION: DATOS DE PLACA -->
    <section class="form-section active" id="section-datos-placa">
      <div class="section-header">
        <h2>Datos de Placa del Equipo</h2>
      </div>

      <!-- Objeto de captura: categoria base para autollenado TP/TC -->
      <div class="card">
        <div class="field-group">
          <label for="tp_categoria">CATEGORÍA</label>
          <select id="tp_categoria" name="tp_categoria" class="field-input">
            <option value="">Selecciona una categoría</option>
            <option value="10-5">10-5</option>
            <option value="50-5">50-5</option>
            <option value="200-5">200-5</option>
          </select>
        </div>
      </div>

      <!-- Objeto de captura: datos generales de placa del equipo -->
      <div class="card">
        <div class="card-grid">
          <div class="field-group">
            <label for="opciones">ZONA</label>
            <select id="opciones" name="opciones">
            <option value="">Seleccione una zona</option>
            <option value="1">21 / ZOCALO</option>
            <option value="2">22 / BENITO JUAREZ</option>
            <option value="3">23 / POLANCO</option>
            <option value="4">24 / TACUBA</option>
            <option value="5">25 / AEROPUERTO</option>
            <option value="6">26 / NEZA</option>
            <option value="6">26 / CHAPINGO</option>
            </select>
          </div>
          <div class="field-group">
          <label for="opciones/marca">MARCA</label>
          <select id="opciones/marca" name="opciones/marca">
            <option value="">Seleccione una marca</option>
            <option value="1">SCHNEIDER</option>
            <option value="2">ARTECHE</option>
            <option value="3">EPRECSA-CHUANGYIN</option>
            <option value="otro">OTRO</option>
            </select>
          </div>
          <div class="field-group" id="marca_otro_wrap" style="display:none;">
            <label for="marca_otro">MARCA (OTRO)</label>
            <input type="text" id="marca_otro" class="field-input editable" placeholder="Escriba la marca" disabled>
          </div>
          <div class="field-group">
            <label>TIPO</label>
            <input type="text" id="tipo" class="field-input editable" placeholder="Ej. ECT-24">
          </div>
          <div class="field-group">
            <label>No. SERIE</label>
            <input type="text" id="no_serie" class="field-input editable" placeholder="Número de serie">
          </div>
          <div class="field-group">
            <label>FRECUENCIA</label>
            <input type="text" id="frecuencia" class="field-input disabled" value="60 Hz" disabled>
          </div>
          <div class="field-group">
            <label>NIVEL DE AISLAMIENTO (kV)</label>
            <input type="text" id="nivel_aislamiento" class="field-input disabled" value="25.8" disabled>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION: TRANSFORMADORES DE POTENCIA -->
    <section class="form-section" id="section-tp-datos">
      <div class="section-header">
        <h2>Transformadores de Potencia (TP´s)</h2>
      </div>

      <!-- Objeto de captura TP: series, parametros y pruebas -->
      <div class="card">
        <h3 class="subsection-title">Números de Serie por Elemento</h3>
        <p class="calc-note">Captura manual por el usuario. Estos elementos se reflejan automáticamente en aislamiento, conexiones y en la sección TC.</p>
        <div class="elementos-grid">
          <div class="elemento-col">
            <div class="elemento-header">ELEMENTO 1 <span class="elem-badge">A</span></div>
            <input type="text" id="tp_serie_1" class="field-input editable" placeholder="No. Serie Elem. 1">
          </div>
          <div class="elemento-col">
            <div class="elemento-header">ELEMENTO 2 <span class="elem-badge">B</span></div>
            <input type="text" id="tp_serie_2" class="field-input editable" placeholder="No. Serie Elem. 2">
          </div>
          <div class="elemento-col">
            <div class="elemento-header">ELEMENTO 3 <span class="elem-badge">C</span></div>
            <input type="text" id="tp_serie_3" class="field-input editable" placeholder="No. Serie Elem. 3">
          </div>
        </div>

        <div class="card-grid mt-20">
          <div class="field-group">
            <label>RELACIÓN</label>
            <input type="text" id="tp_relacion" class="field-input disabled" value="14400:120V" disabled>
          </div>
          <div class="field-group">
            <label>CONSTANTE</label>
            <input type="text" id="tp_constante" class="field-input disabled" value="120" disabled>
          </div>
          <div class="field-group">
            <label>POT. MÁX</label>
            <input type="text" id="tp_pot_max" class="field-input disabled" value="500 VA" disabled>
          </div>
          <div class="field-group">
            <label>CLASE PRECISIÓN</label>
            <input type="text" id="tp_clase" class="field-input disabled" value="0.2 V" disabled>
          </div>
        </div>

        <h3 class="subsection-title mt-20">Pruebas en Vacío</h3>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>PRUEBA</th>
                <th>VOLT. PRIM.</th>
                <th>VOLT. SEC.</th>
                <th>REL. OBT.</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">1E</td>
                <td><input type="text" id="tp_vp1" class="td-input disabled" value="14400" readonly disabled></td>
                <td><input type="text" id="tp_vs1_e1" class="td-input calc" placeholder="auto" readonly></td>
                <td><input type="text" id="tp_rel1_e1" class="td-input editable" placeholder="Rel. Obtenida"></td>
              </tr>
              <tr>
                <td class="row-label">2E</td>
                <td><input type="text" id="tp_vp2" class="td-input disabled" value="14400" readonly disabled></td>
                <td><input type="text" id="tp_vs2_e1" class="td-input calc" placeholder="auto" readonly></td>
                <td><input type="text" id="tp_rel2_e1" class="td-input editable" placeholder="Rel. Obtenida"></td>
              </tr>
              <tr>
                <td class="row-label">3E</td>
                <td><input type="text" id="tp_vp3" class="td-input disabled" value="14400" readonly disabled></td>
                <td><input type="text" id="tp_vs3_e1" class="td-input calc" placeholder="auto" readonly></td>
                <td><input type="text" id="tp_rel3_e1" class="td-input editable" placeholder="Rel. Obtenida"></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="calc-note">* Volt. Sec. = Volt. Prim. / Relación Obtenida.</p>
      </div>
    </section>

    <!-- SECTION: TRANSFORMADORES DE CORRIENTE -->
    <section class="form-section" id="section-tc-datos">
      <div class="section-header">
        <h2>Transformadores de Corriente (TC´s)</h2>
      </div>

      <!-- Objeto de captura TC: series, parametros y pruebas -->
      <div class="card">
        <h3 class="subsection-title">Números de Serie por Elemento</h3>
        <p class="calc-note">Los elementos en TC se muestran automáticamente desde TP (solo lectura).</p>
        <div class="elementos-grid">
          <div class="elemento-col">
            <div class="elemento-header">ELEMENTO 1 <span class="elem-badge">A</span></div>
            <input type="text" id="tc_serie_1" class="field-input disabled" placeholder="No. Serie Elem. 1" disabled>
          </div>
          <div class="elemento-col">
            <div class="elemento-header">ELEMENTO 2 <span class="elem-badge">B</span></div>
            <input type="text" id="tc_serie_2" class="field-input disabled" placeholder="No. Serie Elem. 2" disabled>
          </div>
          <div class="elemento-col">
            <div class="elemento-header">ELEMENTO 3 <span class="elem-badge">C</span></div>
            <input type="text" id="tc_serie_3" class="field-input disabled" placeholder="No. Serie Elem. 3" disabled>
          </div>
        </div>

        <div class="card-grid mt-20">
          <div class="field-group">
            <label>RELACIÓN</label>
            <input type="text" id="tc_relacion" class="field-input disabled" placeholder="Ej. 200:5" disabled>
          </div>
          <div class="field-group">
            <label>CONSTANTE</label>
            <input type="text" id="tc_constante" class="field-input disabled" placeholder="Auto" disabled>
          </div>
          <div class="field-group">
            <label>SOBRECORRIENTE MÁX</label>
            <input type="text" id="tc_sobrecorriente" class="field-input disabled" value="10 In" disabled>
          </div>
          <div class="field-group">
            <label>CLASE PRECISIÓN</label>
            <input type="text" id="tc_clase" class="field-input disabled" value="0.2 A" disabled>
          </div>
        </div>

        <h3 class="subsection-title mt-20">Pruebas en Vacío</h3>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>PRUEBA</th>
                <th>CORR. PRIM.</th>
                <th>CORR. SEC. (Elem. 1)</th>
                <th>REL. OBT. E1</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">1E</td>
                <td><input type="text" id="tc_cp1" class="td-input editable" placeholder="Corr. Prim."></td>
                <td><input type="text" id="tc_cs1_e1" class="td-input calc" placeholder="auto" readonly></td>
                <td><input type="text" id="tc_rel1_e1" class="td-input editable" placeholder="Rel. Obtenida"></td>
              </tr>
              <tr>
                <td class="row-label">2E</td>
                <td><input type="text" id="tc_cp2" class="td-input editable" placeholder="Corr. Prim."></td>
                <td><input type="text" id="tc_cs2_e1" class="td-input calc" placeholder="auto" readonly></td>
                <td><input type="text" id="tc_rel2_e1" class="td-input editable" placeholder="Rel. Obtenida"></td>
              </tr>
              <tr>
                <td class="row-label">3E</td>
                <td><input type="text" id="tc_cp3" class="td-input editable" placeholder="Corr. Prim."></td>
                <td><input type="text" id="tc_cs3_e1" class="td-input calc" placeholder="auto" readonly></td>
                <td><input type="text" id="tc_rel3_e1" class="td-input editable" placeholder="Rel. Obtenida"></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="calc-note">* Corr. Sec. = Corr. Prim. / Relación Obtenida.</p>
      </div>
    </section>

    <!-- SECTION: DATOS DE CONEXIÓN -->
    <section class="form-section" id="section-conexion">
      <div class="section-header">
        <h2>Datos de Conexión del Equipo</h2>
      </div>

      <!-- Objeto de captura: datos de conexion para TP y TC -->
      <div class="two-col-cards">
        <div class="card">
          <h3 class="subsection-title">TP´S</h3>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr><th>ELEMENTO</th><th>Col. 1 (%)</th><th>Col. 2</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td class="row-label elemento-conexion-cell">
                    <span class="elem-conexion-nombre">Elemento 1</span>
                    <span class="elem-conexion-id" id="tp_conn_serie_1">—</span>
                  </td>
                  <td><div class="td-percent-wrap"><input type="text" id="tp_con1_a" class="td-input editable td-input-percent" inputmode="decimal" autocomplete="off" placeholder="0" aria-label="Col. 1 porcentaje elemento 1"><span class="td-percent-suffix">%</span></div></td>
                  <td><input type="text" id="tp_con1_b" class="td-input editable" placeholder="Val."></td>
                </tr>
                <tr>
                  <td class="row-label elemento-conexion-cell">
                    <span class="elem-conexion-nombre">Elemento 2</span>
                    <span class="elem-conexion-id" id="tp_conn_serie_2">—</span>
                  </td>
                  <td><div class="td-percent-wrap"><input type="text" id="tp_con2_a" class="td-input editable td-input-percent" inputmode="decimal" autocomplete="off" placeholder="0" aria-label="Col. 1 porcentaje elemento 2"><span class="td-percent-suffix">%</span></div></td>
                  <td><input type="text" id="tp_con2_b" class="td-input editable" placeholder="Val."></td>
                </tr>
                <tr>
                  <td class="row-label elemento-conexion-cell">
                    <span class="elem-conexion-nombre">Elemento 3</span>
                    <span class="elem-conexion-id" id="tp_conn_serie_3">—</span>
                  </td>
                  <td><div class="td-percent-wrap"><input type="text" id="tp_con3_a" class="td-input editable td-input-percent" inputmode="decimal" autocomplete="off" placeholder="0" aria-label="Col. 1 porcentaje elemento 3"><span class="td-percent-suffix">%</span></div></td>
                  <td><input type="text" id="tp_con3_b" class="td-input editable" placeholder="Val."></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <h3 class="subsection-title">TC´S</h3>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr><th>ELEMENTO</th><th>Col. 1 (%)</th><th>Col. 2</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td class="row-label elemento-conexion-cell">
                    <span class="elem-conexion-nombre">Elemento 1</span>
                    <span class="elem-conexion-id" id="tc_conn_serie_1">—</span>
                  </td>
                  <td><div class="td-percent-wrap"><input type="text" id="tc_con1_a" class="td-input editable td-input-percent" inputmode="decimal" autocomplete="off" placeholder="0" aria-label="Col. 1 porcentaje elemento 1"><span class="td-percent-suffix">%</span></div></td>
                  <td><input type="text" id="tc_con1_b" class="td-input editable" placeholder="Val."></td>
                </tr>
                <tr>
                  <td class="row-label elemento-conexion-cell">
                    <span class="elem-conexion-nombre">Elemento 2</span>
                    <span class="elem-conexion-id" id="tc_conn_serie_2">—</span>
                  </td>
                  <td><div class="td-percent-wrap"><input type="text" id="tc_con2_a" class="td-input editable td-input-percent" inputmode="decimal" autocomplete="off" placeholder="0" aria-label="Col. 1 porcentaje elemento 2"><span class="td-percent-suffix">%</span></div></td>
                  <td><input type="text" id="tc_con2_b" class="td-input editable" placeholder="Val."></td>
                </tr>
                <tr>
                  <td class="row-label elemento-conexion-cell">
                    <span class="elem-conexion-nombre">Elemento 3</span>
                    <span class="elem-conexion-id" id="tc_conn_serie_3">—</span>
                  </td>
                  <td><div class="td-percent-wrap"><input type="text" id="tc_con3_a" class="td-input editable td-input-percent" inputmode="decimal" autocomplete="off" placeholder="0" aria-label="Col. 1 porcentaje elemento 3"><span class="td-percent-suffix">%</span></div></td>
                  <td><input type="text" id="tc_con3_b" class="td-input editable" placeholder="Val."></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION: PRUEBA DE AISLAMIENTO -->
    <section class="form-section" id="section-aislamiento">
      <div class="section-header">
        <h2>Prueba de Aislamiento</h2>
      </div>

      <!-- TC's -->
      <!-- Objeto de captura: pruebas de aislamiento para TC -->
      <div class="card">
        <h3 class="subsection-title">PRUEBAS A TC´s</h3>
        <div class="equipo-row">
          <div class="field-group">
            <label>Equipo utilizado:</label>
            <input type="text" id="tc_equipo" class="field-input editable" placeholder="Nombre del equipo">
          </div>
        </div>
        <div class="table-wrapper mt-20">
          <table class="data-table">
            <thead>
              <tr>
                <th>ELEMENTO</th>
                <th>LÍNEA (Conexión)</th>
                <th>GUARDA</th>
                <th>TIERRA</th>
                <th>TENSIÓN</th>
                <th>ELEM. 1 (MΩ)</th>
                <th>ELEM. 2 (MΩ)</th>
                <th>ELEM. 3 (MΩ)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label elemento-conexion-cell">
                  <span class="elem-conexion-nombre">Elemento 1</span>
                  <span class="elem-conexion-id" id="tc_ais_row_label_1">—</span>
                </td>
                <td><input type="text" id="tc_ais_linea1" class="td-input disabled" value="TC(P1-P2)" disabled></td>
                <td><input type="text" id="tc_ais_guarda1" class="td-input disabled" value="AISLAMIENTO" disabled></td>
                <td><input type="text" id="tc_ais_tierra1" class="td-input disabled" value="TC(S1-S2)" disabled></td>
                <td><input type="text" id="tc_ais_tension1" class="td-input disabled" value="1,000V" disabled></td>
                <td><input type="text" id="tc_ais_e1_1" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tc_ais_e1_2" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tc_ais_e1_3" class="td-input editable" placeholder="Val."></td>
              </tr>
              <tr>
                <td class="row-label elemento-conexion-cell">
                  <span class="elem-conexion-nombre">Elemento 2</span>
                  <span class="elem-conexion-id" id="tc_ais_row_label_2">—</span>
                </td>
                <td><input type="text" id="tc_ais_linea2" class="td-input disabled" value="TC(S1-S2)" disabled></td>
                <td><input type="text" id="tc_ais_guarda2" class="td-input disabled" value="-----------" disabled></td>
                <td><input type="text" id="tc_ais_tierra2" class="td-input disabled" value="TC(P1-P2)" disabled></td>
                <td><input type="text" id="tc_ais_tension2" class="td-input disabled" value="500V" disabled></td>
                <td><input type="text" id="tc_ais_e2_1" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tc_ais_e2_2" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tc_ais_e2_3" class="td-input editable" placeholder="Val."></td>
              </tr>
              <tr>
                <td class="row-label elemento-conexion-cell">
                  <span class="elem-conexion-nombre">Elemento 3</span>
                  <span class="elem-conexion-id" id="tc_ais_row_label_3">—</span>
                </td>
                <td><input type="text" id="tc_ais_linea3" class="td-input disabled" value="TC(S1-S2)" disabled></td>
                <td><input type="text" id="tc_ais_guarda3" class="td-input disabled" value="-----------" disabled></td>
                <td><input type="text" id="tc_ais_tierra3" class="td-input disabled" value="Chasis" disabled></td>
                <td><input type="text" id="tc_ais_tension3" class="td-input disabled" value="500V" disabled></td>
                <td><input type="text" id="tc_ais_e3_1" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tc_ais_e3_2" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tc_ais_e3_3" class="td-input editable" placeholder="Val."></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TP's -->
      <!-- Objeto de captura: pruebas de aislamiento para TP -->
      <div class="card">
        <h3 class="subsection-title">PRUEBAS A TP´s</h3>
        <div class="equipo-row">
          <div class="field-group">
            <label>Equipo utilizado:</label>
            <input type="text" id="tp_equipo" class="field-input editable" placeholder="Nombre del equipo">
          </div>
        </div>
        <div class="table-wrapper mt-20">
          <table class="data-table">
            <thead>
              <tr>
                <th>ELEMENTO</th>
                <th>LÍNEA (Conexión)</th>
                <th>GUARDA</th>
                <th>TIERRA</th>
                <th>TENSIÓN</th>
                <th>ELEM. 1 (MΩ)</th>
                <th>ELEM. 2 (MΩ)</th>
                <th>ELEM. 3 (MΩ)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label elemento-conexion-cell">
                  <span class="elem-conexion-nombre">Elemento 1</span>
                  <span class="elem-conexion-id" id="tp_ais_row_label_1">—</span>
                </td>
                <td><input type="text" id="tp_ais_linea1" class="td-input disabled" value="TP(H1-H2)" disabled></td>
                <td><input type="text" id="tp_ais_guarda1" class="td-input disabled" value="AISLAMIENTO" disabled></td>
                <td><input type="text" id="tp_ais_tierra1" class="td-input disabled" value="TP(X1-X2)" disabled></td>
                <td><input type="text" id="tp_ais_tension1" class="td-input disabled" value="2,500V" disabled></td>
                <td><input type="text" id="tp_ais_e1_1" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tp_ais_e1_2" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tp_ais_e1_3" class="td-input editable" placeholder="Val."></td>
              </tr>
              <tr>
                <td class="row-label elemento-conexion-cell">
                  <span class="elem-conexion-nombre">Elemento 2</span>
                  <span class="elem-conexion-id" id="tp_ais_row_label_2">—</span>
                </td>
                <td><input type="text" id="tp_ais_linea2" class="td-input disabled" value="TP(X1-X2)" disabled></td>
                <td><input type="text" id="tp_ais_guarda2" class="td-input disabled" value="-----------" disabled></td>
                <td><input type="text" id="tp_ais_tierra2" class="td-input disabled" value="TP(H1-H2)" disabled></td>
                <td><input type="text" id="tp_ais_tension2" class="td-input disabled" value="500V" disabled></td>
                <td><input type="text" id="tp_ais_e2_1" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tp_ais_e2_2" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tp_ais_e2_3" class="td-input editable" placeholder="Val."></td>
              </tr>
              <tr>
                <td class="row-label elemento-conexion-cell">
                  <span class="elem-conexion-nombre">Elemento 3</span>
                  <span class="elem-conexion-id" id="tp_ais_row_label_3">—</span>
                </td>
                <td><input type="text" id="tp_ais_linea3" class="td-input disabled" value="TP(X1-X2)" disabled></td>
                <td><input type="text" id="tp_ais_guarda3" class="td-input disabled" value="-----------" disabled></td>
                <td><input type="text" id="tp_ais_tierra3" class="td-input disabled" value="Chasis" disabled></td>
                <td><input type="text" id="tp_ais_tension3" class="td-input disabled" value="500V" disabled></td>
                <td><input type="text" id="tp_ais_e3_1" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tp_ais_e3_2" class="td-input editable" placeholder="Val."></td>
                <td><input type="text" id="tp_ais_e3_3" class="td-input editable" placeholder="Val."></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- SECTION: FIRMAS -->
    <section class="form-section" id="section-firmas">
      <div class="section-header">
        <h2>Firmas y Responsables</h2>
      </div>

      <!-- Objeto de captura: responsables (realizo, reviso y recibe) -->
      <div class="firmas-grid">
        <div class="card firma-card">
          <h3 class="subsection-title">Realizó</h3>
          <div class="field-group">
            <label>Nombre</label>
            <input type="text" id="realizo_nom" class="field-input disabled" placeholder="Nombre completo" disabled>
          </div>
          <div class="field-group">
            <label>RPE</label>
            <input type="text" id="realizo_rpe" class="field-input disabled" placeholder="Clave RPE" maxlength="5" disabled>
            <small class="rpe-feedback" id="realizo_rpe_feedback"></small>
          </div>
          <div class="field-group">
            <label>Área</label>
            <input type="text" id="realizo_area" class="field-input disabled" value="PREPARACION DE MEDIDORES" readonly disabled>
          </div>
          <div class="field-group">
            <label>Fecha</label>
            <input type="date" id="realizo_fecha" class="field-input disabled" disabled>
          </div>
        </div>

        <div class="card firma-card">
          <h3 class="subsection-title">Revisó</h3>
          <div class="field-group">
            <label>Nombre</label>
            <input type="text" id="reviso_nom" class="field-input editable" placeholder="Nombre completo">
          </div>
          <div class="field-group">
            <label>RPE</label>
            <input type="text" id="reviso_rpe" class="field-input editable" placeholder="Clave RPE" maxlength="5">
            <small class="rpe-feedback" id="reviso_rpe_feedback"></small>
          </div>
          <div class="field-group">
            <label>Área</label>
            <input type="text" id="reviso_area" class="field-input disabled" value="PREPARACION DE MEDIDORES" readonly disabled>
          </div>
          <div class="field-group">
            <label>Fecha</label>
            <input type="date" id="reviso_fecha" class="field-input editable">
          </div>
        </div>

        <div class="card firma-card">
          <h3 class="subsection-title">Recibe</h3>
          <div class="field-group">
            <label>Nombre</label>
            <input type="text" id="recibe_nom" class="field-input editable" placeholder="Nombre completo">
          </div>
          <div class="field-group">
            <label>RPE</label>
            <input type="text" id="recibe_rpe" class="field-input editable" placeholder="Clave RPE" maxlength="5">
            <small class="rpe-feedback" id="recibe_rpe_feedback"></small>
          </div>
          <div class="field-group">
            <label>Área</label>
            <input type="text" id="recibe_area" class="field-input disabled" value="PREPARACION DE MEDIDORES" readonly disabled>
          </div>
          <div class="field-group">
            <label>Fecha</label>
            <input type="date" id="recibe_fecha" class="field-input editable">
          </div>
        </div>
      </div>

      <!-- Acciones finales: guardar evaluacion y generar reporte -->
      <div class="btn-generate-wrapper">
        <button class="btn-generate-pdf btn-save-eval" id="submitApprovalBtn" type="button" style="display:none;">
          <span>ENVIAR A APROBACIÓN</span>
        </button>
        <button class="btn-generate-pdf" id="approveReportBtn" type="button" style="display:none;">
          <span>APROBAR REPORTE</span>
        </button>
        <button class="btn-generate-pdf btn-save-eval" id="saveEvaluationBtn" type="button">
          <span>GUARDAR REPORTE</span>
        </button>
        <button class="btn-generate-pdf" onclick="generarPDF()">
          <span>GENERAR REPORTE PDF</span>
        </button>
      </div>
    </section>

    <!-- BOTTOM NAV -->
    <div class="bottom-nav">
      <button class="btn-nav" id="btn-prev" onclick="navStep(-1)">← Anterior</button>
      <span class="step-indicator" id="stepIndicator">1 / 6</span>
      <button class="btn-nav" id="btn-next" onclick="navStep(1)">Siguiente →</button>
    </div>
  </main>
</div>

<!-- TOAST NOTIFICATION -->
<div class="toast" id="toast"></div>

<script src="js/app.js?v=<?php echo filemtime(__DIR__ . '/js/app.js'); ?>"></script>
</body>
</html>