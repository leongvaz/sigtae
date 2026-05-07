<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

if (!\App\Services\AmiGuard::canAccess($user)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — AMI';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'AMI'], ['label' => 'Cambio SIGAMI']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder al módulo AMI.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$pageTitle = 'AMI — Cambio SIGAMI';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'AMI'], ['label' => 'Cambio SIGAMI']];
$currentUser = $user;

ob_start();
include $base . '/views/ami/cambio-sigami.php';
$content = ob_get_clean();
include $base . '/views/layout.php';

