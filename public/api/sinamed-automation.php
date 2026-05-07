<?php
// API JSON: universo IUSA–SINAMED (DataTables server-side) para usuarios AMI.

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

$resource = (string)($_GET['resource'] ?? '');
$universePath = $base . '/storage/json/sinamed_automation/universe_sinamed.json';

if ($resource !== 'universe') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Parámetro resource inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_readable($universePath)) {
    echo json_encode([
        'draw' => (int)($_GET['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$decoded = json_decode((string)file_get_contents($universePath), true);
$items = is_array($decoded) && isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];

$draw = (int)($_GET['draw'] ?? 1);
$start = max(0, (int)($_GET['start'] ?? 0));
$length = (int)($_GET['length'] ?? 25);
if ($length <= 0 || $length > 5000) {
    $length = 100;
}

$search = '';
if (isset($_GET['search']) && is_array($_GET['search'])) {
    $search = trim((string)($_GET['search']['value'] ?? ''));
}

$filtered = $items;
if ($search !== '') {
    $sl = mb_strtolower($search, 'UTF-8');
    $filtered = array_values(array_filter($items, static function ($row) use ($sl) {
        if (!is_array($row)) {
            return false;
        }
        $hay = mb_strtolower(
            ((string)($row['medidor'] ?? '')) . ' ' .
            ((string)($row['cuenta'] ?? '')) . ' ' .
            ((string)($row['ciclo'] ?? '')),
            'UTF-8'
        );
        return str_contains($hay, $sl);
    }));
}

$total = count($items);
$filt = count($filtered);
$slice = array_slice($filtered, $start, $length);

$data = [];
foreach ($slice as $row) {
    if (!is_array($row)) {
        continue;
    }
    $data[] = [
        (string)($row['medidor'] ?? ''),
        (string)($row['cuenta'] ?? ''),
        (string)($row['ciclo'] ?? ''),
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filt,
    'data' => $data,
], JSON_UNESCAPED_UNICODE);
