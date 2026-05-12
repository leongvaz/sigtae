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
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Bitácora']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder al módulo de Metrología.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$bitRepo = $container['repositories']['met_bitacora_equipos'];

// Seed automático del "Programa 2026" (solo si la bitácora está vacía)
// Fuente: Programa 2026.txt (TSV) en la raíz del proyecto.
if (empty($bitRepo->findAll())) {
    $programaPath = $base . '/Programa 2026.txt';
    if (is_file($programaPath)) {
        $raw = (string)@file_get_contents($programaPath);
        if ($raw !== '') {
            $lines = preg_split("/\\r\\n|\\n|\\r/", $raw) ?: [];
            $header = null;
            $idx = [];

            $normalize = function(string $s): string {
                $s = strtoupper(trim($s));
                $map = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
                return strtr($s, $map);
            };
            $startsWith = function(string $s, string $prefix): bool {
                return $prefix === '' ? true : substr($s, 0, strlen($prefix)) === $prefix;
            };
            $contains = function(string $s, string $needle): bool {
                return $needle === '' ? true : (strpos($s, $needle) !== false);
            };
            $normalizeFolio = function(string $folio): string {
                $f = trim($folio);
                if ($f === '') return '';
                // 26-0565 => 2026-0565
                if (preg_match('/^(\\d{2})\\-(\\d{4})$/', $f, $m)) {
                    return '20' . $m[1] . '-' . $m[2];
                }
                // 2026--5 => 2026-0005
                if (preg_match('/^(\\d{4})\\-\\-(\\d{1,4})$/', $f, $m)) {
                    return $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
                }
                // 2026-5 => 2026-0005
                if (preg_match('/^(\\d{4})\\-(\\d{1,4})$/', $f, $m)) {
                    return $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
                }
                return $f;
            };
            $parseDateDMY = function(string $s): string {
                $s = trim($s);
                if ($s === '') return '';
                // 20/04/2026
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
                    return $m[3] . '-' . $m[2] . '-' . $m[1];
                }
                return '';
            };

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;

                // Detecta header TSV
                if ($header === null && $startsWith($line, 'DESCRIPCION')) {
                    $header = explode("\t", $line);
                    foreach ($header as $i => $h) {
                        $idx[$normalize($h)] = $i;
                    }
                    continue;
                }
                if ($header === null) continue;

                $cols = explode("\t", $line);
                // Salta líneas muy cortas
                if (count($cols) < 10) continue;

                $get = function(string $key) use ($idx, $cols, $normalize): string {
                    $k = $normalize($key);
                    if (!isset($idx[$k])) return '';
                    $i = (int)$idx[$k];
                    return isset($cols[$i]) ? trim((string)$cols[$i]) : '';
                };

                $folio = $normalizeFolio($get('FOLIO'));
                $serie = $get('No. SERIE');
                $desc = $get('DESCRIPCION');
                $marca = $get('MARCA');
                $modelo = $get('MODELO');
                $zona = $get('ZONA');
                $area = $get('AREA');
                $oficina = $get('OFICINA');
                $obs = $get('OBSERVACIONES');
                $recibido = $parseDateDMY($get('RECIBIDO'));
                $tecnico = $get('TECNICO');
                $fechaCal = $parseDateDMY($get('FECHA DE CALIBRACION/BAJA'));
                $evalConf = $get('EVALUACION DE CONFORMIDAD');
                $fechaImpresion = $parseDateDMY($get('FECHA DE IMPRESION'));
                $fechaEntregaEsc = $parseDateDMY($get('FECHA DE ENTREGA DE INFORME ESCANEADO'));
                $entregado = $parseDateDMY($get('ENTREGADO'));
                $aQuienEntrega = $get('NOMBRE A QUIEN SE ENTREGA');
                $fechaProgramada = $get('FECHA PROGRAMADA');
                $tablero = $get('TABLERO EVOLUTIVO');

                $bitRepo->save([
                    'recepcion_id' => null,
                    'folio_recepcion' => null,
                    'folio' => $folio,
                    'descripcion' => $desc,
                    'no_serie' => $serie,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'zona' => $zona,
                    'area' => $area,
                    'oficina' => $oficina,
                    'recibido' => $recibido,
                    'estado' => 'programado',
                    'observaciones' => $obs,
                    'recibe' => '',
                    'entrega' => '',
                    'source' => 'Programa 2026.txt',
                    // Extras para trazabilidad (sin forzar UI todavía)
                    'tecnico' => $tecnico,
                    'fecha_calibracion_baja' => $fechaCal,
                    'evaluacion_conformidad' => $evalConf,
                    'fecha_impresion' => $fechaImpresion,
                    'fecha_entrega_informe_escaneado' => $fechaEntregaEsc,
                    'entregado' => $entregado,
                    'nombre_a_quien_se_entrega' => $aQuienEntrega,
                    'fecha_programada' => $fechaProgramada,
                    'tablero_evolutivo' => $tablero,
                ]);
            }
        }
    }
}

// Filtros
$anio = (int)($_GET['anio'] ?? date('Y'));
$fZona = trim((string)($_GET['zona'] ?? ''));
$fArea = trim((string)($_GET['area'] ?? ''));
$fQuery = trim((string)($_GET['q'] ?? ''));
$fEstado = trim((string)($_GET['estado'] ?? ''));

$bitacora = $bitRepo->findByFilters([
    'anio' => $anio,
    'zona' => $fZona !== '' ? $fZona : null,
    'area' => $fArea !== '' ? $fArea : null,
    'q' => $fQuery !== '' ? $fQuery : null,
    'estado' => $fEstado !== '' ? $fEstado : null,
]);

$zonasEntrega = [];

$pageTitle = 'Metrología — Bitácora';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Bitácora']];
$currentUser = $user;
ob_start();
include $base . '/views/metrologia/bitacora.php';
$content = ob_get_clean();
include $base . '/views/layout.php';

