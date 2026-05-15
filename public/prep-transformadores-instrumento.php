<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$pageTitle = 'Preparación — Transformadores de instrumento';
$breadcrumb = [
    ['label' => 'Inicio', 'url' => '/dashboard.php'],
    ['label' => 'Preparación de medidores'],
    ['label' => 'Transformadores de instrumento'],
];
$currentUser = $user;

$reportesBase = rtrim($basePath, '/') . '/prep-transformadores/';
$appJsPath = __DIR__ . '/prep-transformadores/js/app.js';
$appJsVersion = is_file($appJsPath) ? (string) filemtime($appJsPath) : '1';

$extraStyles = [
    'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;600;700&display=swap',
    'prep-transformadores/css/style.css',
    'prep-transformadores/css/embed.css',
];

$reportesUser = [
    'rpe' => trim((string)($user['rpe'] ?? '')),
    'nombre' => trim((string)($user['nombre'] ?? '')),
    'isAdmin' => !empty($user['es_super_admin']),
    'area' => 'PREPARACION DE MEDIDORES',
];

$inlineScript = 'window.__SIGTAE_REPORTES_BASE = ' . json_encode($reportesBase, JSON_UNESCAPED_SLASHES) . ";\n"
    . 'window.__SIGTAE_REPORTES_USER = ' . json_encode($reportesUser, JSON_UNESCAPED_UNICODE) . ';';

$shellPath = __DIR__ . '/prep-transformadores/partials/app-shell.html';
$shellHtml = is_file($shellPath) ? (string) file_get_contents($shellPath) : '';

ob_start();
?>
<div class="sigtae-reportes-module"
     data-sigtae-rpe="<?= htmlspecialchars($reportesUser['rpe'], ENT_QUOTES, 'UTF-8') ?>"
     data-sigtae-nombre="<?= htmlspecialchars($reportesUser['nombre'], ENT_QUOTES, 'UTF-8') ?>">
<?= $shellHtml ?>
</div>
<?php
$content = ob_get_clean();

$extraScripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
    'prep-transformadores/js/app.js?v=' . rawurlencode($appJsVersion),
];

include $base . '/views/layout.php';
