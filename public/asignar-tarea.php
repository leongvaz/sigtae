<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$permissionService = $container['PermissionService'];
$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$historyService = $container['HistoryService'];
$userRepo = $container['repositories']['user'];
$officeRepo = $container['repositories']['office'];

$assignable = $permissionService->getAssignableUsers($user);
$offices = $officeRepo->findAll();
$constants = $container['constants'];
$prioridades = ['alta', 'media', 'baja'];
$categorias = $constants['categorias_tarea'] ?? ['operativa', 'administrativo', 'aplicativo'];
$modalidades = $constants['modalidades_asignacion'] ?? ['diaria', 'programada'];
$tzMx = new \DateTimeZone('America/Mexico_City');
$hoyMx = (new \DateTimeImmutable('now', $tzMx))->format('Y-m-d');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $responsableId = trim($_POST['responsable_id'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';
    $categoria = $_POST['categoria'] ?? 'operativa';
    $modalidad = $_POST['modalidad_asignacion'] ?? 'diaria';
    if (!in_array($modalidad, $modalidades, true)) {
        $modalidad = 'diaria';
    }
    $fechaLimite = trim($_POST['fecha_limite'] ?? '');
    if ($modalidad === 'diaria') {
        $fechaLimite = $hoyMx;
    }
    if ($titulo === '' || $responsableId === '' || $fechaLimite === '') {
        $error = 'Complete título, responsable y fecha límite.';
    } elseif ($modalidad === 'programada' && $fechaLimite < $hoyMx) {
        $error = 'Para tareas programadas la fecha límite no puede ser anterior a hoy.';
    } else {
        $responsable = $userRepo->find($responsableId);
        $check = $permissionService->canAssignTo($user, $responsable);
        if (!$check['allowed']) {
            $error = $check['reason'];
        } else {
            $folio = $taskRepo->nextFolio();
            $task = [
                'folio' => $folio,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'prioridad' => $prioridad,
                'categoria' => $categoria,
                'asignador_id' => $user['id'],
                'responsable_id' => $responsableId,
                'nivel_responsable' => $responsable['nivel_jerarquico'] ?? 3,
                'oficina_id' => $responsable['oficina_id'] ?? '',
                'departamento_id' => $responsable['departamento_id'] ?? '',
                'fecha_asignacion' => (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d'),
                'fecha_limite' => $fechaLimite,
                'fecha_entrega' => null,
                'modalidad_asignacion' => $modalidad,
                'estado' => 'asignada',
                'comentarios_responsable' => '',
                'comentarios_evaluador' => '',
                'evaluacion' => null,
                'dictamen' => null,
                'porcentaje_cumplimiento' => null,
                'evidencias' => [],
                'historial' => [],
            ];
            $task = $stateService->computeState($task);
            $task = $taskRepo->save($task);
            $historyService->log($task['id'], $user['id'], 'tarea_creada', "Tarea creada y asignada a " . ($responsable['nombre'] ?? $responsableId), ['folio' => $folio]);
            $success = 'Tarea ' . $folio . ' asignada correctamente.';
            $_POST = [];
        }
    }
}

$pageTitle = 'Asignar tarea — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Asignar tarea']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/asignar-tarea.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
