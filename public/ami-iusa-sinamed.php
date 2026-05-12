<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

if (!\App\Services\AmiGuard::canAccess($user)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — AMI';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'AMI'], ['label' => 'IUSA — SINAMED']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder al módulo AMI.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$snapshotPath = $base . '/storage/json/sinamed_automation/snapshot.json';
$snapshot = null;
if (is_readable($snapshotPath)) {
    $raw = json_decode((string)file_get_contents($snapshotPath), true);
    $snapshot = is_array($raw) ? $raw : null;
}

$universeMetaPath = $base . '/storage/json/sinamed_automation/universe_sinamed.json';
$universeGeneratedAt = null;
$universeTotal = null;
if (is_readable($universeMetaPath)) {
    $uj = json_decode((string)file_get_contents($universeMetaPath), true);
    if (is_array($uj)) {
        $universeGeneratedAt = isset($uj['generated_at']) ? (string)$uj['generated_at'] : null;
        if (isset($uj['items']) && is_array($uj['items'])) {
            $universeTotal = count($uj['items']);
        }
    }
}

$pageTitle = 'AMI — IUSA — SINAMED';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'AMI'], ['label' => 'IUSA — SINAMED']];
$currentUser = $user;

ob_start();
include $base . '/views/ami/iusa-sinamed.php';
$content = ob_get_clean();
include $base . '/views/layout.php';
