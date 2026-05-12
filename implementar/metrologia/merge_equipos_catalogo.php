<?php
/**
 * Fusión catálogo metrología: Excel/TSV + catálogo actual + recepciones/bitácora en app.
 *
 * MAPEO COLUMNAS EXPORT (TSV/CSV tab) → campos catálogo (metrologia_equipos.json):
 *   DESCRIPCION              → descripcion
 *   FOLIO                    → folio (tras normalización de año)
 *   No. SERIE / NO. SERIE   → no_serie (trim + mayúsculas como clave de cruce)
 *   MARCA                    → marca
 *   MODELO                   → modelo
 *   ZONA                     → zona
 *   AREA                     → area
 *   OFICINA                  → oficina
 * (resto de columnas del concentrado se ignoran para el shape del catálogo)
 *
 * NORMALIZACIÓN:
 *   - Serie: trim + mb_strtoupper (UTF-8).
 *   - Folio: vacío se conserva; "N/A" literal; patrón YY-NNNN → 20YY-NNNN con parte numérica a 4 dígitos;
 *     patrón 20YY-NNNN rellena parte numérica a 4 dígitos.
 *
 * USO (desde la raíz del proyecto):
 *   php implementar/metrologia/merge_equipos_catalogo.php ruta/al/export.tsv
 *
 * Salidas (storage/json por defecto):
 *   - metrologia_equipos_merged.json   (mismo esquema que catálogo + claves extra opcionales)
 *   - metrologia_equipos_merge_report.json
 *
 * Activación en runtime: ver comentario en app/bootstrap.php y app/config/constants.php
 *   (constante metrologia_equipos_catalogo_file o env SIGTAE_MET_EQUIPO_CATALOGO_JSON).
 */

declare(strict_types=1);

function usage(int $code = 0): void
{
    $msg = <<<TXT
Uso: php merge_equipos_catalogo.php <archivo.tsv> [--storage=RUTA_JSON]

Lee el TSV (primera fila = encabezados, delimitador TAB), fusiona con:
  metrologia_equipos.json, metrologia_recepciones.json, metrologia_bitacora_equipos.json
y escribe metrologia_equipos_merged.json + metrologia_equipos_merge_report.json.

TXT;
    fwrite($code === 0 ? STDOUT : STDERR, $msg);
    exit($code);
}

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ejecutar solo en CLI.\n");
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);
$tsvPath = null;
$storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'json';

foreach ($argv as $arg) {
    if (strpos($arg, '--storage=') === 0) {
        $storagePath = substr($arg, strlen('--storage='));
    } elseif ($arg === '-h' || $arg === '--help') {
        usage(0);
    } elseif ($tsvPath === null && $arg !== '') {
        $tsvPath = $arg;
    }
}

if ($tsvPath === null || !is_file($tsvPath)) {
    fwrite(STDERR, "Archivo TSV inválido o no indicado.\n");
    usage(1);
}

$storagePath = rtrim($storagePath, DIRECTORY_SEPARATOR . '/');
if (!is_dir($storagePath)) {
    fwrite(STDERR, "Directorio storage no existe: {$storagePath}\n");
    exit(1);
}

function nowIsoMx(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('c');
}

function newMeqId(): string
{
    return 'meq-' . bin2hex(random_bytes(6));
}

/** @param string $s */
function normSerie(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '';
    }
    return mb_strtoupper($s, 'UTF-8');
}

/** Normaliza folio programa/calibración (no folio REC- de recepción). */
function normFolio(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '';
    }
    if (strtoupper($s) === 'N/A') {
        return 'N/A';
    }
    if (preg_match('/^(20\d{2})-(\d+)$/u', $s, $m)) {
        return $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
    }
    if (preg_match('/^(\d{2})-(\d+)$/u', $s, $m)) {
        return '20' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
    }
    return $s;
}

/** @return array<string, int> campo interno => índice columna */
function detectHeaderMap(array $headerCells): array
{
    $map = [];
    $patterns = [
        'descripcion' => '/descrip/i',
        'folio' => '/^folio$/i',
        'no_serie' => '/no\.?\s*serie/i',
        'marca' => '/^marca$/i',
        'modelo' => '/^modelo$/i',
        'zona' => '/^zona$/i',
        'area' => '/^area$/i',
        'oficina' => '/^oficina$/i',
    ];
    foreach ($headerCells as $i => $raw) {
        $h = trim((string)$raw);
        if ($h === '') {
            continue;
        }
        foreach ($patterns as $field => $re) {
            if (preg_match($re, $h) === 1 && !isset($map[$field])) {
                $map[$field] = (int)$i;
                break;
            }
        }
    }
    return $map;
}

