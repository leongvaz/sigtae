<?php
// API JSON para catálogo maestro de equipos (sugerencias y CRUD).

$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

header('Content-Type: application/json; charset=utf-8');

$metPerm = $container['MetrologiaPermissionService'];
$seed = $container['MetrologiaEquipoCatalogoSeedService'];
$repo = $container['repositories']['met_equipo_catalogo'];

// Asegura que exista la base maestra.
if ($seed) {
    $seed->ensureSeeded();
}

$action = (string)($_GET['action'] ?? '');

if ($action === 'suggest') {
    $field = (string)($_GET['field'] ?? '');
    $q = (string)($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 12);
    $limit = max(1, min(50, $limit));
    echo json_encode(['ok' => true, 'items' => $repo->findSuggestions($field, $q, $limit)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get') {
    $id = (string)($_GET['id'] ?? '');
    $it = $id !== '' ? $repo->find($id) : null;
    if (!$it) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Equipo no encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'item' => $it], JSON_UNESCAPED_UNICODE);
    exit;
}

// CRUD requiere permisos de administración de catálogo.
if (!$metPerm->canAdminEquiposCatalogo($user)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) $data = [];

    $entity = [
        'id' => (string)($data['id'] ?? ''),
        'folio' => trim((string)($data['folio'] ?? '')),
        'no_serie' => strtoupper(trim((string)($data['no_serie'] ?? ''))),
        'marca' => trim((string)($data['marca'] ?? '')),
        'modelo' => trim((string)($data['modelo'] ?? '')),
        'descripcion' => trim((string)($data['descripcion'] ?? '')),
        'zona' => trim((string)($data['zona'] ?? '')),
        'area' => trim((string)($data['area'] ?? '')),
        'oficina' => trim((string)($data['oficina'] ?? '')),
    ];

    // Validación mínima
    if ($entity['no_serie'] === '' && $entity['folio'] === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Capture al menos No. serie o Folio.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($entity['marca'] === '' || $entity['modelo'] === '' || $entity['descripcion'] === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Marca, modelo y descripción son obligatorios.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $saved = $repo->save($entity);
    echo json_encode(['ok' => true, 'item' => $saved], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    $id = is_array($data) ? (string)($data['id'] ?? '') : '';
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'ID requerido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ok = $repo->delete($id);
    if (!$ok) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Equipo no encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);

