<?php
// API interna para utilidades de administración de usuarios.
// Incluye proxy controlado para consulta por RPE (evita CORS desde el navegador).
// Retorna JSON.

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
    if ($rpe === '' || !preg_match('/^[A-Z0-9]{1,8}$/', $rpe)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'RPE inválido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $url = 'http://10.4.157.20/api/consulta/' . rawurlencode($rpe);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 4,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || trim($raw) === '') {
        http_response_code(502);
        echo json_encode(['ok' => false, 'message' => 'No se pudo consultar el servicio.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(502);
        echo json_encode(['ok' => false, 'message' => 'Respuesta inválida del servicio.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $codigo = (string)($data['CodigoMensaje'] ?? '');
    if ($codigo !== '' && $codigo !== '0') {
        $msg = (string)($data['Mensaje'] ?? 'Consulta no válida.');
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nomina = is_array($data['Nomina'] ?? null) ? $data['Nomina'] : [];
    $email = (string)($nomina['EMail'] ?? ($data['Email'] ?? ''));
    $puesto = (string)($nomina['Puesto'] ?? '');
    $nombreCompleto = (string)($nomina['Nombre'] ?? ($data['NombreCompleto'] ?? ''));
    $nombre = trim($nombreCompleto) !== '' ? $nombreCompleto : trim(((string)($data['Nombre'] ?? '')) . ' ' . ((string)($data['Apellidos'] ?? '')));

    echo json_encode([
        'ok' => true,
        'item' => [
            'rpe' => $rpe,
            'nombre' => trim($nombre),
            'email' => trim($email),
            'cargo' => trim($puesto),
            'habilitado' => (string)($data['Habilitado'] ?? ''),
            'bloqueado' => (string)($data['Bloqueado'] ?? ''),
        ],
        'raw' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Acción no soportada.'], JSON_UNESCAPED_UNICODE);

