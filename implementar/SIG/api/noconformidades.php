<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
$uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
$ncPath = $dataDir . DIRECTORY_SEPARATOR . 'noconformidades.json';
$documentsPath = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

define('MAX_FILE_BYTES', 25 * 1024 * 1024);
$allowedMimes = ['application/pdf'];
$allowedExt = ['pdf'];

function ensureNcFile($path) {
    if (!is_file($path)) {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($path, json_encode(['auditoria_interna' => [], 'auditoria_externa' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function getNcData($path) {
    ensureNcFile($path);
    $data = json_decode(file_get_contents($path), true);
    $data['auditoria_interna'] = $data['auditoria_interna'] ?? [];
    $data['auditoria_externa'] = $data['auditoria_externa'] ?? [];
    return $data;
}

function getDocOriginalName($documentsPath, $docId) {
    if (!is_file($documentsPath)) return '';
    $data = json_decode(file_get_contents($documentsPath), true);
    $docs = $data['docs'] ?? [];
    foreach ($docs as $d) {
        if (($d['id'] ?? '') === $docId) return $d['originalName'] ?? '';
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    if (!in_array($tipo, ['interna', 'externa'])) {
        http_response_code(400);
        echo json_encode(['error' => 'tipo requerido (interna o externa)']);
        exit;
    }
    $key = 'auditoria_' . $tipo;
    $data = getNcData($ncPath);
    $list = $data[$key];
    foreach ($list as &$item) {
        if (!empty($item['documentId'])) {
            $item['originalName'] = getDocOriginalName($documentsPath, $item['documentId']);
        }
    }
    unset($item);
    echo json_encode($list);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    if (!in_array($tipo, ['interna', 'externa'])) {
        http_response_code(400);
        echo json_encode(['error' => 'tipo requerido (interna o externa)']);
        exit;
    }
    $refId = isset($_POST['refId']) ? trim((string)$_POST['refId']) : '';
    $comment = isset($_POST['comment']) ? trim((string)$_POST['comment']) : '';
    if (mb_strlen($comment) > 500) $comment = mb_substr($comment, 0, 500);
    if (mb_strlen($refId) > 50) $refId = mb_substr($refId, 0, 50);

    $documentId = null;
    $originalName = '';
    $nodeId = 'nc_' . $tipo;
    if (!empty($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $type = $_FILES['file']['type'] ?? '';
        $size = (int)($_FILES['file']['size'] ?? 0);
        $tmp = $_FILES['file']['tmp_name'] ?? '';
        $name = $_FILES['file']['name'] ?? '';
        if ($size > MAX_FILE_BYTES) {
            http_response_code(400);
            echo json_encode(['error' => 'Archivo excede el tamaño máximo']);
            exit;
        }
        if (!in_array($type, $allowedMimes) || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
            http_response_code(400);
            echo json_encode(['error' => 'Solo se permite PDF']);
            exit;
        }
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $realUploads = realpath($uploadsDir);
        $storedName = bin2hex(random_bytes(16)) . '.pdf';
        $dest = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;
        $destReal = realpath(dirname($dest)) . DIRECTORY_SEPARATOR . basename($dest);
        if (strpos($destReal, $realUploads) !== 0 || !move_uploaded_file($tmp, $dest)) {
            http_response_code(400);
            echo json_encode(['error' => 'No se pudo guardar el archivo']);
            exit;
        }
        $documentId = pathinfo($storedName, PATHINFO_FILENAME);
        $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
        $originalName = substr($originalName, 0, 200) ?: 'file.pdf';
        $doc = ['id' => $documentId, 'nodeItemId' => $nodeId, 'originalName' => $originalName, 'storedName' => $storedName, 'mimeType' => $type, 'size' => $size, 'createdAt' => date('c')];
        $docData = ['docs' => []];
        if (is_file($documentsPath)) {
            $docData = json_decode(file_get_contents($documentsPath), true);
            if (!isset($docData['docs'])) $docData['docs'] = [];
        }
        $docData['docs'][] = $doc;
        file_put_contents($documentsPath, json_encode($docData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    $id = bin2hex(random_bytes(8));
    $nc = ['id' => $id, 'refId' => $refId, 'comment' => $comment, 'documentId' => $documentId ?: '', 'originalName' => $originalName, 'createdAt' => date('c')];
    $data = getNcData($ncPath);
    $key = 'auditoria_' . $tipo;
    $data[$key][] = $nc;
    file_put_contents($ncPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = trim((string)($input['id'] ?? ''));
    $tipo = trim((string)($input['tipo'] ?? ''));
    if (!in_array($tipo, ['interna', 'externa']) || $id === '') {
        http_response_code(400);
        echo json_encode(['error' => 'id y tipo requeridos']);
        exit;
    }
    $refId = isset($input['refId']) ? trim((string)$input['refId']) : '';
    $comment = isset($input['comment']) ? trim((string)$input['comment']) : '';
    if (mb_strlen($comment) > 500) $comment = mb_substr($comment, 0, 500);
    if (mb_strlen($refId) > 50) $refId = mb_substr($refId, 0, 50);

    $data = getNcData($ncPath);
    $key = 'auditoria_' . $tipo;
    $found = false;
    foreach ($data[$key] as &$item) {
        if (($item['id'] ?? '') === $id) {
            $item['refId'] = $refId;
            $item['comment'] = $comment;
            $found = true;
            break;
        }
    }
    unset($item);
    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'No conformidad no encontrada']);
        exit;
    }
    file_put_contents($ncPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = trim((string)($input['id'] ?? ''));
    $tipo = trim((string)($input['tipo'] ?? ''));
    if (!in_array($tipo, ['interna', 'externa']) || $id === '') {
        http_response_code(400);
        echo json_encode(['error' => 'id y tipo requeridos']);
        exit;
    }
    $data = getNcData($ncPath);
    $key = 'auditoria_' . $tipo;
    $docIdToRemove = null;
    $data[$key] = array_values(array_filter($data[$key], function ($item) use ($id, &$docIdToRemove) {
        if (($item['id'] ?? '') === $id) {
            $docIdToRemove = $item['documentId'] ?? null;
            return false;
        }
        return true;
    }));
    file_put_contents($ncPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($docIdToRemove && is_file($documentsPath)) {
        $docData = json_decode(file_get_contents($documentsPath), true);
        if (isset($docData['docs'])) {
            $storedName = null;
            $docData['docs'] = array_values(array_filter($docData['docs'], function ($d) use ($docIdToRemove, &$storedName) {
                if (($d['id'] ?? '') === $docIdToRemove) {
                    $storedName = $d['storedName'] ?? null;
                    return false;
                }
                return true;
            }));
            file_put_contents($documentsPath, json_encode($docData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($storedName) {
                $f = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;
                if (is_file($f)) @unlink($f);
            }
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
