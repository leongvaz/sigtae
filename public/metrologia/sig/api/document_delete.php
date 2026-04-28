<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$documentId = isset($input['documentId']) ? trim($input['documentId']) : (isset($_POST['documentId']) ? trim($_POST['documentId']) : '');
if ($documentId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'documentId requerido']);
    exit;
}

$projectRoot = dirname(__DIR__, 4);
$dataDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'metrologia_sig';
$uploadsDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'metrologia_sig';
$documentsPath = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

if (!is_file($documentsPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Documento no encontrado']);
    exit;
}

$data = json_decode(file_get_contents($documentsPath), true);
$docs = $data['docs'] ?? [];
$found = null;
$idx = null;
foreach ($docs as $i => $d) {
    if (($d['id'] ?? '') === $documentId) {
        $found = $d;
        $idx = $i;
        break;
    }
}
if ($found === null || $idx === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Documento no encontrado']);
    exit;
}

$storedName = $found['storedName'] ?? '';
$filePath = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;
$realUploads = realpath($uploadsDir);
if ($storedName !== '' && is_file($filePath)) {
    $realFile = realpath($filePath);
    if ($realFile && strpos($realFile, $realUploads) === 0) {
        unlink($filePath);
    }
}

array_splice($docs, $idx, 1);
$data['docs'] = $docs;
file_put_contents($documentsPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['ok' => true]);
