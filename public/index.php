<?php
/**
 * Punto de entrada principal. Redirige a login si no hay sesión, sino a dashboard.
 */
session_name('sigtae_session');
session_start();

$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';

$auth = sigtae_auth_service($container, $basePath);
$user = $auth->currentUser();

if (!$user) {
    header('Location: ' . $basePath . '/login.php');
    exit;
}
header('Location: ' . $basePath . '/dashboard.php');
exit;
