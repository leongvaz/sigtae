<?php

use App\Services\UserAdminGuard;

$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

if (!UserAdminGuard::canAccessOfficeAdmin($user)) {
    http_response_code(403);
    echo 'Solo super administradores pueden gestionar oficinas.';
    exit;
}

$officeRepo = $container['repositories']['office'];
$userRepo = $container['repositories']['user'];
$deptRepo = $container['repositories']['department'];
$departments = $deptRepo->findAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $deptId = trim($_POST['departamento_id'] ?? '');
        if ($nombre === '' || $deptId === '') {
            $error = 'Nombre y departamento son obligatorios.';
        } else {
            $officeRepo->save([
                'nombre' => $nombre,
                'departamento_id' => $deptId,
            ]);
            $success = 'Oficina creada.';
        }
    } elseif ($action === 'update' && ($id = trim($_POST['id'] ?? '')) !== '') {
        $of = $officeRepo->find($id);
        if (!$of) {
            $error = 'Oficina no encontrada.';
        } else {
            $of['nombre'] = trim($_POST['nombre'] ?? $of['nombre']);
            $of['departamento_id'] = trim($_POST['departamento_id'] ?? $of['departamento_id']);
            $officeRepo->save($of);
            $success = 'Oficina actualizada.';
        }
    } elseif ($action === 'delete' && ($id = trim($_POST['id'] ?? '')) !== '') {
        $users = $userRepo->findAll();
        $inUse = false;
        foreach ($users as $u) {
            if (($u['oficina_id'] ?? '') === $id) {
                $inUse = true;
                break;
            }
        }
        if ($inUse) {
            $error = 'No se puede eliminar: hay usuarios asignados a esta oficina.';
        } elseif ($officeRepo->delete($id)) {
            $success = 'Oficina eliminada.';
        } else {
            $error = 'No se pudo eliminar.';
        }
    }
}

$offices = $officeRepo->findAll();
$editId = trim((string)($_GET['edit'] ?? ''));
$editOffice = $editId !== '' ? $officeRepo->find($editId) : null;

$pageTitle = 'Administración de oficinas — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Admin', 'url' => '#'], ['label' => 'Oficinas']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/admin-oficinas.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
