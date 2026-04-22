<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$auth->requireAuth();
$oid = $container['constants']['oficina_preparacion_medidores_id'] ?? 'of-lab';
header('Location: ' . $basePath . '/seguimiento.php?oficina=' . rawurlencode($oid));
exit;
