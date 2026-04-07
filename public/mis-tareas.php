<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$user = $auth->requireAuth();

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$userRepo = $container['repositories']['user'];

$tasks = $taskRepo->findByResponsable($user['id']);
$withState = [];
foreach ($tasks as $t) {
    $withState[] = $stateService->computeState($t);
}
usort($withState, fn($a, $b) => strcmp($a['fecha_limite'] ?? '9999', $b['fecha_limite'] ?? '9999'));

$pageTitle = 'Mis tareas — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Mis tareas']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/mis-tareas.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
