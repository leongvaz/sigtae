<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$user = $auth->requireAuth();

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    header('Location: ' . $basePath . '/mis-tareas.php');
    exit;
}

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$userRepo = $container['repositories']['user'];
$historyService = $container['HistoryService'];
$permissionService = $container['PermissionService'];
$constants = $container['constants'];

$task = $taskRepo->find($id);
if (!$task) {
    header('Location: ' . $basePath . '/mis-tareas.php');
    exit;
}
$task = $stateService->computeState($task);

$asignador = $userRepo->find($task['asignador_id'] ?? '');
$responsable = $userRepo->find($task['responsable_id'] ?? '');
$puedeEvaluar = $permissionService->canEvaluate($user, $task, $responsable);
$esResponsable = ($task['responsable_id'] ?? '') === $user['id'];
$esAsignador = ($task['asignador_id'] ?? '') === $user['id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'comentario_responsable' && $esResponsable) {
        $task['comentarios_responsable'] = trim($_POST['comentarios_responsable'] ?? '');
        $taskRepo->save($task);
        $historyService->log($task['id'], $user['id'], 'comentario_agregado', 'Comentario del responsable actualizado', []);
        $success = 'Comentario guardado.';
    } elseif ($action === 'evidencia' && $esResponsable) {
        $nombre = trim($_POST['ev_nombre'] ?? '') ?: 'Evidencia';
        $comentario = trim($_POST['ev_comentario'] ?? '');
        $evidencias = $task['evidencias'] ?? [];
        $version = count($evidencias) + 1;
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
        $evidencias[] = [
            'id' => 'ev-' . uniqid(),
            'nombre_archivo' => $nombre,
            'ruta' => '',
            'tipo_archivo' => 'texto',
            'tamaño' => 0,
            'version' => $version,
            'fecha_subida' => $now,
            'usuario_subida' => $user['id'],
            'comentario' => $comentario,
        ];
        $task['evidencias'] = $evidencias;
        $task = $stateService->computeState($task);
        $taskRepo->save($task);
        $historyService->log($task['id'], $user['id'], 'evidencia_subida', "Evidencia v{$version} agregada: {$nombre}", []);
        $task = $taskRepo->find($id);
        $task = $stateService->computeState($task);
        $success = 'Evidencia registrada.';
    } elseif ($action === 'evaluar' && $puedeEvaluar) {
        $dictamen = $_POST['dictamen'] ?? '';
        $comentarios = trim($_POST['comentarios_evaluador'] ?? '');
        if ($dictamen === '') {
            $error = 'Seleccione un dictamen.';
        } else {
            $task['dictamen'] = $dictamen;
            $task['comentarios_evaluador'] = $comentarios;
            $task['evaluacion'] = $dictamen === 'satisfactoria' ? 100 : ($dictamen === 'satisfactoria_fuera_tiempo' ? 50 : ($dictamen === 'no_presentada' ? 0 : 50));
            $task['porcentaje_cumplimiento'] = $stateService->porcentajeCumplimiento($task);
            $task['fecha_entrega'] = $task['fecha_entrega'] ?? (count($task['evidencias'] ?? []) > 0 ? substr($task['evidencias'][0]['fecha_subida'] ?? '', 0, 10) : null);
            $task = $stateService->computeState($task);
            $taskRepo->save($task);
            $historyService->log($task['id'], $user['id'], 'evaluacion_registrada', "Evaluación: {$dictamen}. {$comentarios}", []);
            $task = $taskRepo->find($id);
            $task = $stateService->computeState($task);
            $success = 'Evaluación registrada.';
        }
    }
}

$dictamenOpts = $constants['dictamen'] ?? [];
$historial = $historyService->getByTask($id);

$pageTitle = 'Tarea ' . ($task['folio'] ?? '') . ' — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Mis tareas', 'url' => '/mis-tareas.php'], ['label' => $task['folio'] ?? '']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/tarea.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
