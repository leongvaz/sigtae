<?php
require_once __DIR__ . '/bootstrap.php';

$projectRoot = dirname(__DIR__, 4);
$dataDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'metrologia_sig';
$uploadsDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'metrologia_sig';
$structurePath = $dataDir . DIRECTORY_SEPARATOR . 'structure.json';
$documentsPath = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

define('MAX_FILE_BYTES', 25 * 1024 * 1024);
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];

function sanitizeFilename($name) {
    $base = basename($name);
    $base = preg_replace('/[^a-zA-Z0-9._-]/', '_', $base);
    return substr($base, 0, 200) ?: 'file';
}
function getExt($mime, $originalName) {
    $map = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', 'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'];
    $ext = isset($map[$mime]) ? $map[$mime] : strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']) ? $ext : 'bin';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    $nodeId = isset($_GET['nodeId']) ? trim($_GET['nodeId']) : '';
    if ($nodeId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'nodeId requerido']);
        exit;
    }
    if (!is_file($documentsPath)) {
        echo json_encode([]);
        exit;
    }
    $data = json_decode(file_get_contents($documentsPath), true);
    $docs = isset($data['docs']) ? $data['docs'] : [];
    $out = array_values(array_filter($docs, function ($d) use ($nodeId) { return ($d['nodeItemId'] ?? '') === $nodeId; }));
    echo json_encode($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $nodeId = isset($_POST['nodeId']) ? trim($_POST['nodeId']) : '';
    if ($nodeId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'nodeId requerido']);
        exit;
    }
    $auditoriaNodeIds = ['auditoria_interna', 'auditoria_externa'];
    $isAuditoriaNode = in_array($nodeId, $auditoriaNodeIds);
    if (!$isAuditoriaNode) {
        if (!is_file($structurePath)) {
            http_response_code(400);
            echo json_encode(['error' => 'Estructura no encontrada']);
            exit;
        }
        $structure = json_decode(file_get_contents($structurePath), true);
        $nodeItems = isset($structure['nodeItems']) ? $structure['nodeItems'] : [];
        $node = null;
        foreach ($nodeItems as $n) {
            if (($n['id'] ?? '') === $nodeId) {
                $node = $n;
                break;
            }
        }
        $hasChildren = false;
        foreach ($nodeItems as $n) {
            if (isset($n['parentId']) && $n['parentId'] === $nodeId) {
                $hasChildren = true;
                break;
            }
        }
        if (!$node || (empty($node['isLeaf']) && $hasChildren)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nodo no es hoja o no existe']);
            exit;
        }
    }
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    $realUploads = realpath($uploadsDir);
    $data = ['docs' => []];
    if (is_file($documentsPath)) {
        $data = json_decode(file_get_contents($documentsPath), true);
        if (!is_array($data) || !isset($data['docs'])) {
            $data = ['docs' => []];
        }
    }
    $added = [];
    $errors = [];
    $comment = isset($_POST['comment']) ? trim((string)$_POST['comment']) : '';
    if (mb_strlen($comment) > 500) {
        $comment = mb_substr($comment, 0, 500);
    }
    $files = isset($_FILES['files']) ? $_FILES['files'] : [];
    // Normalizar a array (un solo archivo viene como string)
    if (!empty($files['name']) && !is_array($files['name'])) {
        foreach (['name', 'type', 'size', 'tmp_name', 'error'] as $k) {
            if (isset($files[$k])) {
                $files[$k] = [$files[$k]];
            }
        }
    }
    if ($isAuditoriaNode && !empty($files['name']) && is_array($files['name'])) {
        $currentCount = count(array_filter($data['docs'], function ($d) use ($nodeId) { return ($d['nodeItemId'] ?? '') === $nodeId; }));
        if ($currentCount + count($files['name']) > 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Solo se permiten 2 documentos para esta auditoría.']);
            exit;
        }
    }
    if (!empty($files['name']) && is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $name = $files['name'][$i] ?? '';
            $type = $files['type'][$i] ?? '';
            $size = (int)($files['size'][$i] ?? 0);
            $tmp = $files['tmp_name'][$i] ?? '';
            if ($size > MAX_FILE_BYTES) {
                $errors[] = $name . ': Tamaño excede el máximo';
                continue;
            }
            if (!in_array($type, $allowedMimes)) {
                $errors[] = $name . ': Tipo no permitido';
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = $name . ': Extensión no permitida';
                continue;
            }
            if (!is_uploaded_file($tmp)) {
                $errors[] = $name . ': Error de subida';
                continue;
            }
            $safeExt = getExt($type, $name);
            $storedName = bin2hex(random_bytes(16)) . '.' . $safeExt;
            $dest = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;
            $destReal = realpath(dirname($dest)) . DIRECTORY_SEPARATOR . basename($dest);
            if (strpos($destReal, $realUploads) !== 0) {
                $errors[] = $name . ': Ruta no permitida';
                continue;
            }
            if (!move_uploaded_file($tmp, $dest)) {
                $errors[] = $name . ': No se pudo guardar';
                continue;
            }
            $docId = pathinfo($storedName, PATHINFO_FILENAME);
            $doc = ['id' => $docId, 'nodeItemId' => $nodeId, 'originalName' => sanitizeFilename($name), 'storedName' => $storedName, 'mimeType' => $type, 'size' => $size, 'createdAt' => date('c')];
            if ($comment !== '') {
                $doc['comment'] = $comment;
            }
            $data['docs'][] = $doc;
            $added[] = $doc;
        }
    }
    if (!empty($added)) {
        file_put_contents($documentsPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    if (!empty($added)) {
        http_response_code(201);
        echo json_encode(['added' => $added, 'errors' => $errors]);
    } elseif (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => implode('; ', $errors)]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No se enviaron archivos']);
    }
    exit;
}

http_response_code(405);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Método no permitido']);
