<?php

use App\Services\UserAdminGuard;

$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

if (!UserAdminGuard::canAccessUserAdmin($user)) {
    http_response_code(403);
    echo 'No tiene permiso para administrar usuarios.';
    exit;
}

$userRepo = $container['repositories']['user'];
$officeRepo = $container['repositories']['office'];
$deptRepo = $container['repositories']['department'];
$offices = $officeRepo->findAll();
$departments = $deptRepo->findAll();
$isSuper = UserAdminGuard::isSuperAdmin($user);

$config = $container['config'] ?? [];
$cfgAd = $config['ad_validation'] ?? [];
$adLoginEnabled = !empty($cfgAd['enabled']) && trim((string)($cfgAd['url'] ?? '')) !== '';
$showLocalPasswordFields = !$adLoginEnabled;

$error = '';
$success = '';
$editUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $rpe = strtoupper(trim($_POST['rpe'] ?? ''));
        $nombre = trim($_POST['nombre'] ?? '');
        $passwordPlain = $showLocalPasswordFields ? (string)($_POST['password'] ?? '') : '';
        if ($rpe === '' || $nombre === '') {
            $error = 'Indique RPE y nombre.';
        } elseif ($userRepo->findByRpe($rpe)) {
            $error = 'Ya existe un usuario con ese RPE.';
        } else {
            if ($isSuper) {
                $nivel = (int)($_POST['nivel_jerarquico'] ?? 3);
                $oficinaId = trim((string)($_POST['oficina_id'] ?? ''));
                $deptId = trim((string)($_POST['departamento_id'] ?? 'dept-lab-metro'));
                $supId = trim((string)($_POST['supervisor_id'] ?? '')) ?: null;
                $new = [
                    'rpe' => $rpe,
                    'nombre' => $nombre,
                    'email' => '',
                    'password_hash' => password_hash(
                        $passwordPlain !== '' ? $passwordPlain : ($adLoginEnabled ? bin2hex(random_bytes(24)) : 'Sigtae2026!'),
                        PASSWORD_DEFAULT
                    ),
                    'nivel_jerarquico' => $nivel,
                    'cargo' => trim($_POST['cargo'] ?? '') ?: 'Usuario',
                    'oficina_id' => $oficinaId,
                    'departamento_id' => $deptId,
                    'supervisor_id' => $supId,
                    'puede_asignar' => !empty($_POST['puede_asignar']),
                    'puede_evaluar' => !empty($_POST['puede_evaluar']),
                    'puede_delegar' => !empty($_POST['puede_delegar']),
                    'puede_asignar_pares' => !empty($_POST['puede_asignar_pares']),
                    'asignable_por_jefes_oficina' => !empty($_POST['asignable_por_jefes_oficina']),
                    'es_super_admin' => !empty($_POST['es_super_admin']),
                    'alcance_asignacion' => isset($_POST['alcance']) && is_array($_POST['alcance']) ? array_values($_POST['alcance']) : ['nivel_3'],
                    'activo' => !isset($_POST['activo']) || !empty($_POST['activo']),
                ];
            } else {
                $oid = trim((string)($user['oficina_id'] ?? ''));
                $new = [
                    'rpe' => $rpe,
                    'nombre' => $nombre,
                    'email' => '',
                    'password_hash' => password_hash(
                        $passwordPlain !== '' ? $passwordPlain : ($adLoginEnabled ? bin2hex(random_bytes(24)) : 'Sigtae2026!'),
                        PASSWORD_DEFAULT
                    ),
                    'nivel_jerarquico' => 3,
                    'cargo' => trim($_POST['cargo'] ?? '') ?: 'Supervisor',
                    'oficina_id' => $oid,
                    'departamento_id' => $user['departamento_id'] ?? 'dept-lab-metro',
                    'supervisor_id' => $user['id'],
                    'puede_asignar' => !empty($_POST['puede_asignar']),
                    'puede_evaluar' => !empty($_POST['puede_evaluar']),
                    'puede_delegar' => false,
                    'puede_asignar_pares' => !empty($_POST['puede_asignar_pares']),
                    'asignable_por_jefes_oficina' => false,
                    'es_super_admin' => false,
                    'alcance_asignacion' => [],
                    'activo' => true,
                ];
            }
            $userRepo->save($new);
            if ($adLoginEnabled) {
                $success = 'Usuario creado. El acceso a SIGTAE es con RPE y contraseña del Directorio Activo (no se guarda contraseña en este formulario).';
            } else {
                $success = 'Usuario creado. Contraseña inicial: ' . ($passwordPlain !== '' ? 'la indicada.' : 'Sigtae2026! (cámbiela en la primera edición).');
            }
        }
    } elseif ($action === 'update' && ($id = trim($_POST['id'] ?? '')) !== '') {
        $target = $userRepo->find($id);
        if (!$target) {
            $error = 'Usuario no encontrado.';
        } elseif (!UserAdminGuard::canEditUser($user, $target)) {
            $error = 'No puede editar a este usuario.';
        } else {
            $target['nombre'] = trim($_POST['nombre'] ?? $target['nombre']);
            $target['email'] = '';
            $target['cargo'] = trim($_POST['cargo'] ?? $target['cargo']);
            if ($isSuper) {
                $target['nivel_jerarquico'] = (int)($_POST['nivel_jerarquico'] ?? $target['nivel_jerarquico']);
                $target['oficina_id'] = trim((string)($_POST['oficina_id'] ?? ''));
                $target['departamento_id'] = trim((string)($_POST['departamento_id'] ?? $target['departamento_id']));
                $sup = trim((string)($_POST['supervisor_id'] ?? ''));
                $target['supervisor_id'] = $sup !== '' ? $sup : null;
                $target['puede_asignar'] = !empty($_POST['puede_asignar']);
                $target['puede_evaluar'] = !empty($_POST['puede_evaluar']);
                $target['puede_delegar'] = !empty($_POST['puede_delegar']);
                $target['puede_asignar_pares'] = !empty($_POST['puede_asignar_pares']);
                $target['asignable_por_jefes_oficina'] = !empty($_POST['asignable_por_jefes_oficina']);
                $target['es_super_admin'] = !empty($_POST['es_super_admin']);
                $target['alcance_asignacion'] = isset($_POST['alcance']) && is_array($_POST['alcance']) ? array_values($_POST['alcance']) : ($target['alcance_asignacion'] ?? []);
            } else {
                $target['puede_asignar'] = !empty($_POST['puede_asignar']);
                $target['puede_evaluar'] = !empty($_POST['puede_evaluar']);
                $target['puede_asignar_pares'] = !empty($_POST['puede_asignar_pares']);
            }
            if ($showLocalPasswordFields) {
                $pw = (string)($_POST['password'] ?? '');
                if ($pw !== '') {
                    $target['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
                }
            }
            $target['activo'] = !empty($_POST['activo']);
            $userRepo->save($target);
            $success = 'Usuario actualizado.';
        }
    } elseif ($action === 'deactivate' && ($id = trim($_POST['id'] ?? '')) !== '') {
        $target = $userRepo->find($id);
        if (!$target) {
            $error = 'Usuario no encontrado.';
        } elseif (!UserAdminGuard::canEditUser($user, $target)) {
            $error = 'No autorizado.';
        } elseif (($target['id'] ?? '') === ($user['id'] ?? '')) {
            $error = 'No puede desactivarse a sí mismo.';
        } else {
            $target['activo'] = false;
            $userRepo->save($target);
            $success = 'Usuario desactivado.';
        }
    } elseif ($action === 'delete' && ($id = trim($_POST['id'] ?? '')) !== '') {
        $target = $userRepo->find($id);
        if ($target && UserAdminGuard::canDeleteUser($user, $target)) {
            $userRepo->delete($id);
            $success = 'Usuario eliminado del registro.';
        } else {
            $error = 'No autorizado para eliminar.';
        }
    }
}

