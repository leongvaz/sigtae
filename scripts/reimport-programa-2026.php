<?php
/**
 * Reimporta la bitácora desde Programa 2026.txt (reemplazo total).
 * Uso: php scripts/reimport-programa-2026.php
 */
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';

$bitRepo = $container['repositories']['met_bitacora_equipos'];
$importSvc = $container['MetrologiaProgramaImportService'];
$programaPath = $base . '/Programa 2026.txt';
$storagePath = $container['config']['storage_path'] ?? $base . '/storage/json';

$all = $bitRepo->findAll();
if (!empty($all)) {
    $backupPath = $storagePath . '/metrologia_bitacora_equipos.backup.' . date('Y-m-d_His') . '.json';
    file_put_contents($backupPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Respaldo: $backupPath\n";
}

$result = $importSvc->reimportToRepository($programaPath, $bitRepo, true);
if ($result['ok']) {
    echo $result['message'] . "\n";
    exit(0);
}
fwrite(STDERR, $result['message'] . "\n");
exit(1);
