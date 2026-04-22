<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];

$mes = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

$tasks = $taskRepo->findAll();
$withState = [];
foreach ($tasks as $t) {
    $withState[] = $stateService->computeState($t);
}
$eventos = [];
foreach ($withState as $t) {
    $fl = $t['fecha_limite'] ?? null;
    if ($fl) {
        $eventos[] = [
            'id' => $t['id'],
            'folio' => $t['folio'],
            'titulo' => $t['titulo'],
            'fecha' => $fl,
            'estado' => $t['estado'] ?? '',
        ];
    }
}

$pageTitle = 'Calendario — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Calendario']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/calendario.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
