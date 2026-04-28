<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$sectionId = isset($_GET['sectionId']) ? (int)$_GET['sectionId'] : 0;
$parentId = isset($_GET['parentId']) ? trim($_GET['parentId']) : null;
if ($parentId === '') {
    $parentId = null;
}

if ($sectionId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'sectionId inválido']);
    exit;
}

$projectRoot = dirname(__DIR__, 4);
$dataDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'metrologia_sig';
$structurePath = $dataDir . DIRECTORY_SEPARATOR . 'structure.json';
$documentsPath = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

if (!is_file($structurePath)) {
    echo json_encode([]);
    exit;
}

$structure = json_decode(file_get_contents($structurePath), true);
$nodeItems = $structure['nodeItems'] ?? [];

$docs = [];
if (is_file($documentsPath)) {
    $docData = json_decode(file_get_contents($documentsPath), true);
    $docs = $docData['docs'] ?? [];
}
$nodeIdsWithDocs = array_flip(array_column($docs, 'nodeItemId'));

// Obtener IDs de todas las hojas en el subárbol de un nodo (recursivo)
function getLeafIdsUnder($nodeId, $nodeItems, $sectionId) {
    $leaves = [];
    foreach ($nodeItems as $n) {
        if ((int)($n['sectionId'] ?? 0) !== $sectionId || (isset($n['parentId']) ? $n['parentId'] : null) !== $nodeId) continue;
        if (!empty($n['isLeaf'])) {
            $leaves[] = $n['id'];
        } else {
            $leaves = array_merge($leaves, getLeafIdsUnder($n['id'], $nodeItems, $sectionId));
        }
    }
    return $leaves;
}

$children = [];
foreach ($nodeItems as $n) {
    if ((int)$n['sectionId'] !== $sectionId) {
        continue;
    }
    $nParent = isset($n['parentId']) ? $n['parentId'] : null;
    if ($nParent !== $parentId) {
        continue;
    }
    $attended = null;
    $attendedLeaves = null;
    $totalLeaves = null;
    if (!empty($n['isLeaf'])) {
        $attended = isset($nodeIdsWithDocs[$n['id']]);
    } else {
        $leafIds = getLeafIdsUnder($n['id'], $nodeItems, $sectionId);
        $totalLeaves = count($leafIds);
        $attendedLeaves = 0;
        foreach ($leafIds as $lid) {
            if (isset($nodeIdsWithDocs[$lid])) $attendedLeaves++;
        }
    }
    $children[] = [
        'id' => $n['id'],
        'sectionId' => (int)$n['sectionId'],
        'parentId' => $nParent,
        'title' => $n['title'],
        'order' => (int)($n['order'] ?? 0),
        'isLeaf' => !empty($n['isLeaf']),
        'attended' => $attended,
        'attendedLeaves' => $attendedLeaves,
        'totalLeaves' => $totalLeaves,
    ];
}

usort($children, function ($a, $b) {
    return $a['order'] <=> $b['order'];
});

echo json_encode($children);
