<?php
/**
 * Carga sesión y verifica inactividad. Incluir al inicio de cada endpoint.
 */
$projectRoot = dirname(__DIR__, 4);
$container = require $projectRoot . '/app/bootstrap.php';

$basePath = $container['base_path'] ?? '';
// En endpoints: base_path termina como /metrologia/sig/api, queremos la raíz de la app.
$appBasePath = preg_replace('#/metrologia/sig(?:/api)?$#', '', $basePath);

$auth = sigtae_auth_service($container, $appBasePath);
$auth->requireAuth();