/** @return list<list<string>> */
function readTsvRows(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("No se pudo leer: {$path}");
    }
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
        $raw = substr($raw, 3);
    }
    $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
    $rows = [];
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $rows[] = str_getcsv($line, "\t", '"', '\\');
    }
    return $rows;
}

/** @param array<string, mixed> $data */
function rowFromTsv(array $cells, array $colMap): array
{
    $g = static function (string $k) use ($cells, $colMap): string {
        if (!isset($colMap[$k])) {
            return '';
        }
        $i = $colMap[$k];
        return trim((string)($cells[$i] ?? ''));
    };
    return [
        'descripcion' => $g('descripcion'),
        'folio' => normFolio($g('folio')),
        'no_serie' => normSerie($g('no_serie')),
        'marca' => $g('marca'),
        'modelo' => $g('modelo'),
        'zona' => $g('zona'),
        'area' => $g('area'),
        'oficina' => $g('oficina'),
    ];
}

/** @param array<string, mixed> $row */
function mergeKeyForRow(array $row): string
{
    $s = normSerie((string)($row['no_serie'] ?? ''));
    if ($s !== '') {
        return 'S:' . $s;
    }
    $f = normFolio((string)($row['folio'] ?? ''));
    if ($f !== '' && $f !== 'N/A') {
        return 'F:' . $f;
    }
    return '';
}

/**
 * Índice por serie desde recepciones (última aparición gana).
 * @return array<string, array{folio:string,recepcion_id:string,folio_recepcion:string,marca:string,modelo:string,descripcion:string}>
 */
function indexFromRecepciones(array $recepciones): array
{
    $idx = [];
    foreach ($recepciones as $rec) {
        $rid = (string)($rec['id'] ?? '');
        $fr = (string)($rec['folio_recepcion'] ?? '');
        foreach ((array)($rec['equipos'] ?? []) as $eq) {
            $ser = normSerie((string)($eq['serie'] ?? ''));
            if ($ser === '') {
                continue;
            }
            $idx[$ser] = [
                'folio' => normFolio((string)($eq['folio'] ?? '')),
                'recepcion_id' => $rid,
                'folio_recepcion' => $fr,
                'marca' => trim((string)($eq['marca'] ?? '')),
                'modelo' => trim((string)($eq['modelo'] ?? '')),
                'descripcion' => trim((string)($eq['descripcion'] ?? '')),
            ];
        }
    }
    return $idx;
}

/**
 * Complementa recepcion_id / folio_recepcion desde bitácora si faltan en índice de recepciones.
 * @param array<string, array<string, string>> $idx mutado
 * @param list<array<string, mixed>> $bitacora
 */
function enrichIndexFromBitacora(array &$idx, array $bitacora): int
{
    $n = 0;
    foreach ($bitacora as $row) {
        $rid = trim((string)($row['recepcion_id'] ?? ''));
        if ($rid === '') {
            continue;
        }
        $ser = normSerie((string)($row['no_serie'] ?? ''));
        if ($ser === '') {
            continue;
        }
        if (!isset($idx[$ser])) {
            $idx[$ser] = [
                'folio' => normFolio((string)($row['folio'] ?? '')),
                'recepcion_id' => $rid,
                'folio_recepcion' => trim((string)($row['folio_recepcion'] ?? '')),
                'marca' => trim((string)($row['marca'] ?? '')),
                'modelo' => trim((string)($row['modelo'] ?? '')),
                'descripcion' => trim((string)($row['descripcion'] ?? '')),
            ];
            $n++;
        } else {
            $cur = &$idx[$ser];
            if (($cur['recepcion_id'] ?? '') === '' && $rid !== '') {
                $cur['recepcion_id'] = $rid;
                $cur['folio_recepcion'] = trim((string)($row['folio_recepcion'] ?? ''));
            }
        }
    }
    return $n;
}

/** @param array<string, mixed> $base */
function pickNonEmpty(string $a, string $b): string
{
    $a = trim($a);
    if ($a !== '') {
        return $a;
    }
    return trim($b);
}

// --- Carga JSON ---
$pathEquipos = $storagePath . DIRECTORY_SEPARATOR . 'metrologia_equipos.json';
$pathRecep = $storagePath . DIRECTORY_SEPARATOR . 'metrologia_recepciones.json';
$pathBit = $storagePath . DIRECTORY_SEPARATOR . 'metrologia_bitacora_equipos.json';

$equipos = json_decode((string)file_get_contents($pathEquipos), true);
$recepciones = json_decode((string)file_get_contents($pathRecep), true);
$bitacora = json_decode((string)file_get_contents($pathBit), true);
if (!is_array($equipos)) {
    $equipos = [];
}
if (!is_array($recepciones)) {
    $recepciones = [];
}
if (!is_array($bitacora)) {
    $bitacora = [];
}

$appIdx = indexFromRecepciones($recepciones);
$bitOnlyAdds = enrichIndexFromBitacora($appIdx, $bitacora);

