<?php
// API JSON para consulta/edición inline (modal) de Bitácora.

$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

header('Content-Type: application/json; charset=utf-8');

$metPerm = $container['MetrologiaPermissionService'];

$bitRepo = $container['repositories']['met_bitacora_equipos'];
$equipRepo = $container['repositories']['met_equipo_catalogo'] ?? null;
$seed = $container['MetrologiaEquipoCatalogoSeedService'] ?? null;
if ($seed) $seed->ensureSeeded();

$action = (string)($_GET['action'] ?? '');

// Permisos:
// - detail: cualquier usuario con acceso a Metrología
// - update: admins del sistema o Alba (RPE)
$canAccess = $metPerm->canAccess($user);
$canEdit = !empty($user['es_super_admin']) || strtoupper(trim((string)($user['rpe'] ?? ''))) === 'G46B8';

if ($action === 'detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$canAccess) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $id = (string)($_GET['id'] ?? '');
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'ID requerido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $row = $bitRepo->find($id);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Registro no encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Enriquecimiento: si existe el JSON del Programa 2026, intenta completar campos faltantes.
    // Match por serie (si existe), si no por folio.
    $storagePath = $container['config']['storage_path'] ?? ($base . '/storage/json');
    $programPath = rtrim((string)$storagePath, "\\/") . DIRECTORY_SEPARATOR . 'metrologia_programa_2026.json';
    if (is_file($programPath)) {
        $programa = json_decode((string)@file_get_contents($programPath), true);
        if (is_array($programa)) {
            $serie = strtoupper(trim((string)($row['no_serie'] ?? '')));
            $folio = trim((string)($row['folio'] ?? ''));
            $hit = null;
            foreach ($programa as $p) {
                if (!is_array($p)) continue;
                $pSerie = strtoupper(trim((string)($p['no_serie'] ?? '')));
                if ($serie !== '' && $pSerie === $serie) { $hit = $p; break; }
                if ($serie === '' && $folio !== '' && (string)($p['folio'] ?? '') === $folio) { $hit = $p; break; }
            }
            if (is_array($hit)) {
                // Solo completa si no existe o viene vacío en bitácora.
                $fillIfEmpty = function(string $key) use (&$row, $hit): void {
                    $cur = trim((string)($row[$key] ?? ''));
                    if ($cur !== '') return;
                    $val = (string)($hit[$key] ?? '');
                    if (trim($val) === '') return;
                    $row[$key] = $val;
                };
                foreach ([
                    'programa_anual',
                    'nomenclatura_gmcs',
                    'jefe_area',
                    'rpe_jefe_area',
                    'tecnico',
                    'recibido',
                    'fecha_calibracion_baja',
                    'evaluacion_conformidad',
                    'fecha_impresion',
                    'fecha_entrega_informe_escaneado',
                    'entregado',
                    'nombre_a_quien_se_entrega',
                    'fecha_programada',
                    'tablero_evolutivo',
                ] as $k) $fillIfEmpty($k);
            }
        }
    }

    echo json_encode(['ok' => true, 'item' => $row], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action !== 'update' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$canEdit) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) $data = [];

