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

// Helpers de UI reutilizables (partials) cargados globalmente
$uiPartials = dirname(__DIR__) . '/views/partials/ui.php';
if (is_file($uiPartials)) {
    require_once $uiPartials;
}

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

$adValidator = new \App\Services\AdDirectoryValidationService($config);
$authLocalPasswordFallback = (bool)($config['auth_local_password_fallback'] ?? false);

if (!function_exists('sigtae_auth_service')) {
    /**
     * @param array<string, mixed> $container
     */
    function sigtae_auth_service(array $container, string $basePath): \App\Services\AuthService
    {
        return new \App\Services\AuthService($container['repositories']['user'], [
            'base_path' => $basePath,
            'ad_validator' => $container['ad_validator'],
            'auth_local_password_fallback' => $container['auth_local_password_fallback'],
        ]);
    }
}

// Ruta base para subdirectorio (ej. /sigtae/public). Vacío si la app está en la raíz.
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = $scriptName !== '' ? rtrim(dirname($scriptName), '/\\') : '';
if ($basePath === '.' || $basePath === '\\') {
    $basePath = '';
}
$basePath = str_replace('\\', '/', $basePath);
// Tras rewrite desde la raíz del proyecto, SCRIPT_NAME sigue apuntando a public/; quitarlo de la URL visible.
if ($basePath !== '' && substr($basePath, -7) === '/public') {
    $basePath = substr($basePath, 0, -7);
}

return [
    'config' => $config,
    'constants' => $constants,
    'base_path' => $basePath,
    'repositories' => $repositories,
    'ad_validator' => $adValidator,
    'auth_local_password_fallback' => $authLocalPasswordFallback,
    'TaskStateService' => $stateService,
    'PermissionService' => $permissionService,
    'PerformanceService' => $performanceService,
    'HistoryService' => $historyService,
];
