<?php
/**
 * Carga tareas de ejemplo en tasks.json (solo si está vacío).
 * Ejecutar una vez: /seed-tasks.php
 */
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$auth->requireAuth();

$taskRepo = $container['repositories']['task'];
$all = $taskRepo->findAll();
if (count($all) > 0) {
    header('Location: ' . $basePath . '/dashboard.php?msg=ya_hay_tareas');
    exit;
}
$seedPath = $base . '/storage/json/tasks_seed.json';
if (!is_file($seedPath)) {
    header('Location: ' . $basePath . '/dashboard.php?msg=no_seed');
    exit;
}
$seed = json_decode(file_get_contents($seedPath), true);
if (!is_array($seed)) {
    header('Location: ' . $basePath . '/dashboard.php?msg=error_seed');
    exit;
}
$storagePath = $base . '/storage/json/tasks.json';
file_put_contents($storagePath, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: ' . $basePath . '/dashboard.php?msg=seed_ok');
exit;
