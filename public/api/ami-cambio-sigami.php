<?php
// API JSON: cambio de estado SIGAMI de medidores (SQL Server).

$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

header('Content-Type: application/json; charset=utf-8');

if (!\App\Services\AmiGuard::canAccess($user)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$estado = isset($input['estado']) ? (int)$input['estado'] : 0;
$medidores = isset($input['medidores']) && is_array($input['medidores']) ? $input['medidores'] : [];
$medidores = array_values(array_filter(array_map(function ($m) {
    $m = strtoupper(trim((string)$m));
    return $m !== '' ? $m : null;
}, $medidores)));

if (!in_array($estado, [2,3,4,6,7], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Estado inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (count($medidores) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No hay medidores para actualizar.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('sqlsrv_connect')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Extensión sqlsrv no disponible en este servidor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = \App\Services\AmiKcentinelConnection::connect();
if (!$conn) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'message' => 'Error de conexión a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$totalActualizados = 0;
$chunks = array_chunk($medidores, 1000);

foreach ($chunks as $grupo) {
    $placeholders = implode(",", array_fill(0, count($grupo), "?"));
    $query = "UPDATE [kcentinel].[dbo].[TELEPNUEVOMEDIDOR] SET [tlpnIdSigAmi] = ? WHERE [tlpnMedidor] IN ($placeholders)";
    $params = array_merge([$estado], $grupo);
    $stmt = (call_user_func('sqlsrv_query', $conn, $query, $params));
    if ($stmt === false) {
        $errs = call_user_func('sqlsrv_errors');
        call_user_func('sqlsrv_close', $conn);
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Error en la consulta SQL.', 'detail' => $errs], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $aff = (call_user_func('sqlsrv_rows_affected', $stmt));
    if (is_int($aff) && $aff > 0) $totalActualizados += $aff;
}

call_user_func('sqlsrv_close', $conn);

echo json_encode([
    'ok' => true,
    'updated' => $totalActualizados,
    'message' => "Se actualizaron $totalActualizados medidores."
], JSON_UNESCAPED_UNICODE);

