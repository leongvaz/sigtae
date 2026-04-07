<?php
/**
 * Bootstrap: carga de configuración y construcción de dependencias.
 */

$vendor = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendor)) {
    require_once $vendor;
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base = dirname(__DIR__) . '/app/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $rel = str_replace('\\', '/', substr($class, $len)) . '.php';
        $file = $base . $rel;
        if (is_file($file)) require $file;
    });
}

$configPath = __DIR__ . '/config/app.php';
$config = is_file($configPath) ? require $configPath : [];
$constants = require __DIR__ . '/config/constants.php';

$storagePath = $config['storage_path'] ?? __DIR__ . '/../storage/json';

use App\Core\JsonStorage;
use App\Repositories\UserRepositoryJson;
use App\Repositories\TaskRepositoryJson;
use App\Repositories\HistoryRepositoryJson;
use App\Repositories\DelegationRepositoryJson;
use App\Repositories\OfficeRepositoryJson;
use App\Repositories\DepartmentRepositoryJson;

$json = fn($file) => new JsonStorage($storagePath, $file);

$repositories = [
    'user' => new UserRepositoryJson($json('users.json')),
    'task' => new TaskRepositoryJson($json('tasks.json')),
    'history' => new HistoryRepositoryJson($json('history.json')),
    'delegation' => new DelegationRepositoryJson($json('delegations.json')),
    'office' => new OfficeRepositoryJson($json('offices.json')),
    'department' => new DepartmentRepositoryJson($json('departments.json')),
];

$stateService = new \App\Services\TaskStateService();
$permissionService = new \App\Services\PermissionService($repositories['user'], $repositories['delegation']);
$performanceService = new \App\Services\PerformanceService($repositories['task'], $stateService);
$historyService = new \App\Services\HistoryService($repositories['history']);

// Ruta base para subdirectorio (ej. /sigtae/public). Vacío si la app está en la raíz.
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = $scriptName !== '' ? rtrim(dirname($scriptName), '/\\') : '';
if ($basePath === '.' || $basePath === '\\') {
    $basePath = '';
}

return [
    'config' => $config,
    'constants' => $constants,
    'base_path' => $basePath,
    'repositories' => $repositories,
    'TaskStateService' => $stateService,
    'PermissionService' => $permissionService,
    'PerformanceService' => $performanceService,
    'HistoryService' => $historyService,
];
