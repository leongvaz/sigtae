<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAdminEquiposCatalogo($user)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Catálogo de equipos';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Catálogo de equipos']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para administrar el catálogo de equipos.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

// Seed si está vacío
$seed = $container['MetrologiaEquipoCatalogoSeedService'] ?? null;
if ($seed) $seed->ensureSeeded();

$repo = $container['repositories']['met_equipo_catalogo'];
$items = $repo->findByFilters(['q' => trim((string)($_GET['q'] ?? ''))]);

$pageTitle = 'Metrología — Catálogo de equipos';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Catálogo de equipos']];
$currentUser = $user;

ob_start();
include $base . '/views/metrologia/equipos-catalogo.php';
$content = ob_get_clean();
include $base . '/views/layout.php';

