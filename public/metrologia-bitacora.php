<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
$script = basename($_SERVER['PHP_SELF'] ?? 'metrologia-bitacora.php');
if (!$metPerm->canAccessRoute($user, $script)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Metrología';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Bitácora']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder al módulo de Metrología.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$bitRepo = $container['repositories']['met_bitacora_equipos'];
$importSvc = $container['MetrologiaProgramaImportService'];
$programaPath = $base . '/Programa 2026.txt';

// Seed automático (solo si vacía)
if (empty($bitRepo->findAll()) && is_file($programaPath)) {
    $importSvc->reimportToRepository($programaPath, $bitRepo, true);
}

// Filtros
$anio = (int)($_GET['anio'] ?? date('Y'));
$fZona = trim((string)($_GET['zona'] ?? ''));
$fArea = trim((string)($_GET['area'] ?? ''));
$fQuery = trim((string)($_GET['q'] ?? ''));
$fEstado = trim((string)($_GET['estado'] ?? ''));
$fOrigen = trim((string)($_GET['origen'] ?? ''));
if (!in_array($fOrigen, ['', 'con_recepcion', 'sin_recepcion'], true)) {
    $fOrigen = '';
}

$bitacora = $bitRepo->findByFilters([
    'anio' => $anio,
    'zona' => $fZona !== '' ? $fZona : null,
    'area' => $fArea !== '' ? $fArea : null,
    'q' => $fQuery !== '' ? $fQuery : null,
    'estado' => $fEstado !== '' ? $fEstado : null,
    'origen' => $fOrigen !== '' ? $fOrigen : null,
]);

$zonasEntrega = [];

$pageTitle = 'Metrología — Bitácora';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Bitácora']];
$currentUser = $user;
ob_start();
include $base . '/views/metrologia/bitacora.php';
$content = ob_get_clean();
include $base . '/views/layout.php';
