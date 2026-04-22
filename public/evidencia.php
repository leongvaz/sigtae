<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$taskId = trim($_GET['task_id'] ?? '');
$evId = trim($_GET['ev_id'] ?? '');
if ($taskId === '' || $evId === '') {
    http_response_code(400);
    echo "Solicitud inválida.";
    exit;
}

$taskRepo = $container['repositories']['task'];
$userRepo = $container['repositories']['user'];
$permissionService = $container['PermissionService'];

$task = $taskRepo->find($taskId);
if (!$task) {
    http_response_code(404);
    echo "No encontrado.";
    exit;
}

$responsable = $userRepo->find($task['responsable_id'] ?? '');
$canView =
    (($task['responsable_id'] ?? '') === ($user['id'] ?? '')) ||
    (($task['asignador_id'] ?? '') === ($user['id'] ?? '')) ||
    $permissionService->canEvaluate($user, $task, $responsable);

if (!$canView) {
    http_response_code(403);
    echo "No autorizado.";
    exit;
}

$evidencias = $task['evidencias'] ?? [];
$ev = null;
foreach ($evidencias as $item) {
    if (($item['id'] ?? '') === $evId) {
        $ev = $item;
        break;
    }
}
if (!$ev || empty($ev['ruta'])) {
    http_response_code(404);
    echo "Evidencia no encontrada.";
    exit;
}

$path = (string)$ev['ruta'];
if (!is_file($path)) {
    http_response_code(404);
    echo "Archivo no disponible.";
    exit;
}

$mime = (string)($ev['tipo_mime'] ?? '');
if ($mime === '' && function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)finfo_file($finfo, $path) : '';
    if ($finfo) { finfo_close($finfo); }
}
if ($mime === '') {
    $mime = 'application/octet-stream';
}

$downloadName = (string)($ev['nombre_original'] ?? '');
if ($downloadName === '') {
    $downloadName = (string)($ev['nombre_archivo'] ?? 'evidencia');
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($ext !== '' && !str_ends_with(strtolower($downloadName), '.' . $ext)) {
    $downloadName .= '.' . $ext;
}

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));

// Inline para imágenes/video/pdf; attachment para el resto.
$inline = str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || $mime === 'application/pdf';
$disposition = $inline ? 'inline' : 'attachment';
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $downloadName) . '"');

readfile($path);
exit;

