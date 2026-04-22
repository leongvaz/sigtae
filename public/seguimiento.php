<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$userRepo = $container['repositories']['user'];

$filtroEstado = trim($_GET['estado'] ?? '');
$filtroOficina = trim($_GET['oficina'] ?? '');
$filtroResponsable = trim($_GET['responsable'] ?? '');

$tasks = $taskRepo->findAll();
$withState = [];
foreach ($tasks as $t) {
    $withState[] = $stateService->computeState($t);
}
if ($filtroEstado !== '') {
    $withState = array_values(array_filter($withState, fn($t) => ($t['estado'] ?? '') === $filtroEstado));
}
if ($filtroOficina !== '') {
    $withState = array_values(array_filter($withState, fn($t) => ($t['oficina_id'] ?? '') === $filtroOficina));
}
if ($filtroResponsable !== '') {
    $withState = array_values(array_filter($withState, fn($t) => ($t['responsable_id'] ?? '') === $filtroResponsable));
}
usort($withState, fn($a, $b) => strcmp($a['fecha_limite'] ?? '9999', $b['fecha_limite'] ?? '9999'));

$officeRepo = $container['repositories']['office'];
$offices = $officeRepo->findAll();
$users = $userRepo->findAll();

$constants = $container['constants'];
$oficinaMetrologiaId = $constants['oficina_metrologia_id'] ?? 'of-metro';
$oficinaPreparacionId = $constants['oficina_preparacion_medidores_id'] ?? 'of-lab';
$reportOficinaId = in_array($filtroOficina, [$oficinaMetrologiaId, $oficinaPreparacionId], true) ? $filtroOficina : $oficinaMetrologiaId;

$pageTitle = 'Seguimiento — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Seguimiento']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/seguimiento.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
