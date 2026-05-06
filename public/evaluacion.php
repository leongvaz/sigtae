<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$userRepo = $container['repositories']['user'];
$permissionService = $container['PermissionService'];

$tasks = $taskRepo->findAll();
$pendientesEvaluar = [];
foreach ($tasks as $t) {
    $t = $stateService->computeState($t);
    if (!empty($t['cancelada'])) {
        continue;
    }
    $evCount = count($t['evidencias'] ?? []);
    $eval = $t['evaluacion'] ?? null;
    $dict = strtolower((string)($t['dictamen'] ?? ''));
    $evalVer = (int)($t['evaluacion_version'] ?? 0);
    $hayNuevoIntento = ($dict === 'rechazada' && $evCount > $evalVer);
    $pendiente = ($evCount > 0) && (($eval === null) || $hayNuevoIntento) && ($t['estado'] ?? '') !== 'incumplimiento';
    if ($pendiente) {
        $resp = $userRepo->find($t['responsable_id'] ?? '');
        if ($permissionService->canEvaluate($user, $t, $resp)) {
            $pendientesEvaluar[] = $t;
        }
    }
}
usort($pendientesEvaluar, function ($a, $b) {
    return strcmp($a['fecha_limite'] ?? '', $b['fecha_limite'] ?? '');
});

$pageTitle = 'Evaluación — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Evaluación']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/evaluacion.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
