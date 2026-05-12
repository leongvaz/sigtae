<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$pageTitle = 'Preparación — Minutas de supervisión';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Preparación de medidores'], ['label' => 'Minutas de supervisión']];
$currentUser = $user;
ob_start();
sigtae_page_header('Minutas de supervisión', 'Carga documental por año, semestre y zona (pendiente)', '');
?>
<div class="card">
    <div class="card-body">
        <?php sigtae_empty_state('Módulo pendiente de implementación.', 'bi-file-earmark-text'); ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include $base . '/views/layout.php';

