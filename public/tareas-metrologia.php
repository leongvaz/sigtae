<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$auth->requireAuth();
header('Location: ' . $basePath . '/metrologia-dashboard.php');
exit;
