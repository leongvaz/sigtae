<?php
// API mínima (JSON) para modales interactivos del dashboard.
// Sin framework: valida sesión y retorna listas filtradas.

$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

header('Content-Type: application/json; charset=utf-8');

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$officeRepo = $container['repositories']['office'];

$action = $_GET['action'] ?? '';
if ($action === 'by_estado' || $action === 'by_estados') {
    $estado = (string)($_GET['estado'] ?? '');
    $estados = (string)($_GET['estados'] ?? '');
    $allowedEstados = [];
    if ($action === 'by_estado' && $estado !== '') {
        $allowedEstados = [$estado];
    } elseif ($action === 'by_estados' && $estados !== '') {
        $allowedEstados = array_values(array_filter(array_map('trim', explode(',', $estados))));
    }
    $all = $taskRepo->findAll();
    $withState = [];
    foreach ($all as $t) $withState[] = $stateService->computeState($t);

    $filtered = array_values(array_filter($withState, fn($t) => in_array(($t['estado'] ?? ''), $allowedEstados, true)));
    // respuesta compacta
    $out = array_map(fn($t) => [
        'id' => $t['id'] ?? '',
        'folio' => $t['folio'] ?? '',
        'titulo' => $t['titulo'] ?? '',
        'fecha_limite' => $t['fecha_limite'] ?? '',
        'estado' => $t['estado'] ?? '',
        'prioridad' => $t['prioridad'] ?? '',
        'oficina_id' => $t['oficina_id'] ?? '',
    ], $filtered);
    echo json_encode(['ok' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'by_prioridad') {
    $prioridad = (string)($_GET['prioridad'] ?? '');
    $all = $taskRepo->findAll();
    $withState = [];
    foreach ($all as $t) $withState[] = $stateService->computeState($t);
    $filtered = array_values(array_filter($withState, fn($t) => ($t['prioridad'] ?? '') === $prioridad));
    $out = array_map(fn($t) => [
        'id' => $t['id'] ?? '',
        'folio' => $t['folio'] ?? '',
        'titulo' => $t['titulo'] ?? '',
        'fecha_limite' => $t['fecha_limite'] ?? '',
        'estado' => $t['estado'] ?? '',
        'prioridad' => $t['prioridad'] ?? '',
        'oficina_id' => $t['oficina_id'] ?? '',
    ], $filtered);
    echo json_encode(['ok' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'by_oficina') {
    $oficinaId = (string)($_GET['oficina_id'] ?? '');
    $all = $taskRepo->findAll();
    $withState = [];
    foreach ($all as $t) $withState[] = $stateService->computeState($t);
    $filtered = array_values(array_filter($withState, fn($t) => ($t['oficina_id'] ?? '') === $oficinaId));
    $out = array_map(fn($t) => [
        'id' => $t['id'] ?? '',
        'folio' => $t['folio'] ?? '',
        'titulo' => $t['titulo'] ?? '',
        'fecha_limite' => $t['fecha_limite'] ?? '',
        'estado' => $t['estado'] ?? '',
        'prioridad' => $t['prioridad'] ?? '',
        'oficina_id' => $t['oficina_id'] ?? '',
    ], $filtered);
    echo json_encode(['ok' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'by_responsable') {
    $rid = (string)($_GET['responsable_id'] ?? '');
    $all = $taskRepo->findAll();
    $withState = [];
    foreach ($all as $t) $withState[] = $stateService->computeState($t);
    $filtered = array_values(array_filter($withState, fn($t) => ($t['responsable_id'] ?? '') === $rid));
    $out = array_map(fn($t) => [
        'id' => $t['id'] ?? '',
        'folio' => $t['folio'] ?? '',
        'titulo' => $t['titulo'] ?? '',
        'fecha_limite' => $t['fecha_limite'] ?? '',
        'estado' => $t['estado'] ?? '',
        'prioridad' => $t['prioridad'] ?? '',
        'oficina_id' => $t['oficina_id'] ?? '',
    ], $filtered);
    echo json_encode(['ok' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);

