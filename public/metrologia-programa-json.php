<?php
/**
 * Exporta el contenido de "Programa 2026.txt" a JSON.
 *
 * Uso:
 * - GET /metrologia-programa-json.php         -> genera si no existe o está vacío
 * - GET /metrologia-programa-json.php?force=1 -> regenera siempre
 *
 * Salida:
 * - Crea/actualiza storage/json/metrologia_programa_2026.json
 */

$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccess($user) || !$metPerm->canManage($user)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "No autorizado.\n";
    exit;
}

use App\Core\JsonStorage;
use App\Repositories\MetrologiaRepositoryUtils;

$storagePath = $container['config']['storage_path'] ?? ($base . '/storage/json');
$outStorage = new JsonStorage($storagePath, 'metrologia_programa_2026.json');
$force = !empty($_GET['force']);

$existing = $outStorage->read([]);
if (!$force && !empty($existing)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'message' => 'El JSON ya existe. Use ?force=1 para regenerar.',
        'path' => 'storage/json/metrologia_programa_2026.json',
        'count' => count($existing),
        'updated_at' => MetrologiaRepositoryUtils::nowIso(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$programaPath = $base . '/Programa 2026.txt';
if (!is_file($programaPath)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'No se encontró Programa 2026.txt'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = (string)@file_get_contents($programaPath);
if (trim($raw) === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Programa 2026.txt está vacío.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$lines = preg_split("/\\r\\n|\\n|\\r/", $raw) ?: [];
$header = null;
$idx = [];

$normalize = function(string $s): string {
    $s = strtoupper(trim($s));
    $map = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
    return strtr($s, $map);
};
$startsWith = function(string $s, string $prefix): bool {
    return $prefix === '' ? true : substr($s, 0, strlen($prefix)) === $prefix;
};
$normalizeFolio = function(string $folio): string {
    $f = trim($folio);
    if ($f === '') return '';
    // 26-0565 => 2026-0565
    if (preg_match('/^(\\d{2})\\-(\\d{4})$/', $f, $m)) {
        return '20' . $m[1] . '-' . $m[2];
    }
    // 2026--5 => 2026-0005
    if (preg_match('/^(\\d{4})\\-\\-(\\d{1,4})$/', $f, $m)) {
        return $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
    }
    // 2026-5 => 2026-0005
    if (preg_match('/^(\\d{4})\\-(\\d{1,4})$/', $f, $m)) {
        return $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
    }
    return $f;
};
$parseDateDMY = function(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    if (preg_match('/^(\\d{2})\\/(\\d{2})\\/(\\d{4})$/', $s, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return '';
};

$items = [];
foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '') continue;

    if ($header === null && $startsWith($line, 'DESCRIPCION')) {
        $header = explode("\t", $line);
        foreach ($header as $i => $h) {
            $idx[$normalize($h)] = $i;
        }
        continue;
    }
    if ($header === null) continue;

    $cols = explode("\t", $line);
    if (count($cols) < 10) continue;

    $get = function(string $key) use ($idx, $cols, $normalize): string {
        $k = $normalize($key);
        if (!isset($idx[$k])) return '';
        $i = (int)$idx[$k];
        return isset($cols[$i]) ? trim((string)$cols[$i]) : '';
    };

    $items[] = [
        'id' => MetrologiaRepositoryUtils::newId('mprg'),
        'descripcion' => $get('DESCRIPCION'),
        'folio' => $normalizeFolio($get('FOLIO')),
        'no_serie' => $get('No. SERIE'),
        'programa_anual' => $get('PROGRAMA ANUAL DE CALIBRACION'),
        'recibido' => $parseDateDMY($get('RECIBIDO')),
        'tecnico' => $get('TECNICO'),
        'fecha_calibracion_baja' => $parseDateDMY($get('FECHA DE CALIBRACION/BAJA')),
        'evaluacion_conformidad' => $get('EVALUACION DE CONFORMIDAD'),
        'fecha_impresion' => $parseDateDMY($get('FECHA DE IMPRESION')),
        'fecha_entrega_informe_escaneado' => $parseDateDMY($get('FECHA DE ENTREGA DE INFORME ESCANEADO')),
        'entregado' => $parseDateDMY($get('ENTREGADO')),
        'nombre_a_quien_se_entrega' => $get('NOMBRE A QUIEN SE ENTREGA'),
        'marca' => $get('MARCA'),
        'modelo' => $get('MODELO'),
        'zona' => $get('ZONA'),
        'area' => $get('AREA'),
        'oficina' => $get('OFICINA'),
        'nomenclatura_gmcs' => $get('NOMENCLATURA GMCS'),
        'jefe_area' => $get('JEFE DE ÁREA'),
        'rpe_jefe_area' => $get('RPE JEFE DE AREA'),
        'fecha_programada' => $get('FECHA PROGRAMADA'),
        'tablero_evolutivo' => $get('TABLERO EVOLUTIVO'),
        'observaciones' => $get('OBSERVACIONES'),
        'source_file' => 'Programa 2026.txt',
        'imported_at' => MetrologiaRepositoryUtils::nowIso(),
        'imported_by' => (string)($user['id'] ?? ''),
    ];
}

$outStorage->write($items);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'message' => 'Programa 2026 exportado a JSON.',
    'path' => 'storage/json/metrologia_programa_2026.json',
    'count' => count($items),
    'generated_at' => MetrologiaRepositoryUtils::nowIso(),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

