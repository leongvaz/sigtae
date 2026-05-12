<?php
/**
 * API JSON / CSV: consulta tlpnIdSigAmi por lista de medidores (kcentinel.dbo.telepnuevomedidor).
 */

$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

if (!\App\Services\AmiGuard::canAccess($user)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'No autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$format = isset($input['format']) ? strtolower(trim((string)$input['format'])) : 'json';
if ($format !== 'json' && $format !== 'csv') {
    $format = 'json';
}

$medidores = isset($input['medidores']) && is_array($input['medidores']) ? $input['medidores'] : [];
$medidores = array_values(array_filter(array_map(static function ($m) {
    $m = strtoupper(trim((string)$m));
    if ($m === '' || strlen($m) > 48) {
        return null;
    }
    if (!preg_match('/^[A-Z0-9._\-]+$/', $m)) {
        return null;
    }
    return $m;
}, $medidores)));

$seen = [];
$unique = [];
foreach ($medidores as $m) {
    if (isset($seen[$m])) {
        continue;
    }
    $seen[$m] = true;
    $unique[] = $m;
}
$medidores = $unique;

$maxPedidos = 200000;
if (count($medidores) === 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Indique al menos un medidor válido (uno por línea, alfanumérico).'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (count($medidores) > $maxPedidos) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Máximo ' . $maxPedidos . ' medidores por solicitud.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$chunkSize = 1000;
$previewCap = 200;
$sqlsrvQuery = static function ($conn, string $sql, array $params) {
    return call_user_func(
        'sqlsrv_query',
        $conn,
        $sql,
        $params,
        ['QueryTimeout' => 600, 'SendStreamParamsAtExec' => true, 'Scrollable' => 'forward']
    );
};

$conn = \App\Services\AmiKcentinelConnection::connect();
if ($conn === false) {
    if (!function_exists('sqlsrv_connect')) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Extensión sqlsrv no disponible en este servidor.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Error de conexión a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

@set_time_limit(720);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="consulta_sigami_' . gmdate('Ymd_His') . 'Z.csv"');
    $out = fopen('php://output', 'wb');
    if ($out === false) {
        call_user_func('sqlsrv_close', $conn);
        http_response_code(500);
        exit;
    }
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['tlpnMedidor', 'tlpnIdSigAmi'], ';');
    $chunks = array_chunk($medidores, $chunkSize);
    foreach ($chunks as $grupo) {
        $placeholders = implode(',', array_fill(0, count($grupo), '?'));
        $sql = 'SELECT tlpnMedidor, tlpnIdSigAmi FROM [kcentinel].[dbo].[telepnuevomedidor] WHERE [tlpnMedidor] IN (' . $placeholders . ')';
        $stmt = $sqlsrvQuery($conn, $sql, $grupo);
        if ($stmt === false) {
            fclose($out);
            call_user_func('sqlsrv_close', $conn);
            http_response_code(500);
            exit;
        }
        while ($row = call_user_func('sqlsrv_fetch_array', $stmt, 2)) { // SQLSRV_FETCH_ASSOC
            $med = isset($row['tlpnMedidor']) ? (string)$row['tlpnMedidor'] : '';
            $id = $row['tlpnIdSigAmi'] ?? '';
            if ($id !== null && $id !== '' && !is_string($id)) {
                $id = (string)$id;
            }
            fputcsv($out, [$med, $id], ';');
        }
    }
    fclose($out);
    call_user_func('sqlsrv_close', $conn);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$matchedRows = 0;
$foundMedidorKeys = [];
$previewRows = [];
$chunks = array_chunk($medidores, $chunkSize);

foreach ($chunks as $grupo) {
    $placeholders = implode(',', array_fill(0, count($grupo), '?'));
    $sql = 'SELECT tlpnMedidor, tlpnIdSigAmi FROM [kcentinel].[dbo].[telepnuevomedidor] WHERE [tlpnMedidor] IN (' . $placeholders . ')';
    $stmt = $sqlsrvQuery($conn, $sql, $grupo);
    if ($stmt === false) {
        $errs = call_user_func('sqlsrv_errors');
        call_user_func('sqlsrv_close', $conn);
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Error en la consulta SQL.', 'detail' => $errs], JSON_UNESCAPED_UNICODE);
        exit;
    }
    while ($row = call_user_func('sqlsrv_fetch_array', $stmt, 2)) { // SQLSRV_FETCH_ASSOC
        $matchedRows++;
        $med = isset($row['tlpnMedidor']) ? strtoupper(trim((string)$row['tlpnMedidor'])) : '';
        if ($med !== '') {
            $foundMedidorKeys[$med] = true;
        }
        if (count($previewRows) < $previewCap) {
            $medDisp = isset($row['tlpnMedidor']) ? trim((string)$row['tlpnMedidor']) : '';
            $id = $row['tlpnIdSigAmi'] ?? null;
            $previewRows[] = [
                'medidor' => $medDisp,
                'id_sigami' => $id === null ? null : (is_numeric($id) ? (int)$id : $id),
            ];
        }
    }
}

call_user_func('sqlsrv_close', $conn);

$requested = count($medidores);
$encontradosDistintos = count($foundMedidorKeys);
$noEncontrados = max(0, $requested - $encontradosDistintos);

echo json_encode([
    'ok' => true,
    'rows' => $previewRows,
    'stats' => [
        'pedidos' => $requested,
        'filas_devueltas' => $matchedRows,
        'medidores_encontrados' => $encontradosDistintos,
        'no_encontrados' => $noEncontrados,
        'mostrados_en_vista' => count($previewRows),
        'vista_truncada' => $matchedRows > $previewCap,
    ],
    'message' => $matchedRows > $previewCap
        ? 'Se devolvieron ' . $matchedRows . ' filas (' . $encontradosDistintos . ' medidores distintos). La tabla muestra las primeras ' . $previewCap . ' filas; use «Descargar CSV» para el listado completo.'
        : ('Filas en BD: ' . $matchedRows . ' · Medidores de su lista encontrados: ' . $encontradosDistintos . ' de ' . $requested . '.'),
], JSON_UNESCAPED_UNICODE);
