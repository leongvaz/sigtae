<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$pageTitle = 'Preparación — Soltras';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Preparación de medidores'], ['label' => 'Soltras']];
$currentUser = $user;
ob_start();
sigtae_page_header('Soltras', 'Módulo pendiente', '');
?>
<div class="card">
    <div class="card-body">
        <?php sigtae_empty_state('Módulo pendiente de implementación.', 'bi-diagram-3'); ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include $base . '/views/layout.php';

