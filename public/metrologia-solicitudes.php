<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccess($user)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Metrología';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Solicitudes']];
    $currentUser = $user;
    ob_start(); ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos.</div>
    <?php $content = ob_get_clean(); include $base . '/views/layout.php'; exit;
}

$solRepo = $container['repositories']['met_solicitud'];
$expRepo = $container['repositories']['met_expediente'];
$folioSvc = $container['MetrologiaFolioService'];
$metHistory = $container['MetrologiaHistoryService'];
$catalogos = $container['metrologia_catalogos'] ?? [];

$zonas = $catalogos['zonas'] ?? [];
$areas = $catalogos['areas'] ?? [];

$error = '';
$success = '';

// Alta rápida de solicitud (módulo real “solicitudes recibidas”)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'crear_solicitud') {
        $anio = (int)($_POST['anio'] ?? date('Y'));
        $folio = $folioSvc->normalize((string)($_POST['folio'] ?? ''));
        $autoFolio = !empty($_POST['auto_folio']);
        if ($autoFolio || $folio === '') {
            $folio = $solRepo->nextFolio($anio);
        }
        $desc = trim((string)($_POST['descripcion'] ?? ''));
        $serie = trim((string)($_POST['no_serie'] ?? ''));
        $marca = trim((string)($_POST['marca'] ?? ''));
        $modelo = trim((string)($_POST['modelo'] ?? ''));
        $zonaId = trim((string)($_POST['zona_id'] ?? ''));
        $areaId = trim((string)($_POST['area_id'] ?? ''));
        $oficinaId = 'of-metro';
        $programaAnual = !empty($_POST['programa_anual']);
        $fechaSolicitud = trim((string)($_POST['fecha_solicitud'] ?? date('Y-m-d')));
        $fechaProgramada = trim((string)($_POST['fecha_programada'] ?? ''));
        $vigencia = trim((string)($_POST['vigencia_esperada'] ?? ''));
        $obs = trim((string)($_POST['observaciones'] ?? ''));

        if ($desc === '' || $serie === '' || $zonaId === '' || $areaId === '') {
            $error = 'Complete descripción, no. serie, zona y área.';
        } elseif ($folioSvc->isDuplicate($folio)) {
            $error = 'El folio ya existe (duplicado). Corrija o capture otro.';
        } else {
            $sol = $solRepo->save([
                'folio' => $folio,
                'descripcion' => $desc,
                'marca' => $marca,
                'modelo' => $modelo,
                'no_serie' => $serie,
                'zona_id' => $zonaId,
                'area_id' => $areaId,
                'oficina_id' => $oficinaId,
                'programa_anual' => $programaAnual,
                'fecha_programada' => $fechaProgramada !== '' ? $fechaProgramada : null,
                'fecha_solicitud' => $fechaSolicitud,
                'vigencia_esperada' => $vigencia !== '' ? $vigencia : null,
                'estado' => 'solicitud_recibida',
                'observaciones' => $obs,
                'creado_por' => $user['id'] ?? '',
            ]);
            $metHistory->log(null, $user['id'] ?? '', 'solicitud_creada', 'Solicitud recibida registrada.', [
                'folio' => $sol['folio'],
                'solicitud_id' => $sol['id'],
            ]);
            $success = 'Solicitud registrada.';
        }
    } elseif ($action === 'crear_expediente_desde_solicitud') {
        if (!$metPerm->canManage($user)) {
            $error = 'No tiene permisos para convertir a expediente.';
        } else {
            $sid = (string)($_POST['solicitud_id'] ?? '');
            $sol = $solRepo->find($sid);
            if (!$sol) {
                $error = 'Solicitud no encontrada.';
            } else {
                // Crea expediente con estado solicitud_recibida o solicitud_validada según check
                $estadoExp = !empty($_POST['validada']) ? 'solicitud_validada' : 'solicitud_recibida';
                $e = $expRepo->save([
                    'folio' => $sol['folio'] ?? '',
                    'descripcion' => $sol['descripcion'] ?? '',
                    'marca' => $sol['marca'] ?? '',
                    'modelo' => $sol['modelo'] ?? '',
                    'no_serie' => $sol['no_serie'] ?? '',
                    'zona_id' => $sol['zona_id'] ?? '',
                    'area_id' => $sol['area_id'] ?? '',
                    'oficina_id' => $sol['oficina_id'] ?? 'of-metro',
                    'programa_anual' => !empty($sol['programa_anual']),
                    'fecha_programada' => $sol['fecha_programada'] ?? null,
                    'fecha_solicitud' => $sol['fecha_solicitud'] ?? date('Y-m-d'),
                    'estado_expediente' => $estadoExp,
                    'observaciones' => $sol['observaciones'] ?? '',
                    'vigencia_esperada' => $sol['vigencia_esperada'] ?? null,
                    'solicitud_id' => $sol['id'],
                    'tecnico_rpe' => '',
                ]);
                $sol['expediente_id'] = $e['id'];
                $sol['estado'] = 'convertida_a_expediente';
                $solRepo->save($sol);
                $metHistory->log($e['id'], $user['id'] ?? '', 'expediente_creado', 'Expediente creado desde solicitud.', [
                    'folio' => $e['folio'],
                    'solicitud_id' => $sol['id'],
                ]);
                header('Location: ' . $basePath . '/metrologia-expediente.php?id=' . rawurlencode($e['id']));
                exit;
            }
        }
    }
}

// Listado + filtros básicos
$anio = (int)($_GET['anio'] ?? date('Y'));
$zonaId = trim((string)($_GET['zona_id'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));
$filters = ['anio' => $anio];
if ($zonaId !== '') $filters['zona_id'] = $zonaId;
if ($estado !== '') $filters['estado'] = $estado;
$solicitudes = $solRepo->findByFilters($filters);

$pageTitle = 'Metrología — Solicitudes';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Solicitudes']];
$currentUser = $user;
ob_start();
include $base . '/views/metrologia/solicitudes.php';
$content = ob_get_clean();
include $base . '/views/layout.php';

