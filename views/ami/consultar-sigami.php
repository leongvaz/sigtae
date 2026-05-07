<?php
$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Consultar SIGAMI', 'Consultas del estado SIGAMI (próximamente)', $actions);
?>

<?php sigtae_empty_state('Módulo en construcción. Aquí irá la consulta de SIGAMI.', 'bi-search'); ?>

