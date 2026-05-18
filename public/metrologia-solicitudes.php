<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccessRoute($user, basename($_SERVER['PHP_SELF'] ?? 'metrologia-solicitudes.php'))) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología']];
    $currentUser = $user;
    ob_start(); ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder.</div>
    <?php $content = ob_get_clean(); include $base . '/views/layout.php'; exit;
}

$solRepo    = $container['repositories']['met_solicitud'];
$expRepo    = $container['repositories']['met_expediente'];
$folioSvc   = $container['MetrologiaFolioService'];
$metHistory = $container['MetrologiaHistoryService'];
$catalogos  = $container['metrologia_catalogos'] ?? [];

$zonas = $catalogos['zonas'] ?? [];
$areas = $catalogos['areas'] ?? [];

// Determinar si es usuario de zona y su prefijo/datos
$isZonaUser   = $metPerm->isZonaUser($user);
$userZonaId   = (string)($user['zona_id'] ?? '');
$userZonaNombre = (string)($user['zona_nombre'] ?? '');
$userZonaPrefijo = $metPerm->getZonaPrefijo($user);
$userAreaId   = (string)($user['area_id'] ?? '');

// Para usuarios de zona: bloquear acceso si no tiene zona configurada
if ($isZonaUser && $userZonaId === '') {
    $pageTitle = 'Configuración incompleta';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Solicitudes']];
    $currentUser = $user;
    ob_start(); ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>
        Su usuario no tiene zona asignada. Contacte al administrador.
    </div>
    <?php $content = ob_get_clean(); include $base . '/views/layout.php'; exit;
}