$tsvRows = readTsvRows($tsvPath);
if ($tsvRows === []) {
    fwrite(STDERR, "TSV sin filas.\n");
    exit(1);
}
$headerRow = array_shift($tsvRows);
$colMap = detectHeaderMap($headerRow);
if (!isset($colMap['no_serie']) && !isset($colMap['folio'])) {
    fwrite(STDERR, "Encabezados: se requiere al menos columna de serie o folio reconocible.\n");
    exit(1);
}

$now = nowIsoMx();
/** @var array<string, array<string, mixed>> $byKey */
$byKey = [];
$folioConflicts = [];
$excelRowCount = 0;
$keysFromExcel = [];

foreach ($tsvRows as $cells) {
    $excelRowCount++;
    $r = rowFromTsv($cells, $colMap);
    $key = mergeKeyForRow($r);
    if ($key === '') {
        continue;
    }
    $keysFromExcel[$key] = true;

    $serie = normSerie((string)$r['no_serie']);
    $app = $serie !== '' ? ($appIdx[$serie] ?? null) : null;

    $folioExcel = (string)$r['folio'];
    $folioFinal = $folioExcel;
    $mergeSource = 'excel';
    $appRecepcionId = '';
    $appFolioRecepcion = '';

    if ($app !== null) {
        $appRecepcionId = $app['recepcion_id'];
        $appFolioRecepcion = $app['folio_recepcion'];
        if ($app['folio'] !== '') {
            if ($folioExcel !== '' && normFolio($folioExcel) !== normFolio($app['folio'])) {
                $folioConflicts[] = [
                    'serie' => $serie,
                    'folio_excel' => $folioExcel,
                    'folio_app' => $app['folio'],
                    'recepcion_id' => $appRecepcionId,
                ];
            }
            $folioFinal = $app['folio'];
        }
        $mergeSource = 'app+excel';
        $r['marca'] = pickNonEmpty($app['marca'], $r['marca']);
        $r['modelo'] = pickNonEmpty($app['modelo'], $r['modelo']);
        $r['descripcion'] = pickNonEmpty($app['descripcion'], $r['descripcion']);
    }

    $existing = $byKey[$key] ?? null;
    if ($existing === null) {
        $byKey[$key] = [
            'id' => newMeqId(),
            'folio' => $folioFinal,
            'no_serie' => $serie !== '' ? $serie : trim((string)$r['no_serie']),
            'marca' => $r['marca'],
            'modelo' => $r['modelo'],
            'descripcion' => $r['descripcion'],
            'zona' => $r['zona'],
            'area' => $r['area'],
            'oficina' => $r['oficina'],
            'created_at' => $now,
            'updated_at' => $now,
            'merge_source' => $mergeSource,
            'app_recepcion_id' => $appRecepcionId,
            'app_folio_recepcion' => $appFolioRecepcion,
        ];
    } else {
        $byKey[$key]['folio'] = $folioFinal !== '' ? $folioFinal : (string)($byKey[$key]['folio'] ?? '');
        foreach (['marca', 'modelo', 'descripcion', 'zona', 'area', 'oficina'] as $f) {
            $byKey[$key][$f] = pickNonEmpty((string)$r[$f], (string)($byKey[$key][$f] ?? ''));
        }
        $byKey[$key]['merge_source'] = ($app !== null) ? 'app+excel' : (string)($byKey[$key]['merge_source'] ?? 'excel');
        if ($appRecepcionId !== '') {
            $byKey[$key]['app_recepcion_id'] = $appRecepcionId;
            $byKey[$key]['app_folio_recepcion'] = $appFolioRecepcion;
        }
        $byKey[$key]['updated_at'] = $now;
    }
}

// Catálogo actual: conservar id/created_at si coincide clave
$catalogBySerie = [];
foreach ($equipos as $e) {
    if (!is_array($e)) {
        continue;
    }
    $s = normSerie((string)($e['no_serie'] ?? ''));
    if ($s !== '') {
        $catalogBySerie['S:' . $s] = $e;
    } else {
        $f = normFolio((string)($e['folio'] ?? ''));
        if ($f !== '' && $f !== 'N/A') {
            $catalogBySerie['F:' . $f] = $e;
        }
    }
}

foreach ($byKey as $key => &$row) {
    $cat = $catalogBySerie[$key] ?? null;
    if ($cat !== null && is_array($cat)) {
        $row['id'] = (string)($cat['id'] ?? $row['id']);
        $row['created_at'] = (string)($cat['created_at'] ?? $row['created_at']);
        foreach (['marca', 'modelo', 'descripcion', 'zona', 'area', 'oficina', 'folio'] as $f) {
            if (trim((string)($row[$f] ?? '')) === '' && trim((string)($cat[$f] ?? '')) !== '') {
                $row[$f] = (string)$cat[$f];
            }
        }
        $src = (string)($row['merge_source'] ?? '');
        if ($src === 'excel') {
            $row['merge_source'] = 'catalog+excel';
        }
    }
}
unset($row);

