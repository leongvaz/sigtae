<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccess($user)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Metrología';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Recepción (Formato)']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder al módulo de Metrología.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$recepRepo = $container['repositories']['met_recepcion'];

$rid = (string)($_GET['rid'] ?? '');
if ($rid === '') {
    http_response_code(400);
    echo 'RID requerido.';
    exit;
}
$recepcion = $recepRepo->find($rid);
if (!$recepcion) {
    http_response_code(404);
    echo 'Recepción no encontrada.';
    exit;
}

include $base . '/views/metrologia/recepcion-formato.php';

