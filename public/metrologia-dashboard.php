<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccess($user)) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Metrología';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger">
        <i class="bi bi-shield-lock me-1"></i>
        No tiene permisos para acceder al módulo de Metrología.
    </div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$solRepo = $container['repositories']['met_solicitud'];
$expRepo = $container['repositories']['met_expediente'];
$catalogos = $container['metrologia_catalogos'] ?? [];

// Filtros
$anio = (int)($_GET['anio'] ?? date('Y'));
$mes = (int)($_GET['mes'] ?? 0); // 0 = todos
$zona = trim((string)($_GET['zona_id'] ?? ''));
$estado = trim((string)($_GET['estado_expediente'] ?? ''));
$tecnico = trim((string)($_GET['tecnico_rpe'] ?? ''));

$filters = [
    'anio' => $anio,
];
if ($mes > 0) $filters['mes'] = $mes;
if ($zona !== '') $filters['zona_id'] = $zona;
if ($estado !== '') $filters['estado_expediente'] = $estado;
if ($tecnico !== '') $filters['tecnico_rpe'] = $tecnico;

$expedientes = $expRepo->findByFilters($filters);

// KPIs: por estado
$k = [
    'programados' => 0,
    'recibidos' => 0,
    'en_proceso' => 0,
    'pendiente_autorizacion' => 0,
    'listo_entrega' => 0,
    'entregados' => 0,
    'pendientes_zona' => 0,
    'atrasados' => 0,
];
foreach ($expedientes as $e) {
    if (!empty($e['programa_anual'])) $k['programados']++;
    $st = (string)($e['estado_expediente'] ?? '');
    if (in_array($st, ['recibido','asignado','en_calibracion','pendiente_autorizacion','autorizado','listo_para_entrega','entregado'], true)) {
        $k['recibidos']++;
    }
    if (in_array($st, ['asignado','en_calibracion'], true)) $k['en_proceso']++;
    if ($st === 'pendiente_autorizacion') $k['pendiente_autorizacion']++;
    if (in_array($st, ['autorizado','listo_para_entrega'], true)) $k['listo_entrega']++;
    if ($st === 'entregado') $k['entregados']++;
    if (in_array($st, ['solicitud_recibida','solicitud_validada'], true)) $k['pendientes_zona']++;
    // atraso: vigencia/fecha_limite_esperada vencida y no entregado
    $lim = (string)($e['vigencia_esperada'] ?? '');
    if ($lim !== '' && $lim < date('Y-m-d') && $st !== 'entregado' && $st !== 'cancelado') $k['atrasados']++;
}

$pageTitle = 'Metrología — Dashboard';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Dashboard']];
$currentUser = $user;
ob_start();
include $base . '/views/metrologia/dashboard.php';
$content = ob_get_clean();
include $base . '/views/layout.php';

