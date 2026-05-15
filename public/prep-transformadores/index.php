<?php
/**
 * Punto de entrada legacy del módulo.
 * Redirige a la versión integrada en el dashboard SIGTAE.
 */
$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
header('Location: ' . rtrim($basePath, '/') . '/prep-transformadores-instrumento.php');
exit;
