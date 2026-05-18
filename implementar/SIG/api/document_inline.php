<?php
require_once __DIR__ . '/bootstrap.php';

$documentId = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($documentId === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'id requerido';
    exit;
}

$dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
$uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
$documentsPath = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

if (!is_file($documentsPath)) {
    http_response_code(404);
    exit;
}

$data = json_decode(file_get_contents($documentsPath), true);
$docs = $data['docs'] ?? [];
$doc = null;
foreach ($docs as $d) {
    if (($d['id'] ?? '') === $documentId) {
        $doc = $d;
        break;
    }
}
if (!$doc) {
    http_response_code(404);
    exit;
}

$storedName = $doc['storedName'] ?? '';
$filePath = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;
$realUploads = realpath($uploadsDir);
if (!is_file($filePath)) {
    http_response_code(404);
    exit;
}
$realFile = realpath($filePath);
if (!$realFile || strpos($realFile, $realUploads) !== 0) {
    http_response_code(403);
    exit;
}

$mime = $doc['mimeType'] ?? 'application/octet-stream';
$originalName = $doc['originalName'] ?? 'document';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . str_replace('"', '\\"', $originalName) . '"');
readfile($filePath);
exit;
