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
use App\Repositories\MetrologiaSolicitudRepositoryJson;
use App\Repositories\MetrologiaExpedienteRepositoryJson;
use App\Repositories\MetrologiaRecepcionRepositoryJson;
use App\Repositories\MetrologiaBitacoraEquipoRepositoryJson;
use App\Repositories\MetrologiaEquipoCatalogoRepositoryJson;
use App\Repositories\ProgramaTrabajoRepositoryJson;
use App\Repositories\ProgramaActividadRepositoryJson;
use App\Repositories\ProgramaAvanceRepositoryJson;
use App\Repositories\ProgramaEvidenciaRepositoryJson;
use App\Services\MetrologiaHistoryService;
use App\Services\MetrologiaPermissionService;
use App\Services\MetrologiaFolioService;
use App\Services\MetrologiaRecepcionFolioService;
use App\Services\ProgramaCalendarioService;
use App\Services\MetrologiaEquipoCatalogoSeedService;

$json = function ($file) use ($storagePath) {
    return new JsonStorage($storagePath, $file);
};

$metEquipoCatalogoFile = trim((string)(getenv('SIGTAE_MET_EQUIPO_CATALOGO_JSON') ?: ''));
if ($metEquipoCatalogoFile === '') {
    $metEquipoCatalogoFile = (string)($constants['metrologia_equipos_catalogo_file'] ?? 'metrologia_equipos.json');
}

$repositories = [
    'user' => new UserRepositoryJson($json('users.json')),
    'task' => new TaskRepositoryJson($json('tasks.json')),
    'history' => new HistoryRepositoryJson($json('history.json')),
    'delegation' => new DelegationRepositoryJson($json('delegations.json')),
    'office' => new OfficeRepositoryJson($json('offices.json')),
    'department' => new DepartmentRepositoryJson($json('departments.json')),
    // Módulo Metrología (Fase 1)
    'met_solicitud' => new MetrologiaSolicitudRepositoryJson($json('metrologia_solicitudes.json')),
    'met_expediente' => new MetrologiaExpedienteRepositoryJson($json('metrologia_expedientes.json')),
    // Módulo Metrología (Recepción de equipos)
    'met_recepcion' => new MetrologiaRecepcionRepositoryJson($json('metrologia_recepciones.json')),
    'met_bitacora_equipos' => new MetrologiaBitacoraEquipoRepositoryJson($json('metrologia_bitacora_equipos.json')),
    // Metrología: base maestra de equipos (catálogo). Ver $metEquipoCatalogoFile (env o constants).
    'met_equipo_catalogo' => new MetrologiaEquipoCatalogoRepositoryJson($json($metEquipoCatalogoFile)),
    // Administrativo: programas de trabajo (Gantt)
    'programa_trabajo' => new ProgramaTrabajoRepositoryJson($json('programas_trabajo.json')),
    'programa_actividad' => new ProgramaActividadRepositoryJson($json('programas_actividades.json')),
    'programa_avance' => new ProgramaAvanceRepositoryJson($json('programas_avances.json')),
    'programa_evidencia' => new ProgramaEvidenciaRepositoryJson($json('programas_evidencias.json')),
];

$graciaPct = (float)($constants['gracia_presentacion_porcentaje'] ?? 0.10);
$stateService = new \App\Services\TaskStateService(null, $graciaPct);
$permissionService = new \App\Services\PermissionService($repositories['user'], $repositories['delegation']);
$performanceService = new \App\Services\PerformanceService($repositories['task'], $stateService);
$historyService = new \App\Services\HistoryService($repositories['history']);

$adValidator = new \App\Services\AdDirectoryValidationService($config);
$authLocalPasswordFallback = (bool)($config['auth_local_password_fallback'] ?? false);

// ===== Metrología: catálogos + servicios base =====
$metCatalogos = $json('metrologia_catalogos.json')->read([
    'zonas' => [],
    'areas' => [],
    'oficinas' => [],
    'signatarios' => [],
    'actividades_informe_diario' => [],
]);
$metPermissionService = new MetrologiaPermissionService($metCatalogos);
$metHistoryService = new MetrologiaHistoryService($json('metrologia_historial.json'));
$metFolioService = new MetrologiaFolioService($repositories['met_solicitud'], $repositories['met_expediente']);
$metRecepcionFolioService = new MetrologiaRecepcionFolioService($repositories['met_recepcion'], $repositories['met_bitacora_equipos']);
$metEquipoSeedService = new MetrologiaEquipoCatalogoSeedService(
    $json('metrologia_bitacora_equipos.json'),
    $json('metrologia_programa_2026.json'),
    $repositories['met_equipo_catalogo']
);
$programaCalendarioService = new ProgramaCalendarioService($json('calendario_laboral.json'));

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
// Tras rewrite desde la raíz del proyecto, SCRIPT_NAME incluye /public/...; quitarlo de la URL visible.
// Ejemplos:
// - /Laboratorio/sigtae/public              -> /Laboratorio/sigtae
// - /Laboratorio/sigtae/public/api          -> /Laboratorio/sigtae
// - /Laboratorio/sigtae/public/metrologia/sig/api -> /Laboratorio/sigtae
$posPublic = $basePath !== '' ? strpos($basePath, '/public') : false;
if ($posPublic !== false) {
    $basePath = substr($basePath, 0, $posPublic);
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
    // Metrología (Fase 1)
    'metrologia_catalogos' => $metCatalogos,
    'MetrologiaPermissionService' => $metPermissionService,
    'MetrologiaHistoryService' => $metHistoryService,
    'MetrologiaFolioService' => $metFolioService,
    'MetrologiaRecepcionFolioService' => $metRecepcionFolioService,
    'MetrologiaEquipoCatalogoSeedService' => $metEquipoSeedService,
    'ProgramaCalendarioService' => $programaCalendarioService,
];
