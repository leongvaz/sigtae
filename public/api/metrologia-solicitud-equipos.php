<?php
/**
 * API: búsqueda de equipos en bitácora por serie o prefijo de zona.
 * GET ?q=DM21&zona_prefijo=dm21&limit=20
 */
$base = dirname(__DIR__, 2);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

header('Content-Type: application/json; charset=utf-8');

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccess($user)) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$bitacoraRepo = $container['repositories']['met_bitacora_equipos'];

$q            = strtoupper(trim((string)($_GET['q'] ?? '')));
$zonaPrefijo  = strtolower(trim((string)($_GET['zona_prefijo'] ?? '')));
$limit        = min(50, max(1, (int)($_GET['limit'] ?? 20)));

// Si el usuario es de zona y no se pasa zona_prefijo, usar el del usuario
if ($zonaPrefijo === '' && $metPerm->isZonaUser($user)) {
    $zonaPrefijo = $metPerm->getZonaPrefijo($user);
}

$all = $bitacoraRepo->findAll();
$results = [];

foreach ($all as $e) {
    $zona = strtolower((string)($e['zona'] ?? $e['zona_id'] ?? ''));
    $serie = strtoupper((string)($e['no_serie'] ?? ''));

    // Filtro por zona (prefijo)
    if ($zonaPrefijo !== '' && strpos($zona, $zonaPrefijo) === false) {
        continue;
    }

    // Filtro por búsqueda
    if ($q !== '') {
        $folio = strtoupper((string)($e['folio'] ?? ''));
        $desc  = strtoupper((string)($e['descripcion'] ?? ''));
        if (
            strpos($serie, $q) === false &&
            strpos($folio, $q) === false &&
            strpos($desc, $q) === false
        ) {
            continue;
        }
    }

    $results[] = [
        'id'          => $e['id'] ?? '',
        'folio'       => $e['folio'] ?? '',
        'no_serie'    => $e['no_serie'] ?? '',
        'marca'       => $e['marca'] ?? '',
        'modelo'      => $e['modelo'] ?? '',
        'descripcion' => $e['descripcion'] ?? '',
        'zona'        => $e['zona'] ?? $e['zona_id'] ?? '',
        'area'        => $e['area'] ?? $e['area_id'] ?? '',
        'estado'      => $e['estado'] ?? '',
    ];

    if (count($results) >= $limit) break;
}

echo json_encode(['equipos' => $results]);
