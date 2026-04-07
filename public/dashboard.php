<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$user = $auth->requireAuth();

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$performanceService = $container['PerformanceService'];
$userRepo = $container['repositories']['user'];
$officeRepo = $container['repositories']['office'];

$allTasks = $taskRepo->findAll();
$withState = [];
foreach ($allTasks as $t) {
    $withState[] = $stateService->computeState($t);
}

$activas = array_filter($withState, fn($t) => in_array($t['estado'] ?? '', ['asignada', 'en_proceso'], true));
$enProceso = array_filter($withState, fn($t) => ($t['estado'] ?? '') === 'en_proceso');
$vencidas = array_filter($withState, fn($t) => ($t['estado'] ?? '') === 'vencida');
$incumplidas = array_filter($withState, fn($t) => ($t['estado'] ?? '') === 'incumplimiento');
$atendidas = array_filter($withState, fn($t) => ($t['estado'] ?? '') === 'atendida');

$perfArea = $performanceService->getRanking();
$promedioArea = count($perfArea) > 0
    ? round(array_sum(array_column($perfArea, 'porcentaje_desempeno')) / count($perfArea), 1)
    : 0;

$porEstado = [];
foreach ($withState as $t) {
    $e = $t['estado'] ?? 'otro';
    $porEstado[$e] = ($porEstado[$e] ?? 0) + 1;
}
$porPrioridad = [];
foreach ($withState as $t) {
    $p = $t['prioridad'] ?? 'media';
    $porPrioridad[$p] = ($porPrioridad[$p] ?? 0) + 1;
}

$offices = $officeRepo->findAll();
$porOficina = [];
foreach ($offices as $of) {
    $tid = $of['id'];
    $tasksOf = array_filter($withState, fn($t) => ($t['oficina_id'] ?? '') === $tid);
    $atendidasOf = array_filter($tasksOf, fn($t) => ($t['estado'] ?? '') === 'atendida');
    $totalOf = count($tasksOf);
    $porOficina[] = [
        'nombre' => $of['nombre'],
        'total' => $totalOf,
        'atendidas' => count($atendidasOf),
        'porcentaje' => $totalOf > 0 ? round(count($atendidasOf) / $totalOf * 100, 1) : 0,
    ];
}

usort($withState, fn($a, $b) => strcmp($a['fecha_limite'] ?? '9999', $b['fecha_limite'] ?? '9999'));
$proximasVencer = array_slice(array_filter($withState, fn($t) => in_array($t['estado'] ?? '', ['asignada', 'en_proceso'], true)), 0, 5);
$userRepo = $container['repositories']['user'];

$ultimasEvidencias = [];
foreach (array_slice($withState, 0, 20) as $t) {
    foreach ($t['evidencias'] ?? [] as $ev) {
        $ultimasEvidencias[] = [
            'tarea_folio' => $t['folio'] ?? '',
            'titulo' => $t['titulo'] ?? '',
            'fecha' => $ev['fecha_subida'] ?? '',
            'usuario' => $ev['usuario_subida'] ?? '',
        ];
    }
}
usort($ultimasEvidencias, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
$ultimasEvidencias = array_slice($ultimasEvidencias, 0, 5);

$successSeed = ($_GET['msg'] ?? '') === 'seed_ok' ? 'Tareas de ejemplo cargadas.' : '';
$pageTitle = 'Dashboard — SIGTAE';
$pageSubtitle = 'Laboratorio de Metrología';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Dashboard']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/dashboard.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
