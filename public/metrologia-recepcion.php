<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$metPerm = $container['MetrologiaPermissionService'];
if (!$metPerm->canAccessRoute($user, basename($_SERVER['PHP_SELF'] ?? 'metrologia-recepcion.php'))) {
    http_response_code(403);
    $pageTitle = 'Acceso denegado — Metrología';
    $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Recepción']];
    $currentUser = $user;
    ob_start();
    ?>
    <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para acceder al módulo de Metrología.</div>
    <?php
    $content = ob_get_clean();
    include $base . '/views/layout.php';
    exit;
}

$canManage = $metPerm->canManage($user);

$recepRepo = $container['repositories']['met_recepcion'];
$bitRepo = $container['repositories']['met_bitacora_equipos'];
$folioSvc = $container['MetrologiaRecepcionFolioService'];
$metHistory = $container['MetrologiaHistoryService'];
$suggestNextEquipoFolio = $folioSvc->nextEquipoFolio((int)date('Y'));
$zonasEntrega = [
    'Zocalo',
    'Benito Juarez',
    'Polanco',
    'Tacuba',
    'Aeropuerto',
    'Nezahuacoyotl',
    'Chapingo',
];

$error = '';
$success = '';
$warning = '';

// Crear recepción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManage) {
        http_response_code(403);
        $pageTitle = 'No autorizado — Metrología';
        $breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Recepción']];
        $currentUser = $user;
        ob_start(); ?>
        <div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tiene permisos para registrar recepciones.</div>
        <?php $content = ob_get_clean(); include $base . '/views/layout.php'; exit;
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'guardar_recepcion') {
        $motivo = trim((string)($_POST['motivo_recepcion'] ?? ''));

        $entregaRpe = strtoupper(trim((string)($_POST['entrega_rpe'] ?? '')));
        $entregaNombre = trim((string)($_POST['entrega_nombre'] ?? ''));
        $entregaZona = trim((string)($_POST['entrega_zona'] ?? ''));
        $entregaArea = trim((string)($_POST['entrega_area'] ?? ''));

        $equiposMarca = (array)($_POST['equipo_marca'] ?? []);
        $equiposModelo = (array)($_POST['equipo_modelo'] ?? []);
        $equiposSerie = (array)($_POST['equipo_serie'] ?? []);
        $equiposDesc = (array)($_POST['equipo_descripcion'] ?? []);
        $equiposObs = (array)($_POST['equipo_observaciones'] ?? []);
        $equiposFolio = (array)($_POST['equipo_folio'] ?? []);

        $confirmDupSeries = !empty($_POST['confirmar_series_duplicadas']);

        // Validaciones base
        if ($motivo === '') {
            $error = 'El motivo es obligatorio.';
        } elseif ($entregaRpe === '' || $entregaNombre === '' || $entregaZona === '' || $entregaArea === '') {
            $error = 'Complete los datos de ENTREGA: RPE, nombre, zona y área.';
        } elseif (!preg_match('/^[A-Z0-9]{1,8}$/', $entregaRpe)) {
            $error = 'RPE de entrega inválido.';
        } elseif (!in_array($entregaZona, $zonasEntrega, true)) {
            $error = 'Zona de entrega inválida.';
        } else {
            // Construir equipos (y validar requeridos)
            $equipos = [];
            $nRows = max(count($equiposMarca), count($equiposModelo), count($equiposSerie), count($equiposDesc));
            for ($i = 0; $i < $nRows; $i++) {
                $marca = trim((string)($equiposMarca[$i] ?? ''));
                $modelo = trim((string)($equiposModelo[$i] ?? ''));
                $serie = trim((string)($equiposSerie[$i] ?? ''));
                $desc = trim((string)($equiposDesc[$i] ?? ''));
                $obs = trim((string)($equiposObs[$i] ?? ''));
                $folioManual = trim((string)($equiposFolio[$i] ?? ''));
                if ($marca === '' && $modelo === '' && $serie === '' && $desc === '' && $obs === '') {
                    continue; // fila vacía
                }
                $equipos[] = [
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'serie' => $serie,
                    'descripcion' => $desc,
                    'observaciones' => $obs,
                    'folio' => $folioManual,
                ];
            }

            if (empty($equipos)) {
                $error = 'Debe capturar al menos un equipo.';
            } else {
                foreach ($equipos as $idx => $e) {
                    if ($e['marca'] === '' || $e['modelo'] === '' || $e['serie'] === '' || $e['descripcion'] === '') {
                        $error = 'En equipos: marca, modelo, serie y descripción son obligatorios (fila ' . ($idx + 1) . ').';
                        break;
                    }
                }
            }

            // Warning series duplicadas (bitácora)
            $dupSeries = [];
            if ($error === '') {
                foreach ($equipos as $e) {
                    $serie = (string)$e['serie'];
                    if ($serie === '') continue;
                    $hits = $bitRepo->findBySerie($serie);
                    if (!empty($hits)) {
                        $dupSeries[$serie] = ($dupSeries[$serie] ?? 0) + count($hits);
                    }
                }
                if (!empty($dupSeries) && !$confirmDupSeries) {
                    $warning = 'Se detectaron series ya existentes en bitácora: ' . implode(', ', array_map(fn($s) => $s . ' (' . $dupSeries[$s] . ')', array_keys($dupSeries))) . '. Marque la confirmación para continuar.';
                }
            }

            if ($error === '' && $warning === '') {
                $year = (int)date('Y');
                $fechaRecepcion = date('Y-m-d');
                $nowIso = \App\Repositories\MetrologiaRepositoryUtils::nowIso();

                $folioRecepcion = $folioSvc->nextRecepcionFolio($year);
                if ($recepRepo->existsFolioRecepcion($folioRecepcion)) {
                    // extremadamente raro (concurrente), pero protegemos
                    $error = 'No se pudo asignar folio de recepción (duplicado). Intente de nuevo.';
                } else {
                    // Folios manuales (si vienen); si faltan o vienen vacíos, completar con consecutivos sugeridos.
                    $foliosEquipos = [];
                    $need = 0;
                    foreach ($equipos as $e) {
                        $f = trim((string)($e['folio'] ?? ''));
                        if ($f !== '') $foliosEquipos[] = $f;
                        else { $foliosEquipos[] = null; $need++; }
                    }
                    if ($need > 0) {
                        $gen = $folioSvc->nextEquipoFolios($year, $need);
                        $gi = 0;
                        foreach ($foliosEquipos as $i => $f) {
                            if ($f === null) {
                                $foliosEquipos[$i] = $gen[$gi] ?? ($year . '-0000');
                                $gi++;
                            }
                        }
                    }
                    // Valida folios por equipo (concurrente)
                    foreach ($foliosEquipos as $i => $f) {
                        $serie = strtoupper(trim((string)($equipos[$i]['serie'] ?? '')));
                        $excludeId = null;
                        if ($serie !== '') {
                            $hits = $bitRepo->findBySerie($serie);
                            if (!empty($hits)) {
                                $excludeId = (string)($hits[0]['id'] ?? null);
                            }
                        }
                        if ($bitRepo->existsFolio($f, $excludeId)) {
                            $error = 'No se pudo asignar folio de equipo (duplicado). Intente de nuevo.';
                            break;
                        }
                    }
                }

                if ($error === '') {
                    $recibeNombre = trim((string)($user['nombre'] ?? ''));
                    $recibeRpe = strtoupper(trim((string)($user['rpe'] ?? '')));

                    $recepcion = [
                        'folio_recepcion' => $folioRecepcion,
                        'fecha_recepcion' => $fechaRecepcion,
                        'motivo_recepcion' => $motivo,
                        'recibe' => [
                            'usuario_id' => (string)($user['id'] ?? ''),
                            'rpe' => $recibeRpe,
                            'nombre' => $recibeNombre,
                            'area' => 'Metrología',
                            'zona' => 'DM-000',
                            'firma_data_url' => null,
                            'firma_dispositivo' => null,
                            'firma_pendiente' => true,
                        ],
                        'entrega' => [
                            'rpe' => $entregaRpe,
                            'nombre' => $entregaNombre,
                            'area' => $entregaArea,
                            'zona' => $entregaZona,
                            'firma_data_url' => null,
                            'firma_dispositivo' => null,
                            'firma_pendiente' => true,
                        ],
                        'equipos' => [],
                        'estado' => 'recibida',
                        'created_by' => (string)($user['id'] ?? ''),
                        'created_at' => $nowIso,
                        'updated_at' => $nowIso,
                    ];

                    foreach ($equipos as $i => $e) {
                        $recepcion['equipos'][] = [
                            'id' => \App\Repositories\MetrologiaRepositoryUtils::newId('mreq'),
                            'numero' => $i + 1,
                            'folio' => $foliosEquipos[$i] ?? ($year . '-0000'),
                            'marca' => $e['marca'],
                            'modelo' => $e['modelo'],
                            'serie' => $e['serie'],
                            'descripcion' => $e['descripcion'],
                            'observaciones' => $e['observaciones'],
                            'estado' => 'recibido',
                        ];
                    }

                    $saved = $recepRepo->save($recepcion);

                    // Bitácora plana por equipo
                    foreach (($saved['equipos'] ?? []) as $eq) {
                        $serieUp = strtoupper(trim((string)($eq['serie'] ?? '')));
                        $existing = null;
                        if ($serieUp !== '') {
                            $hits = $bitRepo->findBySerie($serieUp);
                            if (!empty($hits)) $existing = $hits[0];
                        }
                        $payload = [
                            'recepcion_id' => $saved['id'] ?? '',
                            'folio_recepcion' => $saved['folio_recepcion'] ?? '',
                            'folio' => $eq['folio'] ?? '',
                            'descripcion' => $eq['descripcion'] ?? '',
                            'no_serie' => $eq['serie'] ?? '',
                            'marca' => $eq['marca'] ?? '',
                            'modelo' => $eq['modelo'] ?? '',
                            'zona' => $saved['entrega']['zona'] ?? '',
                            'area' => $saved['entrega']['area'] ?? '',
                            'recibido' => $saved['fecha_recepcion'] ?? $fechaRecepcion,
                            'estado' => $eq['estado'] ?? 'no_programado',
                            'observaciones' => $eq['observaciones'] ?? '',
                            'recibe' => $saved['recibe']['nombre'] ?? '',
                            'entrega' => $saved['entrega']['nombre'] ?? '',
                        ];
                        if (is_array($existing) && !empty($existing['id'])) {
                            $payload['id'] = (string)$existing['id'];
                            if (!empty($existing['created_at'])) $payload['created_at'] = $existing['created_at'];
                        }
                        $bitRepo->save($payload);
                    }

                    // Catálogo maestro: upsert por serie/folio (misma lógica que public/api/metrologia-bitacora.php)
                    $equipRepo = $container['repositories']['met_equipo_catalogo'];
                    foreach (($saved['equipos'] ?? []) as $eq) {
                        $allEq = $equipRepo->findAll();
                        $target = null;
                        $serieUp = strtoupper(trim((string)($eq['serie'] ?? '')));
                        $folioEq = trim((string)($eq['folio'] ?? ''));
                        foreach ($allEq as $e) {
                            $eSerie = strtoupper(trim((string)($e['no_serie'] ?? '')));
                            if ($serieUp !== '' && $eSerie === $serieUp) {
                                $target = $e;
                                break;
                            }
                            if ($serieUp === '' && $folioEq !== '' && (string)($e['folio'] ?? '') === $folioEq) {
                                $target = $e;
                                break;
                            }
                        }
                        $entity = $target ?: [];
                        $entity['id'] = $entity['id'] ?? '';
                        $entity['folio'] = $folioEq;
                        $entity['no_serie'] = $serieUp;
                        $entity['marca'] = (string)($eq['marca'] ?? '');
                        $entity['modelo'] = (string)($eq['modelo'] ?? '');
                        $entity['descripcion'] = (string)($eq['descripcion'] ?? '');
                        $entity['zona'] = (string)($saved['entrega']['zona'] ?? '');
                        $entity['area'] = (string)($saved['entrega']['area'] ?? '');
                        $entity['oficina'] = (string)($entity['oficina'] ?? '');
                        $equipRepo->save($entity);
                    }

                    // Historial módulo Metrología
                    $metHistory->log(null, (string)($user['id'] ?? ''), 'recepcion_creada', 'Recepción registrada.', [
                        'recepcion_id' => $saved['id'] ?? '',
                        'folio_recepcion' => $saved['folio_recepcion'] ?? '',
                        'fecha_recepcion' => $saved['fecha_recepcion'] ?? $fechaRecepcion,
                        'entrega_rpe' => $entregaRpe,
                        'entrega_nombre' => $entregaNombre,
                        'entrega_zona' => $entregaZona,
                        'entrega_area' => $entregaArea,
                        'total_equipos' => count((array)($saved['equipos'] ?? [])),
                    ]);
                    foreach (($saved['equipos'] ?? []) as $eq) {
                        $metHistory->log(null, (string)($user['id'] ?? ''), 'equipo_recibido', 'Equipo recibido.', [
                            'recepcion_id' => $saved['id'] ?? '',
                            'folio_recepcion' => $saved['folio_recepcion'] ?? '',
                            'folio' => $eq['folio'] ?? '',
                            'serie' => $eq['serie'] ?? '',
                            'marca' => $eq['marca'] ?? '',
                            'modelo' => $eq['modelo'] ?? '',
                        ]);
                    }

                    header('Location: ' . $basePath . '/metrologia-recepcion.php?msg=ok&rid=' . rawurlencode((string)($saved['id'] ?? '')));
                    exit;
                }
            }
        }
    }
}

