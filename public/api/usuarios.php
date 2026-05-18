<?php
// API interna para utilidades de administración de usuarios.
// Incluye proxy controlado para consulta por RPE (evita CORS desde el navegador).
// Retorna JSON.

use App\Services\RpeDirectoryLookupService;
use App\Services\UserAdminGuard;

$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

header('Content-Type: application/json; charset=utf-8');

if (!UserAdminGuard::canAccessUserAdmin($user)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = (string)($_GET['action'] ?? '');

if ($action === 'lookup_rpe') {
    $rpe = strtoupper(trim((string)($_GET['rpe'] ?? '')));
    $lookup = new RpeDirectoryLookupService();
    $result = $lookup->lookup($rpe);

    if (empty($result['ok'])) {
        $status = (int)($result['http_status'] ?? 400);
        http_response_code($status > 0 ? $status : 400);
        echo json_encode([
            'ok' => false,
            'message' => (string)($result['message'] ?? 'Error en la consulta.'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'item' => $result['item'] ?? [],
        'raw' => $result['raw'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);
