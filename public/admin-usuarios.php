<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$user = $auth->requireAuth();

$userRepo = $container['repositories']['user'];
$officeRepo = $container['repositories']['office'];
$users = $userRepo->findAll();
$offices = $officeRepo->findAll();

$pageTitle = 'Administración de usuarios — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Admin', 'url' => '#'], ['label' => 'Usuarios']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/admin-usuarios.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
