<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccessRoute($user, basename($_SERVER['PHP_SELF'] ?? 'metrologia-sig.php'))) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Metrología';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger">
        <i class="bi bi-shield-lock me-1"></i>
        No tiene permisos para acceder al módulo de Metrología.
    </div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$pageTitle = 'Metrología — SIG';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'SIG']];
$currentUser = $user;

ob_start();
include $base . '/views/metrologia/sig.php';
$content = ob_get_clean();

$extraStyles = [
    'metrologia/sig/assets/css/style.css',
];
$extraScripts = [
    'metrologia/sig/assets/js/app.js',
];
$inlineScript = 'window.__SIG_MODULE_BASE = ' . json_encode(($basePath ?? '') . '/metrologia/sig') . ';';

include $base . '/views/layout.php';