// Equipos en catálogo que no aparecieron en el TSV
$catalogOnly = 0;
foreach ($equipos as $e) {
    if (!is_array($e)) {
        continue;
    }
    $s = normSerie((string)($e['no_serie'] ?? ''));
    $key = $s !== '' ? 'S:' . $s : '';
    if ($key === '') {
        $f = normFolio((string)($e['folio'] ?? ''));
        $key = ($f !== '' && $f !== 'N/A') ? 'F:' . $f : '';
    }
    if ($key === '' || isset($byKey[$key])) {
        continue;
    }
    $serie = $s !== '' ? $s : trim((string)($e['no_serie'] ?? ''));
    $app = $serie !== '' ? ($appIdx[$serie] ?? null) : null;
    $folio = trim((string)($e['folio'] ?? ''));
    if ($app !== null && $app['folio'] !== '') {
        if ($folio !== '' && normFolio($folio) !== normFolio($app['folio'])) {
            $folioConflicts[] = [
                'serie' => $serie,
                'folio_catalogo' => $folio,
                'folio_app' => $app['folio'],
                'recepcion_id' => $app['recepcion_id'],
            ];
        }
        $folio = $app['folio'];
    }
    $byKey[$key] = [
        'id' => (string)($e['id'] ?? newMeqId()),
        'folio' => $folio,
        'no_serie' => $serie,
        'marca' => (string)($e['marca'] ?? ''),
        'modelo' => (string)($e['modelo'] ?? ''),
        'descripcion' => (string)($e['descripcion'] ?? ''),
        'zona' => (string)($e['zona'] ?? ''),
        'area' => (string)($e['area'] ?? ''),
        'oficina' => (string)($e['oficina'] ?? ''),
        'created_at' => (string)($e['created_at'] ?? $now),
        'updated_at' => $now,
        'merge_source' => $app !== null ? 'catalog+app' : 'catalog_only',
        'app_recepcion_id' => $app['recepcion_id'] ?? '',
        'app_folio_recepcion' => $app['folio_recepcion'] ?? '',
    ];
    $catalogOnly++;
}

// Series solo en app (recepción) sin fila Excel ni catálogo
$appOnly = 0;
foreach ($appIdx as $serie => $app) {
    $key = 'S:' . $serie;
    if (isset($byKey[$key])) {
        continue;
    }
    $byKey[$key] = [
        'id' => newMeqId(),
        'folio' => $app['folio'],
        'no_serie' => $serie,
        'marca' => $app['marca'],
        'modelo' => $app['modelo'],
        'descripcion' => $app['descripcion'],
        'zona' => '',
        'area' => '',
        'oficina' => '',
        'created_at' => $now,
        'updated_at' => $now,
        'merge_source' => 'app_only',
        'app_recepcion_id' => $app['recepcion_id'],
        'app_folio_recepcion' => $app['folio_recepcion'],
    ];
    $appOnly++;
}

$outList = array_values($byKey);
usort($outList, static function (array $a, array $b): int {
    return strcmp((string)($a['no_serie'] ?? ''), (string)($b['no_serie'] ?? ''));
});

$fromApp = 0;
foreach ($outList as $row) {
    $src = (string)($row['merge_source'] ?? '');
    if (strpos($src, 'app') !== false) {
        $fromApp++;
    }
}

$report = [
    'generated_at' => $now,
    'tsv_path' => realpath($tsvPath) ?: $tsvPath,
    'storage_path' => $storagePath,
    'header_map' => $colMap,
    'counts' => [
        'excel_data_rows' => $excelRowCount,
        'catalog_rows' => count($equipos),
        'output_rows' => count($outList),
        'rows_with_app_trace' => $fromApp,
        'app_index_bitacora_supplements' => $bitOnlyAdds,
        'catalog_only_rows_added' => $catalogOnly,
        'app_only_rows_added' => $appOnly,
        'folio_conflicts' => count($folioConflicts),
    ],
    'folio_conflict_samples' => array_slice($folioConflicts, 0, 50),
];

$outMerged = $storagePath . DIRECTORY_SEPARATOR . 'metrologia_equipos_merged.json';
$outReport = $storagePath . DIRECTORY_SEPARATOR . 'metrologia_equipos_merge_report.json';

file_put_contents(
    $outMerged,
    json_encode($outList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
);
file_put_contents(
    $outReport,
    json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
);

fwrite(STDOUT, "OK: " . count($outList) . " equipos → {$outMerged}\n");
fwrite(STDOUT, "Reporte: {$outReport}\n");