if (($_GET['msg'] ?? '') === 'ok') {
    $success = 'Recepción guardada correctamente.';
}
$rid = (string)($_GET['rid'] ?? '');
$detalleRecepcion = $rid !== '' ? $recepRepo->find($rid) : null;
$historialRecepcionesEquipos = [];
if ($detalleRecepcion) {
    $metHistory = $container['MetrologiaHistoryService'] ?? null;
    if ($metHistory && method_exists($metHistory, 'findGlobal')) {
        $series = [];
        $folios = [];
        foreach ((array)($detalleRecepcion['equipos'] ?? []) as $eq) {
            $s = strtoupper(trim((string)($eq['serie'] ?? '')));
            $f = trim((string)($eq['folio'] ?? ''));
            if ($s !== '') $series[$s] = true;
            if ($f !== '') $folios[$f] = true;
        }

        $events = $metHistory->findGlobal(['tipo_evento' => 'equipo_recibido']);
        foreach ($events as $ev) {
            $meta = (array)($ev['metadata'] ?? []);
            $s = strtoupper(trim((string)($meta['serie'] ?? '')));
            $f = trim((string)($meta['folio'] ?? ''));
            $key = '';
            if ($s !== '' && isset($series[$s])) $key = 'SERIE:' . $s;
            elseif ($f !== '' && isset($folios[$f])) $key = 'FOLIO:' . $f;
            else continue;

            $historialRecepcionesEquipos[$key] = $historialRecepcionesEquipos[$key] ?? [];
            $historialRecepcionesEquipos[$key][] = $ev;
        }
        foreach ($historialRecepcionesEquipos as $k => $list) {
            usort($list, fn($a, $b) => strcmp((string)($b['fecha_hora'] ?? ''), (string)($a['fecha_hora'] ?? '')));
            $historialRecepcionesEquipos[$k] = $list;
        }
        ksort($historialRecepcionesEquipos);
    }
}

$pageTitle = 'Metrología — Recepción';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Metrología'], ['label' => 'Recepción']];
$currentUser = $user;
ob_start();
include $base . '/views/metrologia/recepcion.php';
$content = ob_get_clean();
include $base . '/views/layout.php';

