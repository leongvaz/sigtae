<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$pageTitle = 'Preparación — Compra nacional';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Preparación de medidores'], ['label' => 'Compra nacional']];
$currentUser = $user;
ob_start();
sigtae_page_header('Compra nacional', 'Carga de TXT (pendiente)', '');
?>
<div class="card">
    <div class="card-body">
        <?php sigtae_empty_state('Módulo pendiente de implementación.', 'bi-cart'); ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include $base . '/views/layout.php';

