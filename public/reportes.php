<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$constants = $container['constants'];
$oficinaMetrologiaId = $constants['oficina_metrologia_id'] ?? 'of-metro';
$oficinaPreparacionId = $constants['oficina_preparacion_medidores_id'] ?? 'of-lab';
$oficinasReportePermitidas = [$oficinaMetrologiaId, $oficinaPreparacionId];
$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$userRepo = $container['repositories']['user'];
$officeRepo = $container['repositories']['office'];

$tz = new \DateTimeZone('America/Mexico_City');

/**
 * @return array{0: string, 1: string} Y-m-d
 */
$periodBounds = static function (string $periodo, string $refYmd) use ($tz): array {
    $ref = \DateTimeImmutable::createFromFormat('Y-m-d', $refYmd, $tz);
    if (!$ref) {
        $ref = new \DateTimeImmutable('now', $tz);
    }
    $ref = $ref->setTime(0, 0, 0);
    if ($periodo === 'diario') {
        $d = $ref->format('Y-m-d');
        return [$d, $d];
    }
    if ($periodo === 'semanal') {
        $dow = (int) $ref->format('N');
        $mon = $ref->modify('-' . ($dow - 1) . ' days');
        $sun = $mon->modify('+6 days');
        return [$mon->format('Y-m-d'), $sun->format('Y-m-d')];
    }
    if ($periodo === 'mensual') {
        $first = $ref->modify('first day of this month');
        $last = $first->modify('last day of this month');
        return [$first->format('Y-m-d'), $last->format('Y-m-d')];
    }
    $d = $ref->format('Y-m-d');
    return [$d, $d];
};

$periodo = $_GET['periodo'] ?? 'semanal';
if (!in_array($periodo, ['diario', 'semanal', 'mensual'], true)) {
    $periodo = 'semanal';
}
$fechaRef = trim($_GET['fecha'] ?? '');
if ($fechaRef === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRef)) {
    $fechaRef = (new \DateTimeImmutable('now', $tz))->format('Y-m-d');
}

[$desde, $hasta] = $periodBounds($periodo, $fechaRef);

$oficinaSeleccionada = trim($_GET['oficina'] ?? '');
if (!in_array($oficinaSeleccionada, $oficinasReportePermitidas, true)) {
    $oficinaSeleccionada = $oficinaMetrologiaId;
}

$office = null;
foreach ($officeRepo->findAll() as $o) {
    if (($o['id'] ?? '') === $oficinaSeleccionada) {
        $office = $o;
        break;
    }
}
$nombreOficina = $office['nombre'] ?? $oficinaSeleccionada;

$tasksRaw = $taskRepo->findAll();
$filtradas = [];
foreach ($tasksRaw as $t) {
    if (($t['oficina_id'] ?? '') !== $oficinaSeleccionada) {
        continue;
    }
    $fa = $t['fecha_asignacion'] ?? '';
    if ($fa === '' || $fa < $desde || $fa > $hasta) {
        continue;
    }
    $filtradas[] = $stateService->computeState($t);
}

$userById = [];
foreach ($userRepo->findAll() as $u) {
    $userById[$u['id']] = $u;
}

$porDia = [];
$cur = \DateTimeImmutable::createFromFormat('Y-m-d', $desde, $tz);
$end = \DateTimeImmutable::createFromFormat('Y-m-d', $hasta, $tz);
if ($cur && $end) {
    while ($cur <= $end) {
        $porDia[$cur->format('Y-m-d')] = 0;
        $cur = $cur->modify('+1 day');
    }
}
foreach ($filtradas as $t) {
    $fa = $t['fecha_asignacion'] ?? '';
    if ($fa !== '' && isset($porDia[$fa])) {
        $porDia[$fa]++;
    }
}
$labelsDia = array_keys($porDia);
$dataDia = array_values($porDia);

$export = isset($_GET['export']) && $_GET['export'] === '1';
$format = strtolower(trim($_GET['format'] ?? 'csv'));
if (!in_array($format, ['csv', 'pdf'], true)) {
    $format = 'csv';
}

if ($export) {
    $safeSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nombreOficina);
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reporte_' . $safeSlug . '_' . $desde . '_' . $hasta . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Folio', 'Título', 'Estado', 'Modalidad', 'Responsable', 'RPE', 'Fecha asignación', 'Fecha límite', 'Cancelada', 'Motivo cancelación', 'Dictamen', 'Evaluación %']);
        foreach ($filtradas as $t) {
            $rid = $t['responsable_id'] ?? '';
            $ru = $userById[$rid] ?? null;
            fputcsv($out, [
                $t['folio'] ?? '',
                $t['titulo'] ?? '',
                $t['estado'] ?? '',
                $t['modalidad_asignacion'] ?? '',
                $ru['nombre'] ?? $rid,
                $ru['rpe'] ?? '',
                $t['fecha_asignacion'] ?? '',
                $t['fecha_limite'] ?? '',
                !empty($t['cancelada']) ? 'Sí' : 'No',
                (string) ($t['motivo_cancelacion'] ?? ''),
                (string) ($t['dictamen'] ?? ''),
                $t['evaluacion'] !== null && $t['evaluacion'] !== '' ? (string) $t['evaluacion'] : '',
            ]);
        }
        fclose($out);
        exit;
    }
    if ($format === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reporte_' . $safeSlug . '_' . $desde . '_' . $hasta . '.html"');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Reporte</title>';
        echo '<style>body{font-family:Segoe UI,sans-serif;margin:24px;color:#111}h1{font-size:18px}table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #ccc;padding:6px;text-align:left}th{background:#f0f4f8}@media print{.no-print{display:none}}</style></head><body>';
        echo '<h1>Reporte de actividades — ' . htmlspecialchars($nombreOficina) . '</h1>';
        echo '<p>Periodo: ' . htmlspecialchars($periodo) . ' · Desde ' . htmlspecialchars($desde) . ' hasta ' . htmlspecialchars($hasta) . '</p>';
        echo '<p class="no-print"><em>Abra este archivo en el navegador y use Imprimir → Guardar como PDF.</em></p>';
        echo '<table><thead><tr><th>Folio</th><th>Título</th><th>Estado</th><th>Responsable</th><th>Asignación</th><th>Límite</th><th>Cancelada</th></tr></thead><tbody>';
        foreach ($filtradas as $t) {
            $rid = $t['responsable_id'] ?? '';
            $ru = $userById[$rid] ?? null;
            echo '<tr><td>' . htmlspecialchars($t['folio'] ?? '') . '</td><td>' . htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 80)) . '</td><td>' . htmlspecialchars($t['estado'] ?? '') . '</td><td>' . htmlspecialchars($ru['nombre'] ?? '') . '</td><td>' . htmlspecialchars($t['fecha_asignacion'] ?? '') . '</td><td>' . htmlspecialchars($t['fecha_limite'] ?? '') . '</td><td>' . (!empty($t['cancelada']) ? 'Sí' : 'No') . '</td></tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }
}

$pageTitle = 'Reportes de actividades — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Reportes']];
$currentUser = $user;
$oficinaIdReporte = $oficinaSeleccionada;
$opcionesOficinaReporte = [];
foreach ($officeRepo->findAll() as $o) {
    $oid = $o['id'] ?? '';
    if (in_array($oid, $oficinasReportePermitidas, true)) {
        $opcionesOficinaReporte[] = $o;
    }
}
ob_start();
include dirname(__DIR__) . '/views/reportes.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