$error   = '';
$success = '';
$anio    = (int)date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_solicitud') {
        $autoFolio = true; // siempre auto desde la vista nueva
        $folio = $folioSvc->normalize((string)($_POST['folio'] ?? ''));
        if ($autoFolio || $folio === '') {
            $folio = $solRepo->nextFolio($anio);
        }

        $serie    = strtoupper(trim((string)($_POST['no_serie'] ?? '')));
        $marca    = strtoupper(trim((string)($_POST['marca'] ?? '')));
        $modelo   = strtoupper(trim((string)($_POST['modelo'] ?? '')));
        $descEq   = strtoupper(trim((string)($_POST['descripcion_equipo'] ?? '')));
        $obs      = trim((string)($_POST['observaciones'] ?? ''));

        // Zona y área: forzadas para usuarios de zona, elegibles para laboratorio
        if ($isZonaUser) {
            $zonaId = $userZonaId;
            $areaId = $userAreaId !== '' ? $userAreaId : trim((string)($_POST['area_id'] ?? ''));
        } else {
            $zonaId = trim((string)($_POST['zona_id'] ?? ''));
            $areaId = trim((string)($_POST['area_id'] ?? ''));
        }

        $fechaSolicitud = date('Y-m-d');

        if ($serie === '' || $zonaId === '' || $areaId === '') {
            $error = 'No. serie, zona y área son obligatorios.';
        } elseif ($folioSvc->isDuplicate($folio)) {
            $error = 'Error al generar folio (duplicado). Intente de nuevo.';
        } else {
            // Buscar en bitácora para auto-rellenar descripción/marca/modelo si están vacíos
            $bitacoraRepo = $container['repositories']['met_bitacora_equipos'];
            $equipoBitacora = $bitacoraRepo->findBySerie($serie);
            $eb = !empty($equipoBitacora) ? $equipoBitacora[0] : [];
            if ($descEq === '' && !empty($eb['descripcion'])) $descEq = strtoupper($eb['descripcion']);
            if ($marca  === '' && !empty($eb['marca']))       $marca  = strtoupper($eb['marca']);
            if ($modelo === '' && !empty($eb['modelo']))      $modelo = strtoupper($eb['modelo']);

            $sol = $solRepo->save([
                'folio'           => $folio,
                'descripcion'     => $descEq,
                'marca'           => $marca,
                'modelo'          => $modelo,
                'no_serie'        => $serie,
                'zona_id'         => $zonaId,
                'area_id'         => $areaId,
                'oficina_id'      => 'of-metro',
                'programa_anual'  => false,
                'fecha_programada'=> null,
                'fecha_solicitud' => $fechaSolicitud,
                'vigencia_esperada' => null,
                'estado'          => 'por_entregar_a_laboratorio',
                'observaciones'   => $obs,
                'creado_por'      => $user['id'] ?? '',
                'bitacora_id'     => $eb['id'] ?? null,
            ]);

            // Actualizar bitácora: marcar estado y agregar folio de solicitud
            if (!empty($eb['id'])) {
                $eb['estado_solicitud'] = 'solicitud_enviada';
                $eb['folio_solicitud']  = $folio;
                $eb['solicitud_id']     = $sol['id'];
                $bitacoraRepo->save($eb);
            }

            $metHistory->log(null, $user['id'] ?? '', 'solicitud_creada', 'Solicitud registrada desde zona.', [
                'folio'        => $sol['folio'],
                'solicitud_id' => $sol['id'],
                'zona_id'      => $zonaId,
                'no_serie'     => $serie,
            ]);
            $success = 'Solicitud registrada con folio <strong>' . htmlspecialchars($folio) . '</strong>.';
        }

    } elseif ($action === 'crear_expediente_desde_solicitud' && !$isZonaUser) {
        if (!$metPerm->canManage($user)) {
            $error = 'No tiene permisos para convertir a expediente.';
        } else {
            $sid = (string)($_POST['solicitud_id'] ?? '');
            $sol = $solRepo->find($sid);
            if (!$sol) {
                $error = 'Solicitud no encontrada.';
            } else {
                $estadoExp = !empty($_POST['validada']) ? 'solicitud_validada' : 'solicitud_recibida';
                $e = $expRepo->save([
                    'folio'             => $sol['folio'] ?? '',
                    'descripcion'       => $sol['descripcion'] ?? '',
                    'marca'             => $sol['marca'] ?? '',
                    'modelo'            => $sol['modelo'] ?? '',
                    'no_serie'          => $sol['no_serie'] ?? '',
                    'zona_id'           => $sol['zona_id'] ?? '',
                    'area_id'           => $sol['area_id'] ?? '',
                    'oficina_id'        => $sol['oficina_id'] ?? 'of-metro',
                    'programa_anual'    => false,
                    'fecha_programada'  => null,
                    'fecha_solicitud'   => $sol['fecha_solicitud'] ?? date('Y-m-d'),
                    'estado_expediente' => $estadoExp,
                    'observaciones'     => $sol['observaciones'] ?? '',
                    'vigencia_esperada' => null,
                    'solicitud_id'      => $sol['id'],
                    'tecnico_rpe'       => '',
                ]);
                $sol['expediente_id'] = $e['id'];
                $sol['estado'] = 'convertida_a_expediente';
                $solRepo->save($sol);
                $metHistory->log($e['id'], $user['id'] ?? '', 'expediente_creado', 'Expediente creado desde solicitud.', [
                    'folio' => $e['folio'], 'solicitud_id' => $sol['id'],
                ]);
                header('Location: ' . $basePath . '/metrologia-expediente.php?id=' . rawurlencode($e['id']));
                exit;
            }
        }
    }
}

// Listado filtrado: usuarios de zona solo ven su zona
$filters = ['anio' => $anio];
if ($isZonaUser) {
    $filters['zona_id'] = $userZonaId;
}
$zonaFiltro   = trim((string)($_GET['zona_id'] ?? ''));
$estadoFiltro = trim((string)($_GET['estado'] ?? ''));
if (!$isZonaUser && $zonaFiltro !== '')   $filters['zona_id'] = $zonaFiltro;
if ($estadoFiltro !== '')                  $filters['estado']   = $estadoFiltro;
$solicitudes = $solRepo->findByFilters($filters);

// Datos para la vista
$nextFolioSugerido = $solRepo->nextFolio($anio);

$pageTitle = $isZonaUser ? 'Solicitud de calibración' : 'Metrología — Solicitudes';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => '/dashboard.php'],
    ['label' => 'Metrología'],
    ['label' => 'Solicitudes'],
];
$currentUser = $user;
ob_start();
include $base . '/views/metrologia/solicitudes.php';
$content = ob_get_clean();
include $base . '/views/layout.php';
