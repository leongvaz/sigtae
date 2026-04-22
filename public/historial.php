<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$historyService = $container['HistoryService'];
$userRepo = $container['repositories']['user'];
$taskRepo = $container['repositories']['task'];

$filtroTipo = trim($_GET['tipo'] ?? '');
$filtroUsuario = trim($_GET['usuario'] ?? '');
$filtroTarea = trim($_GET['tarea'] ?? '');
$filtros = array_filter([
    'tipo_evento' => $filtroTipo ?: null,
    'usuario_id' => $filtroUsuario ?: null,
    'tarea_id' => $filtroTarea ?: null,
]);
$historial = $historyService->getGlobal($filtros);
$historial = array_slice($historial, 0, 200);

$users = $userRepo->findAll();
$taskRepo = $container['repositories']['task'];
$constants = $container['constants'];
$tiposEvento = $constants['tipos_evento'] ?? [];

$pageTitle = 'Historial — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Historial']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/historial.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
