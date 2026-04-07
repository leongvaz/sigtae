<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$user = $auth->requireAuth();

$performanceService = $container['PerformanceService'];
$userRepo = $container['repositories']['user'];

$ranking = $performanceService->getRanking();
foreach ($ranking as &$r) {
    $u = $userRepo->find($r['responsable_id']);
    $r['nombre'] = $u ? $u['nombre'] : $r['responsable_id'];
}
unset($r);

$pageTitle = 'Ranking de desempeño — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Ranking']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/ranking.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