if (isset($_GET['edit'])) {
    $eid = trim((string)$_GET['edit']);
    $editUser = $userRepo->find($eid);
    if ($editUser && !UserAdminGuard::canEditUser($user, $editUser)) {
        $editUser = null;
        $error = 'No puede editar ese usuario.';
    }
}

$deptIdForSuper = ($isSuper && !empty($editUser))
    ? ($editUser['departamento_id'] ?? 'dept-lab-metro')
    : ($user['departamento_id'] ?? 'dept-lab-metro');
$usersForSupervisor = $isSuper
    ? array_values(array_filter(
        $userRepo->findByDepartment($deptIdForSuper),
        fn($u) => ($u['activo'] ?? true) && (int)($u['nivel_jerarquico'] ?? 999) <= 2
    ))
    : [];

$users = $userRepo->findAll();
if (!$isSuper) {
    $oid = trim((string)($user['oficina_id'] ?? ''));
    $jid = $user['id'] ?? '';
    $users = array_values(array_filter(
        $users,
        fn($u) => ($u['oficina_id'] ?? '') === $oid
            && (int)($u['nivel_jerarquico'] ?? 0) === 3
            && ($u['supervisor_id'] ?? '') === $jid
    ));
}

$pageTitle = 'Administración de usuarios — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Admin', 'url' => '#'], ['label' => 'Usuarios']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/admin-usuarios.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