$id = (string)($data['id'] ?? '');
if ($id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'ID requerido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = $bitRepo->find($id);
if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Registro no encontrado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Campos editables en bitácora
$row['folio'] = trim((string)($data['folio'] ?? ($row['folio'] ?? '')));
$row['no_serie'] = strtoupper(trim((string)($data['no_serie'] ?? ($row['no_serie'] ?? ''))));
$row['marca'] = trim((string)($data['marca'] ?? ($row['marca'] ?? '')));
$row['modelo'] = trim((string)($data['modelo'] ?? ($row['modelo'] ?? '')));
$row['descripcion'] = trim((string)($data['descripcion'] ?? ($row['descripcion'] ?? '')));
$row['zona'] = trim((string)($data['zona'] ?? ($row['zona'] ?? '')));
$row['area'] = trim((string)($data['area'] ?? ($row['area'] ?? '')));
$row['oficina'] = trim((string)($data['oficina'] ?? ($row['oficina'] ?? '')));
$row['observaciones'] = trim((string)($data['observaciones'] ?? ($row['observaciones'] ?? '')));

// Campos extra (Programa / trazabilidad)
$row['programa_anual'] = trim((string)($data['programa_anual'] ?? ($row['programa_anual'] ?? '')));
$row['recibido'] = trim((string)($data['recibido'] ?? ($row['recibido'] ?? '')));
$row['tecnico'] = trim((string)($data['tecnico'] ?? ($row['tecnico'] ?? '')));
$row['fecha_calibracion_baja'] = trim((string)($data['fecha_calibracion_baja'] ?? ($row['fecha_calibracion_baja'] ?? '')));
$row['evaluacion_conformidad'] = trim((string)($data['evaluacion_conformidad'] ?? ($row['evaluacion_conformidad'] ?? '')));
$row['fecha_impresion'] = trim((string)($data['fecha_impresion'] ?? ($row['fecha_impresion'] ?? '')));
$row['fecha_entrega_informe_escaneado'] = trim((string)($data['fecha_entrega_informe_escaneado'] ?? ($row['fecha_entrega_informe_escaneado'] ?? '')));
$row['entregado'] = trim((string)($data['entregado'] ?? ($row['entregado'] ?? '')));
$row['nombre_a_quien_se_entrega'] = trim((string)($data['nombre_a_quien_se_entrega'] ?? ($row['nombre_a_quien_se_entrega'] ?? '')));
$row['nomenclatura_gmcs'] = trim((string)($data['nomenclatura_gmcs'] ?? ($row['nomenclatura_gmcs'] ?? '')));
$row['jefe_area'] = trim((string)($data['jefe_area'] ?? ($row['jefe_area'] ?? '')));
$row['rpe_jefe_area'] = strtoupper(trim((string)($data['rpe_jefe_area'] ?? ($row['rpe_jefe_area'] ?? ''))));
$row['fecha_programada'] = trim((string)($data['fecha_programada'] ?? ($row['fecha_programada'] ?? '')));
$row['tablero_evolutivo'] = trim((string)($data['tablero_evolutivo'] ?? ($row['tablero_evolutivo'] ?? '')));

if ($row['marca'] === '' || $row['modelo'] === '' || $row['descripcion'] === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Marca, modelo y descripción son obligatorios.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$saved = $bitRepo->save($row);

// Sincroniza en catálogo maestro (best-effort): por serie si existe, si no por folio.
if ($equipRepo) {
    $all = $equipRepo->findAll();
    $target = null;
    $serie = strtoupper(trim((string)($saved['no_serie'] ?? '')));
    $folio = trim((string)($saved['folio'] ?? ''));
    foreach ($all as $e) {
        $eSerie = strtoupper(trim((string)($e['no_serie'] ?? '')));
        if ($serie !== '' && $eSerie === $serie) { $target = $e; break; }
        if ($serie === '' && $folio !== '' && (string)($e['folio'] ?? '') === $folio) { $target = $e; break; }
    }
    $entity = $target ?: [];
    $entity['id'] = $entity['id'] ?? '';
    $entity['folio'] = $folio;
    $entity['no_serie'] = $serie;
    $entity['marca'] = (string)($saved['marca'] ?? '');
    $entity['modelo'] = (string)($saved['modelo'] ?? '');
    $entity['descripcion'] = (string)($saved['descripcion'] ?? '');
    $entity['zona'] = (string)($saved['zona'] ?? '');
    $entity['area'] = (string)($saved['area'] ?? '');
    $entity['oficina'] = (string)($saved['oficina'] ?? '');
    $equipRepo->save($entity);
}

echo json_encode(['ok' => true, 'item' => $saved], JSON_UNESCAPED_UNICODE);

