<?php
// Descarga/visualización de evidencias del Programa de trabajo.

$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$evidRepo = $container['repositories']['programa_evidencia'];

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') {
    http_response_code(400);
    echo 'Solicitud inválida.';
    exit;
}
$ev = $evidRepo->find($id);
if (!$ev) {
    http_response_code(404);
    echo 'No encontrado.';
    exit;
}

// Regla simple: solo autenticación (módulo administrativo). Si se requiere, aquí se puede amarrar por oficina/rol.

$path = (string)($ev['ruta'] ?? '');
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    echo 'Archivo no disponible.';
    exit;
}

$mime = (string)($ev['tipo_mime'] ?? '');
if ($mime === '' && function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)finfo_file($finfo, $path) : '';
    if ($finfo) { finfo_close($finfo); }
}
if ($mime === '') $mime = 'application/octet-stream';

$downloadName = (string)($ev['nombre_original'] ?? '');
if ($downloadName === '') $downloadName = (string)($ev['nombre_archivo'] ?? 'evidencia');

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));

$inline = (substr($mime, 0, 6) === 'image/') || (substr($mime, 0, 6) === 'video/') || $mime === 'application/pdf';
$disposition = $inline ? 'inline' : 'attachment';
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $downloadName) . '"');

readfile($path);
exit;

