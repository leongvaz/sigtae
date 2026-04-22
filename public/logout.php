<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$auth->startSession();
$auth->logout();
header('Location: ' . $basePath . '/login.php');
exit;
