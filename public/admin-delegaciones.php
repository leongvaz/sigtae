<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$delegationRepo = $container['repositories']['delegation'];
$userRepo = $container['repositories']['user'];
$delegations = $delegationRepo->findAll();
$userById = [];
foreach ($userRepo->findAll() as $u) { $userById[$u['id']] = $u; }

$pageTitle = 'Delegaciones temporales — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Admin', 'url' => '#'], ['label' => 'Delegaciones']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/admin-delegaciones.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
