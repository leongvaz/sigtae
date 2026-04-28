<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$projectRoot = dirname(__DIR__, 4);
$dataDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'metrologia_sig';
$structurePath = $dataDir . DIRECTORY_SEPARATOR . 'structure.json';
$documentsPath = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

if (!is_file($structurePath)) {
    // Seed mínimo
    $seed = [
        'sections' => [
            ['id' => 4, 'order' => 4, 'title' => 'Requisitos generales', 'isActive' => true],
            ['id' => 5, 'order' => 5, 'title' => 'Requisitos relativos a la estructura', 'isActive' => true],
            ['id' => 6, 'order' => 6, 'title' => 'Requisitos relativos a recursos', 'isActive' => true],
            ['id' => 7, 'order' => 7, 'title' => 'Requisitos del proceso', 'isActive' => true],
            ['id' => 8, 'order' => 8, 'title' => 'Requisitos del sistema de gestión', 'isActive' => true],
            ['id' => 9, 'order' => 9, 'title' => '9. (Pendiente)', 'isActive' => false],
            ['id' => 10, 'order' => 10, 'title' => '10. (Pendiente)', 'isActive' => false],
            ['id' => 11, 'order' => 11, 'title' => '11. (Pendiente)', 'isActive' => false],
        ],
        'nodeItems' => [],
    ];
    for ($sid = 4; $sid <= 8; $sid++) {
        for ($i = 1; $i <= 4; $i++) {
            $pid = $sid . '.' . $i;
            $seed['nodeItems'][] = ['id' => $pid, 'sectionId' => $sid, 'parentId' => null, 'title' => $pid . ' ...', 'order' => $i, 'isLeaf' => false];
            for ($j = 1; $j <= 2; $j++) {
                $lid = $pid . '.' . $j;
                $seed['nodeItems'][] = ['id' => $lid, 'sectionId' => $sid, 'parentId' => $pid, 'title' => $lid . ' ...', 'order' => $j, 'isLeaf' => true];
            }
        }
    }
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    file_put_contents($structurePath, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$structure = json_decode(file_get_contents($structurePath), true);
$sections = $structure['sections'] ?? [];
$nodeItems = $structure['nodeItems'] ?? [];

$docs = [];
if (is_file($documentsPath)) {
    $docData = json_decode(file_get_contents($documentsPath), true);
    $docs = $docData['docs'] ?? [];
}
$nodeIdsWithDocs = array_unique(array_column($docs, 'nodeItemId'));

$parentIds = array_unique(array_filter(array_column($nodeItems, 'parentId')));
$result = [];
foreach ($sections as $s) {
    $sectionId = (int)$s['id'];
    $totalLeaves = 0;
    $attendedLeaves = 0;
    foreach ($nodeItems as $n) {
        if ((int)$n['sectionId'] !== $sectionId) continue;
        $isEvidenceNode = !empty($n['isLeaf']);
        if (!$isEvidenceNode) {
            $hasChildren = in_array($n['id'], $parentIds);
            $isEvidenceNode = !$hasChildren;
        }
        if ($isEvidenceNode) {
            $totalLeaves++;
            if (in_array($n['id'], $nodeIdsWithDocs)) {
                $attendedLeaves++;
            }
        }
    }
    $progressPercent = $totalLeaves > 0 ? round(($attendedLeaves / $totalLeaves) * 100) : 0;
    $result[] = [
        'id' => $sectionId,
        'title' => $s['title'],
        'order' => (int)$s['order'],
        'progressPercent' => $progressPercent,
        'attendedLeaves' => $attendedLeaves,
        'totalLeaves' => $totalLeaves,
        'isActive' => !empty($s['isActive']),
    ];
}

// Sección 9 "Avance General": sumatoria de secciones 4 a 8
$sumAttended = 0;
$sumTotal = 0;
foreach ($result as $r) {
    if ($r['id'] >= 4 && $r['id'] <= 8) {
        $sumAttended += $r['attendedLeaves'];
        $sumTotal += $r['totalLeaves'];
    }
}
foreach ($result as &$r) {
    if ($r['id'] === 9) {
        $r['attendedLeaves'] = $sumAttended;
        $r['totalLeaves'] = $sumTotal;
        $r['progressPercent'] = $sumTotal > 0 ? round(($sumAttended / $sumTotal) * 100) : 0;
        break;
    }
}
unset($r);

echo json_encode($result);
