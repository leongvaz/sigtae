<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$canManage = !empty($user['puede_asignar']) || !empty($user['es_super_admin']);
$userId = (string)($user['id'] ?? '');

$programaRepo = $container['repositories']['programa_trabajo'];
$actividadRepo = $container['repositories']['programa_actividad'];
$avanceRepo = $container['repositories']['programa_avance'];
$evidRepo = $container['repositories']['programa_evidencia']; // evidencias deshabilitadas en UI
$userRepo = $container['repositories']['user'];
$calService = $container['ProgramaCalendarioService'];

$usuarioPuedeEditarPrograma = static function (?array $programa) use ($canManage, $userId): bool {
    if (!$programa) {
        return false;
    }
    if ($canManage) {
        return true;
    }
    return $userId !== '' && (string)($programa['created_by'] ?? '') === $userId;
};

$eliminarAvancesActividad = static function (string $actividadId) use ($avanceRepo): void {
    foreach ($avanceRepo->findByActividad($actividadId) as $av) {
        $rid = (string)($av['id'] ?? '');
        if ($rid !== '') {
            $avanceRepo->delete($rid);
        }
    }
};

$eliminarEvidenciasActividad = static function (string $actividadId) use ($evidRepo): void {
    foreach ($evidRepo->findAll() as $ev) {
        if (($ev['actividad_id'] ?? '') !== $actividadId) {
            continue;
        }
        $ruta = (string)($ev['ruta'] ?? '');
        if ($ruta !== '' && is_file($ruta)) {
            @unlink($ruta);
        }
        $eid = (string)($ev['id'] ?? '');
        if ($eid !== '') {
            $evidRepo->delete($eid);
        }
    }
};

$recalcularRangoPrograma = static function (string $programaId) use ($programaRepo, $actividadRepo): void {
    $programa = $programaRepo->find($programaId);
    if (!$programa) {
        return;
    }
    $acts = $actividadRepo->findByPrograma($programaId);
    if (count($acts) === 0) {
        $programa['fecha_inicio'] = '';
        $programa['fecha_fin'] = '';
        $programaRepo->save($programa);
        return;
    }
    $minStart = null;
    $maxEnd = null;
    foreach ($acts as $a) {
        $ini = (string)($a['fecha_inicio'] ?? '');
        $fin = (string)($a['fecha_fin'] ?? '');
        if ($ini === '' || $fin === '') {
            continue;
        }
        if ($minStart === null || $ini < $minStart) {
            $minStart = $ini;
        }
        if ($maxEnd === null || $fin > $maxEnd) {
            $maxEnd = $fin;
        }
    }
    if ($minStart !== null && $maxEnd !== null) {
        $programa['fecha_inicio'] = $minStart;
        $programa['fecha_fin'] = $maxEnd;
        $programaRepo->save($programa);
    }
};

$renumerarActividadesPrograma = static function (string $programaId) use ($actividadRepo): void {
    $acts = $actividadRepo->findByPrograma($programaId);
    usort($acts, static function ($a, $b) {
        $na = (int)($a['numero'] ?? 0);
        $nb = (int)($b['numero'] ?? 0);
        if ($na !== $nb) {
            return $na <=> $nb;
        }
        return strcmp((string)($a['fecha_inicio'] ?? ''), (string)($b['fecha_inicio'] ?? ''));
    });
    $n = 1;
    foreach ($acts as $row) {
        $row['numero'] = $n++;
        $actividadRepo->save($row);
    }
};

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $normalizeFinByFrecuencia = function (string $inicio, string $fin, string $frecuencia): string {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $inicio);
            if (!$dt) return $fin;
            $freq = strtolower(trim($frecuencia));
            if ($freq === 'semanal') return $dt->modify('+6 day')->format('Y-m-d');
            if ($freq === 'catorcenal') return $dt->modify('+13 day')->format('Y-m-d');
            if ($freq === 'mensual') return $dt->modify('+29 day')->format('Y-m-d'); // acotado a 30 días
            return $fin;
        };

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'crear_programa' && !$canManage) {
        $error = 'No tiene permisos para crear programas de trabajo.';
    } elseif ($action === 'crear_programa') {
            $nombre = trim((string)($_POST['nombre_programa'] ?? ''));
            $frecuencia = strtolower(trim((string)($_POST['frecuencia'] ?? 'semanal')));

            $actividadesNombre = (array)($_POST['actividad_nombre'] ?? []);
            $actividadesCantidad = (array)($_POST['actividad_cantidad'] ?? []);
            $actividadesMedicion = (array)($_POST['actividad_medicion'] ?? []);
            $actividadesInicio = (array)($_POST['actividad_inicio'] ?? []);
            $actividadesFin = (array)($_POST['actividad_fin'] ?? []);
            $actividadesTipo = (array)($_POST['actividad_tipo'] ?? []);
            $actividadesRespId = (array)($_POST['actividad_responsable_id'] ?? []);

            if ($nombre === '') {
                $error = 'Nombre del programa es obligatorio.';
            } elseif (!in_array($frecuencia, ['diario', 'semanal', 'catorcenal', 'mensual'], true)) {
                $error = 'Frecuencia inválida.';
            } else {
                $n = max(count($actividadesNombre), count($actividadesCantidad), count($actividadesTipo), count($actividadesRespId), count($actividadesInicio), count($actividadesFin));
                $actsToSave = [];
                $minStart = null;
                $maxEnd = null;

                for ($i = 0; $i < $n; $i++) {
                    $aNom = trim((string)($actividadesNombre[$i] ?? ''));
                    $aMed = strtolower(trim((string)($actividadesMedicion[$i] ?? 'cantidad')));
                    $aCantRaw = (float)($actividadesCantidad[$i] ?? 0);
                    $aUni = '';
                    $aTipo = trim((string)($actividadesTipo[$i] ?? ''));
                    $aIni = trim((string)($actividadesInicio[$i] ?? ''));
                    $aFin = trim((string)($actividadesFin[$i] ?? ''));
                    $aRespId = trim((string)($actividadesRespId[$i] ?? ''));

                    if ($aNom === '' && $aTipo === '' && $aIni === '' && $aFin === '') continue;

                    if (!in_array($aMed, ['cantidad','entregable'], true)) $aMed = 'cantidad';

                    // Validación básica por medición
                    if ($aNom === '' || $aTipo === '' || $aIni === '' || $aFin === '') continue;
                    if ($aMed === 'cantidad' && $aCantRaw <= 0) continue;

                    // Normaliza fin por frecuencia del programa
                    $aFinNorm = $normalizeFinByFrecuencia($aIni, $aFin, $frecuencia);
                    $di = \DateTimeImmutable::createFromFormat('Y-m-d', $aIni);
                    $df = \DateTimeImmutable::createFromFormat('Y-m-d', $aFinNorm);
                    if (!$di || !$df || $di > $df) continue;

                    if ($minStart === null || $aIni < $minStart) $minStart = $aIni;
                    if ($maxEnd === null || $aFinNorm > $maxEnd) $maxEnd = $aFinNorm;

                    // Responsable: por defecto el creador, solo super-admin puede elegir.
                    if (empty($user['es_super_admin'])) {
                        $aRespId = (string)($user['id'] ?? '');
                    }
                    if ($aRespId === '') {
                        $aRespId = (string)($user['id'] ?? '');
                    }
                    $resp = $userRepo->find($aRespId);
                    $respNombre = (string)($resp['nombre'] ?? '');

                    // Normalizaciones de unidad/cantidad por tipo
                    $aCant = $aCantRaw;
                    if ($aMed === 'entregable') {
                        $aCant = 1;
                    }

                    $actsToSave[] = [
                        'actividad' => $aNom,
                        'cantidad' => $aCant,
                        'unidad' => $aUni,
                        'tipo_medicion' => $aMed,
                        'fecha_inicio' => $aIni,
                        'fecha_fin' => $aFinNorm,
                        'tipo' => $aTipo,
                        'responsable_id' => $aRespId,
                        'responsable' => $respNombre !== '' ? $respNombre : $aRespId,
                    ];
                }

                if (count($actsToSave) === 0 || !$minStart || !$maxEnd) {
                    $error = 'Agregue al menos una actividad válida (con inicio/fin).';
                } else {
                    $programa = $programaRepo->save([
                        'nombre' => $nombre,
                        'frecuencia' => $frecuencia,
                        'fecha_inicio' => $minStart,
                        'fecha_fin' => $maxEnd,
                        'estado' => 'activo',
                        'created_by' => (string)($user['id'] ?? ''),
                    ]);

                    usort($actsToSave, function ($a, $b) {
                        return strcmp((string)($a['fecha_inicio'] ?? ''), (string)($b['fecha_inicio'] ?? ''));
                    });

                    $numero = 1;
                    foreach ($actsToSave as $row) {
                        $row['programa_id'] = $programa['id'];
                        $row['numero'] = $numero++;
                        $act = $actividadRepo->save($row);

                        // Programado automático solo para medición por cantidad: distribuir en días hábiles del rango de la actividad.
                        if (($row['tipo_medicion'] ?? '') === 'cantidad') {
                            $ini = (string)($row['fecha_inicio'] ?? '');
                            $fin = (string)($row['fecha_fin'] ?? '');
                            $dates = ($ini !== '' && $fin !== '') ? $calService->generateDailyColumns($ini, $fin) : [];
                            $total = (int)round((float)($row['cantidad'] ?? 0));
                            $nDates = count($dates);
                            if ($nDates > 0 && $total > 0) {
                                $base = (int)floor($total / $nDates);
                                $rem = $total - ($base * $nDates);
                                foreach ($dates as $idx => $d) {
                                    $val = $base + (($idx < $rem) ? 1 : 0);
                                    $avanceRepo->save([
                                        'programa_id' => (string)($programa['id'] ?? ''),
                                        'actividad_id' => (string)($act['id'] ?? ''),
                                        'periodo_fecha' => (string)$d,
                                        'tipo' => 'P',
                                        'valor' => $val,
                                    ]);
                                }
                            }
                        }
                    }

                    header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode((string)$programa['id']) . '&msg=created');
                    exit;
                }
            }
        } elseif ($action === 'guardar_avances') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $programa = $programaRepo->find($programaId);
            if (!$programa) {
                $error = 'Programa no encontrado.';
            } else {
                $programado = (array)($_POST['programado'] ?? []);
                $ejecutado = (array)($_POST['ejecutado'] ?? []);
                $ejecutadoChk = (array)($_POST['ejecutado_chk'] ?? []);

                if ($canManage) {
                    foreach ($programado as $actividadId => $vals) {
                        foreach ((array)$vals as $fecha => $valorRaw) {
                            $valor = (float)$valorRaw;
                            if ($valor < 0) $valor = 0;
                            $avanceRepo->save([
                                'programa_id' => $programaId,
                                'actividad_id' => (string)$actividadId,
                                'periodo_fecha' => (string)$fecha,
                                'tipo' => 'P',
                                'valor' => $valor,
                            ]);
                        }
                    }
                }
                foreach ($ejecutado as $actividadId => $vals) {
                    foreach ((array)$vals as $fecha => $valorRaw) {
                        $valor = (float)$valorRaw;
                        if ($valor < 0) $valor = 0;
                        $avanceRepo->save([
                            'programa_id' => $programaId,
                            'actividad_id' => (string)$actividadId,
                            'periodo_fecha' => (string)$fecha,
                            'tipo' => 'E',
                            'valor' => $valor,
                        ]);
                    }
                }
                foreach ($ejecutadoChk as $actividadId => $vals) {
                    foreach ((array)$vals as $fecha => $valorRaw) {
                        $valor = (float)$valorRaw;
                        if ($valor < 0) $valor = 0;
                        if ($valor > 1) $valor = 1;
                        $avanceRepo->save([
                            'programa_id' => $programaId,
                            'actividad_id' => (string)$actividadId,
                            'periodo_fecha' => (string)$fecha,
                            'tipo' => 'E',
                            'valor' => $valor,
                        ]);
                    }
                }

                header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=saved');
                exit;
            }
        } elseif ($action === 'agregar_actividad') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $programa = $programaRepo->find($programaId);
            if (!$programa) {
                $error = 'Programa no encontrado.';
            } elseif (!$usuarioPuedeEditarPrograma($programa)) {
                $error = 'No tiene permisos para modificar este programa.';
            } else {
                $aNom = trim((string)($_POST['actividad'] ?? ''));
                $aMed = strtolower(trim((string)($_POST['tipo_medicion'] ?? 'cantidad')));
                $aCantRaw = (float)($_POST['cantidad'] ?? 0);
                $aUni = '';
                $aIni = trim((string)($_POST['actividad_inicio'] ?? ''));
                $aFin = trim((string)($_POST['actividad_fin'] ?? ''));
                $aTipo = trim((string)($_POST['tipo'] ?? ''));
                $aRespId = trim((string)($_POST['responsable_id'] ?? ''));

                if ($aNom === '' || $aTipo === '' || $aIni === '' || $aFin === '') {
                    $error = 'Complete actividad, inicio, fin y tipo.';
                } elseif (!in_array($aMed, ['cantidad','entregable'], true)) {
                    $error = 'Medición inválida.';
                } elseif ($aMed === 'cantidad' && $aCantRaw <= 0) {
                    $error = 'Capture una cantidad válida.';
                } else {
                    $aFin = $normalizeFinByFrecuencia($aIni, $aFin, (string)($programa['frecuencia'] ?? 'diario'));
                    $di = \DateTimeImmutable::createFromFormat('Y-m-d', $aIni);
                    $df = \DateTimeImmutable::createFromFormat('Y-m-d', $aFin);
                    if (!$di || !$df || $di > $df) {
                        $error = 'Rango de fechas inválido.';
                    } else {
                        if (empty($user['es_super_admin'])) {
                            $aRespId = (string)($user['id'] ?? '');
                        }
                        if ($aRespId === '') $aRespId = (string)($user['id'] ?? '');
                        $resp = $userRepo->find($aRespId);
                        $respNombre = (string)($resp['nombre'] ?? '');

                        $aCant = $aCantRaw;
                        if ($aMed === 'entregable') { $aCant = 1; }

                        // siguiente número
                        $acts = $actividadRepo->findByPrograma($programaId);
                        $maxNum = 0;
                        foreach ($acts as $it) $maxNum = max($maxNum, (int)($it['numero'] ?? 0));
                        $act = $actividadRepo->save([
                            'programa_id' => $programaId,
                            'numero' => $maxNum + 1,
                            'actividad' => $aNom,
                            'cantidad' => $aCant,
                            'unidad' => $aUni,
                            'tipo_medicion' => $aMed,
                            'fecha_inicio' => $aIni,
                            'fecha_fin' => $aFin,
                            'tipo' => $aTipo,
                            'responsable_id' => $aRespId,
                            'responsable' => $respNombre !== '' ? $respNombre : $aRespId,
                        ]);

                        // Programado automático solo para medición por cantidad (distribución en días hábiles del rango de la actividad)
                        if ($aMed === 'cantidad') {
                            $dates = $calService->generateDailyColumns($aIni, $aFin);
                            $total = (int)round((float)$aCant);
                            $nDates = count($dates);
                            if ($nDates > 0 && $total > 0) {
                                $base = (int)floor($total / $nDates);
                                $rem = $total - ($base * $nDates);
                                foreach ($dates as $idx => $d) {
                                    $val = $base + (($idx < $rem) ? 1 : 0);
                                    $avanceRepo->save([
                                        'programa_id' => $programaId,
                                        'actividad_id' => (string)($act['id'] ?? ''),
                                        'periodo_fecha' => (string)$d,
                                        'tipo' => 'P',
                                        'valor' => $val,
                                    ]);
                                }
                            }
                        }

                        // Recalcula rango del programa (derivado de actividades)
                        $minStart = (string)($programa['fecha_inicio'] ?? '');
                        $maxEnd = (string)($programa['fecha_fin'] ?? '');
                        if ($minStart === '' || $aIni < $minStart) $minStart = $aIni;
                        if ($maxEnd === '' || $aFin > $maxEnd) $maxEnd = $aFin;
                        $programa['fecha_inicio'] = $minStart;
                        $programa['fecha_fin'] = $maxEnd;
                        $programaRepo->save($programa);

                        header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=act_ok');
                        exit;
                    }
                }
            }
        } elseif ($action === 'toggle_terminada') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $actividadId = trim((string)($_POST['actividad_id'] ?? ''));
            $valor = (string)($_POST['valor'] ?? '1');
            $programa = $programaRepo->find($programaId);
            $actividad = $actividadRepo->find($actividadId);
            if (!$programa || !$actividad) {
                $error = 'Programa o actividad no encontrada.';
            } elseif (!$usuarioPuedeEditarPrograma($programa)) {
                $error = 'No tiene permisos para modificar este programa.';
            } elseif (($actividad['programa_id'] ?? '') !== $programaId) {
                $error = 'Actividad no pertenece al programa.';
            } else {
                $actividad['terminada'] = ($valor === '1' || $valor === 'true');
                $actividadRepo->save($actividad);
                header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=term_ok');
                exit;
            }
        } elseif ($action === 'actualizar_programa') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $programa = $programaRepo->find($programaId);
            if (!$programa) {
                $error = 'Programa no encontrado.';
            } elseif (!$usuarioPuedeEditarPrograma($programa)) {
                $error = 'No tiene permisos para modificar este programa.';
            } else {
                $nombre = trim((string)($_POST['nombre_programa'] ?? ''));
                $frecuencia = strtolower(trim((string)($_POST['frecuencia'] ?? '')));
                $estado = strtolower(trim((string)($_POST['estado'] ?? '')));
                if ($nombre === '') {
                    $error = 'El nombre del programa es obligatorio.';
                } elseif (!in_array($frecuencia, ['diario', 'semanal', 'catorcenal', 'mensual'], true)) {
                    $error = 'Frecuencia inválida.';
                } elseif ($estado !== '' && !in_array($estado, ['activo', 'cerrado'], true)) {
                    $error = 'Estado inválido.';
                } else {
                    $programa['nombre'] = $nombre;
                    $programa['frecuencia'] = $frecuencia;
                    if ($estado !== '') {
                        $programa['estado'] = $estado;
                    }
                    $programaRepo->save($programa);
                    header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=prog_upd');
                    exit;
                }
            }
        } elseif ($action === 'eliminar_programa') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $programa = $programaRepo->find($programaId);
            if (!$programa) {
                $error = 'Programa no encontrado.';
            } elseif (!$usuarioPuedeEditarPrograma($programa)) {
                $error = 'No tiene permisos para eliminar este programa.';
            } else {
                foreach ($evidRepo->findByPrograma($programaId) as $ev) {
                    $ruta = (string)($ev['ruta'] ?? '');
                    if ($ruta !== '' && is_file($ruta)) {
                        @unlink($ruta);
                    }
                    $eid = (string)($ev['id'] ?? '');
                    if ($eid !== '') {
                        $evidRepo->delete($eid);
                    }
                }
                foreach ($avanceRepo->findByPrograma($programaId) as $av) {
                    $aid = (string)($av['id'] ?? '');
                    if ($aid !== '') {
                        $avanceRepo->delete($aid);
                    }
                }
                foreach ($actividadRepo->findByPrograma($programaId) as $act) {
                    $acid = (string)($act['id'] ?? '');
                    if ($acid !== '') {
                        $actividadRepo->delete($acid);
                    }
                }
                $programaRepo->delete($programaId);
                header('Location: ' . $basePath . '/programa-trabajo-gantt.php?msg=prog_del');
                exit;
            }
        } elseif ($action === 'eliminar_actividad') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $actividadId = trim((string)($_POST['actividad_id'] ?? ''));
            $programa = $programaRepo->find($programaId);
            $actividad = $actividadRepo->find($actividadId);
            if (!$programa || !$actividad) {
                $error = 'Programa o actividad no encontrada.';
            } elseif (!$usuarioPuedeEditarPrograma($programa)) {
                $error = 'No tiene permisos para modificar este programa.';
            } elseif (($actividad['programa_id'] ?? '') !== $programaId) {
                $error = 'Actividad no pertenece al programa.';
            } else {
                $eliminarAvancesActividad($actividadId);
                $eliminarEvidenciasActividad($actividadId);
                $actividadRepo->delete($actividadId);
                $recalcularRangoPrograma($programaId);
                $renumerarActividadesPrograma($programaId);
                header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=act_del');
                exit;
            }
        } elseif ($action === 'editar_actividad') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $actividadId = trim((string)($_POST['actividad_id'] ?? ''));
            $programa = $programaRepo->find($programaId);
            $actividad = $actividadRepo->find($actividadId);
            if (!$programa || !$actividad) {
                $error = 'Programa o actividad no encontrada.';
            } elseif (!$usuarioPuedeEditarPrograma($programa)) {
                $error = 'No tiene permisos para modificar este programa.';
            } elseif (($actividad['programa_id'] ?? '') !== $programaId) {
                $error = 'Actividad no pertenece al programa.';
            } else {
                $aNom = trim((string)($_POST['actividad'] ?? ''));
                $aMed = strtolower(trim((string)($_POST['tipo_medicion'] ?? 'cantidad')));
                $aCantRaw = (float)($_POST['cantidad'] ?? 0);
                $aUni = '';
                $aIni = trim((string)($_POST['actividad_inicio'] ?? ''));
                $aFin = trim((string)($_POST['actividad_fin'] ?? ''));
                $aTipo = trim((string)($_POST['tipo'] ?? ''));
                $aRespId = trim((string)($_POST['responsable_id'] ?? ''));

                if ($aNom === '' || $aTipo === '' || $aIni === '' || $aFin === '') {
                    $error = 'Complete actividad, inicio, fin y tipo.';
                } elseif (!in_array($aMed, ['cantidad', 'entregable'], true)) {
                    $error = 'Medición inválida.';
                } elseif ($aMed === 'cantidad' && $aCantRaw <= 0) {
                    $error = 'Capture una cantidad válida.';
                } else {
                    $aFin = $normalizeFinByFrecuencia($aIni, $aFin, (string)($programa['frecuencia'] ?? 'diario'));
                    $di = \DateTimeImmutable::createFromFormat('Y-m-d', $aIni);
                    $df = \DateTimeImmutable::createFromFormat('Y-m-d', $aFin);
                    if (!$di || !$df || $di > $df) {
                        $error = 'Rango de fechas inválido.';
                    } else {
                        if (empty($user['es_super_admin'])) {
                            $aRespId = (string)($user['id'] ?? '');
                        }
                        if ($aRespId === '') {
                            $aRespId = (string)($user['id'] ?? '');
                        }
                        $resp = $userRepo->find($aRespId);
                        $respNombre = (string)($resp['nombre'] ?? '');

                        $aCant = $aCantRaw;
                        if ($aMed === 'entregable') {
                            $aCant = 1;
                        }

                        $oldIni = (string)($actividad['fecha_inicio'] ?? '');
                        $oldFin = (string)($actividad['fecha_fin'] ?? '');
                        $oldMed = strtolower((string)($actividad['tipo_medicion'] ?? 'cantidad'));
                        $oldCant = (float)($actividad['cantidad'] ?? 0);
                        $structChanged = ($oldIni !== $aIni || $oldFin !== $aFin || $oldMed !== $aMed
                            || abs($oldCant - $aCant) > 0.00001);

                        if ($structChanged) {
                            $eliminarAvancesActividad($actividadId);
                            if ($aMed !== 'entregable') {
                                $eliminarEvidenciasActividad($actividadId);
                            } elseif ($oldMed !== 'entregable') {
                                $eliminarEvidenciasActividad($actividadId);
                            } else {
                                foreach ($evidRepo->findAll() as $ev) {
                                    if (($ev['actividad_id'] ?? '') !== $actividadId) {
                                        continue;
                                    }
                                    $fc = (string)($ev['fecha_columna'] ?? '');
                                    if ($fc === '' || $fc < $aIni || $fc > $aFin) {
                                        $ruta = (string)($ev['ruta'] ?? '');
                                        if ($ruta !== '' && is_file($ruta)) {
                                            @unlink($ruta);
                                        }
                                        $eid = (string)($ev['id'] ?? '');
                                        if ($eid !== '') {
                                            $evidRepo->delete($eid);
                                        }
                                    }
                                }
                            }
                        }

                        $actividad['actividad'] = $aNom;
                        $actividad['cantidad'] = $aCant;
                        $actividad['unidad'] = $aUni;
                        $actividad['tipo_medicion'] = $aMed;
                        $actividad['fecha_inicio'] = $aIni;
                        $actividad['fecha_fin'] = $aFin;
                        $actividad['tipo'] = $aTipo;
                        $actividad['responsable_id'] = $aRespId;
                        $actividad['responsable'] = $respNombre !== '' ? $respNombre : $aRespId;
                        $actividadRepo->save($actividad);

                        if ($structChanged && $aMed === 'cantidad') {
                            $dates = $calService->generateDailyColumns($aIni, $aFin);
                            $total = (int)round((float)$aCant);
                            $nDates = count($dates);
                            if ($nDates > 0 && $total > 0) {
                                $base = (int)floor($total / $nDates);
                                $rem = $total - ($base * $nDates);
                                foreach ($dates as $idx => $d) {
                                    $val = $base + (($idx < $rem) ? 1 : 0);
                                    $avanceRepo->save([
                                        'programa_id' => $programaId,
                                        'actividad_id' => $actividadId,
                                        'periodo_fecha' => (string)$d,
                                        'tipo' => 'P',
                                        'valor' => $val,
                                    ]);
                                }
                            }
                        }

                        $recalcularRangoPrograma($programaId);
                        header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=act_ed');
                        exit;
                    }
                }
            }
        } elseif ($action === 'subir_evidencia') {
            $programaId = trim((string)($_POST['programa_id'] ?? ''));
            $actividadId = trim((string)($_POST['actividad_id'] ?? ''));
            $fechaCol = trim((string)($_POST['fecha_columna'] ?? ''));
            $comentario = trim((string)($_POST['comentario'] ?? ''));

            if ($programaId === '' || $actividadId === '' || $fechaCol === '') {
                $error = 'Solicitud inválida para evidencia.';
            } else {
                $programa = $programaRepo->find($programaId);
                $actividad = $actividadRepo->find($actividadId);
                if (!$programa || !$actividad) {
                    $error = 'Programa o actividad no encontrada.';
                } elseif (($actividad['programa_id'] ?? '') !== $programaId) {
                    $error = 'Actividad no pertenece al programa.';
                } elseif (strtolower((string)($actividad['tipo_medicion'] ?? '')) !== 'entregable') {
                    $error = 'Solo se permite evidencia en actividades de tipo Entregable.';
                } elseif (!$canManage && $userId !== '' && (string)($actividad['responsable_id'] ?? '') !== $userId) {
                    $error = 'No tiene permisos para cargar evidencia de esta actividad.';
                } else {
                    // Normaliza $_FILES (multiple)
                    $archivos = [];
                    if (isset($_FILES['ev_archivo']) && is_array($_FILES['ev_archivo'])) {
                        $raw = $_FILES['ev_archivo'];
                        if (is_array($raw['name'] ?? null)) {
                            $n = count($raw['name']);
                            for ($i = 0; $i < $n; $i++) {
                                $archivos[] = [
                                    'name'     => (string)($raw['name'][$i] ?? ''),
                                    'type'     => (string)($raw['type'][$i] ?? ''),
                                    'tmp_name' => (string)($raw['tmp_name'][$i] ?? ''),
                                    'error'    => (int)($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                                    'size'     => (int)($raw['size'][$i] ?? 0),
                                ];
                            }
                        } else {
                            $archivos[] = [
                                'name'     => (string)($raw['name'] ?? ''),
                                'type'     => (string)($raw['type'] ?? ''),
                                'tmp_name' => (string)($raw['tmp_name'] ?? ''),
                                'error'    => (int)($raw['error'] ?? UPLOAD_ERR_NO_FILE),
                                'size'     => (int)($raw['size'] ?? 0),
                            ];
                        }
                    }
                    $archivos = array_values(array_filter($archivos, function ($f) {
                        return ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    }));
                    if (count($archivos) === 0) {
                        $error = 'Seleccione al menos un archivo.';
                    } else {
                        $cfg = $container['config'] ?? [];
                        $uploadBase = $cfg['upload_path'] ?? (dirname(__DIR__) . '/../storage/uploads');
                        $uploadBase = rtrim((string)$uploadBase, "/\\");
                        $subdir = $uploadBase . DIRECTORY_SEPARATOR . 'programas_trabajo'
                            . DIRECTORY_SEPARATOR . $programaId
                            . DIRECTORY_SEPARATOR . $actividadId
                            . DIRECTORY_SEPARATOR . str_replace('-', '', $fechaCol);
                        if (!is_dir($subdir) && !@mkdir($subdir, 0775, true) && !is_dir($subdir)) {
                            $error = 'No se pudo crear carpeta de evidencias.';
                        } else {
                            $allowed = ['jpg','jpeg','png','gif','webp','mp4','webm','mov','pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt','zip','rar'];
                            $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
                            foreach ($archivos as $f) {
                                $err = (int)($f['error'] ?? UPLOAD_ERR_OK);
                                if ($err !== UPLOAD_ERR_OK) { $error = 'Error al subir archivo.'; break; }
                                $tmp = (string)($f['tmp_name'] ?? '');
                                $size = (int)($f['size'] ?? 0);
                                $nombreOriginal = (string)($f['name'] ?? '');
                                if ($size <= 0) { $error = 'Archivo vacío: ' . $nombreOriginal; break; }
                                $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                                if ($ext === '' || !in_array($ext, $allowed, true)) { $error = 'Tipo no permitido: ' . $nombreOriginal; break; }
                                $mime = '';
                                if (function_exists('finfo_open')) {
                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                                    if ($finfo) { finfo_close($finfo); }
                                }
                                $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
                                $safeBase = $safeBase !== '' ? $safeBase : 'evidencia';
                                $fileName = 'ev_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $ext;
                                $dest = $subdir . DIRECTORY_SEPARATOR . $fileName;
                                if (!@move_uploaded_file($tmp, $dest)) { $error = 'No se pudo guardar archivo.'; break; }

                                $tipoArchivo = (substr((string)$mime, 0, 6) === 'image/') ? 'imagen' : ((substr((string)$mime, 0, 6) === 'video/') ? 'video' : 'documento');
                                $evidRepo->save([
                                    'programa_id' => $programaId,
                                    'actividad_id' => $actividadId,
                                    'fecha_columna' => $fechaCol,
                                    'tipo_registro' => 'E',
                                    'ruta' => $dest,
                                    'nombre_original' => $nombreOriginal,
                                    'nombre_archivo' => $fileName,
                                    'tipo_archivo' => $tipoArchivo,
                                    'tipo_mime' => $mime,
                                    'tamaño' => $size,
                                    'usuario_carga' => (string)($user['id'] ?? ''),
                                    'fecha_carga' => $now,
                                    'comentario' => $comentario,
                                ]);
                            }
                        }
                    }
                }
            }
            if ($error === '') {
                header('Location: ' . $basePath . '/programa-trabajo-gantt.php?programa_id=' . rawurlencode($programaId) . '&msg=ev_ok');
                exit;
            }
        }
}

$programas = $programaRepo->findAll();
$programaId = trim((string)($_GET['programa_id'] ?? ''));
$programaActual = $programaId !== '' ? $programaRepo->find($programaId) : (count($programas) ? $programas[0] : null);
if ($programaActual && $programaId === '') {
    $programaId = (string)($programaActual['id'] ?? '');
}

$canEditProgramaActual = $usuarioPuedeEditarPrograma($programaActual);

$actividades = $programaActual ? $actividadRepo->findByPrograma((string)$programaActual['id']) : [];
$avances = $programaActual ? $avanceRepo->findByPrograma((string)$programaActual['id']) : [];
$evidencias = $programaActual ? $evidRepo->findByPrograma((string)$programaActual['id']) : [];

$activityRanges = [];
$globalStart = '';
$globalEnd = '';
foreach ($actividades as $a) {
    $aid = (string)($a['id'] ?? '');
    $ini = (string)($a['fecha_inicio'] ?? '');
    $fin = (string)($a['fecha_fin'] ?? '');
    if ($ini === '' || $fin === '') continue;
    $activityRanges[$aid] = ['inicio' => $ini, 'fin' => $fin];
    if ($globalStart === '' || $ini < $globalStart) $globalStart = $ini;
    if ($globalEnd === '' || $fin > $globalEnd) $globalEnd = $fin;
}
if ($globalStart === '' && $programaActual) $globalStart = (string)($programaActual['fecha_inicio'] ?? '');
if ($globalEnd === '' && $programaActual) $globalEnd = (string)($programaActual['fecha_fin'] ?? '');

$columnas = ($globalStart !== '' && $globalEnd !== '')
    ? $calService->generateDailyColumns($globalStart, $globalEnd)
    : [];

$today = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');

$avanceMap = [];
foreach ($avances as $av) {
    $aid = (string)($av['actividad_id'] ?? '');
    $tipo = strtoupper((string)($av['tipo'] ?? 'P'));
    $f = (string)($av['periodo_fecha'] ?? '');
    $avanceMap[$aid][$tipo][$f] = (float)($av['valor'] ?? 0);
}

$evidMap = [];
foreach ($evidencias as $ev) {
    $aid = (string)($ev['actividad_id'] ?? '');
    $f = (string)($ev['fecha_columna'] ?? '');
    if ($aid === '' || $f === '') continue;
    if (!isset($evidMap[$aid][$f])) $evidMap[$aid][$f] = [];
    $evidMap[$aid][$f][] = $ev;
}

$resumenBarras = [];
$resumenActividades = [];
$totalAtrasadas = 0;
$totalCumplidas = 0;
$totalEvidencias = is_array($evidencias) ? count($evidencias) : 0;
$sumAvance = 0.0;
foreach ($actividades as $act) {
    $aid = (string)($act['id'] ?? '');
    $sumP = 0.0;
    $sumE = 0.0;
    $maxE = 0.0;
    $sumPtoDate = 0.0;
    $sumEtoDate = 0.0;
    foreach ($columnas as $f) {
        $vP = (float)($avanceMap[$aid]['P'][$f] ?? 0);
        $sumP += $vP;
        $vE = (float)($avanceMap[$aid]['E'][$f] ?? 0);
        $sumE += $vE;
        if ($vE > $maxE) $maxE = $vE;
        if ($f <= $today) {
            $sumPtoDate += $vP;
            $sumEtoDate += $vE;
        }
    }
    $med = (string)($act['tipo_medicion'] ?? 'cantidad');
    $total = (float)($act['cantidad'] ?? 0);
    $pct = 0.0;
    $diasActivos = 0;
    $diasHechos = 0;
    $terminada = !empty($act['terminada']);
    if ($med === 'porcentaje' || $med === 'entregable') {
        $ini = (string)($act['fecha_inicio'] ?? '');
        $fin = (string)($act['fecha_fin'] ?? '');
        $colsAct = ($ini !== '' && $fin !== '') ? $calService->generateDailyColumns($ini, $fin) : [];
        $diasActivos = count($colsAct);
        if ($diasActivos > 0) {
            foreach ($colsAct as $d) {
                $hasCheck = ((float)($avanceMap[$aid]['E'][$d] ?? 0)) > 0;
                if ($med === 'entregable') {
                    $hasEv = !empty($evidMap[$aid][$d] ?? []);
                    if ($hasCheck || $hasEv) $diasHechos++;
                } else {
                    if ($hasCheck) $diasHechos++;
                }
            }
            $pct = round(min(100, ($diasHechos / $diasActivos) * 100), 1);
        } else {
            $pct = 0.0;
        }
        if ($med === 'entregable' && $terminada) {
            $pct = 100.0;
            $diasHechos = $diasActivos;
        }
    } else {
        $pct = $total > 0 ? round(min(100, ($sumE / $total) * 100), 1) : 0.0;
    }

    // Estado
    $estado = 'sin_iniciar';
    if ($med === 'porcentaje' || $med === 'entregable') {
        if ($pct > 0 && $pct < 100) $estado = 'en_proceso';
        if ($pct >= 100) $estado = 'cumplido';
        $fin = (string)($act['fecha_fin'] ?? '');
        if ($fin !== '' && $today > $fin && $pct < 100) $estado = 'atrasado';
    } else {
        if ($sumE > 0 && $pct < 100) $estado = 'en_proceso';
        if ($pct >= 100) $estado = 'cumplido';
        if ($sumE > 0 && $sumE > max($total, $sumP)) $estado = 'excedido';
        if ($sumEtoDate < $sumPtoDate && $pct < 100) $estado = 'atrasado';
    }

    $resumenBarras[] = [
        'actividad' => (string)($act['actividad'] ?? ''),
        'porcentaje' => $pct,
    ];
    $resumenActividades[$aid] = [
        'sumP' => $sumP,
        'sumE' => $sumE,
        'diff' => $sumE - $sumP,
        'avance' => $pct,
        'estado' => $estado,
        'sumPtoDate' => $sumPtoDate,
        'sumEtoDate' => $sumEtoDate,
        'diasActivos' => $diasActivos,
        'diasHechos' => $diasHechos,
    ];
    $sumAvance += $pct;
    if ($estado === 'atrasado') $totalAtrasadas++;
    if ($estado === 'cumplido' || $estado === 'excedido') $totalCumplidas++;
}

$avanceGlobal = count($actividades) > 0 ? round($sumAvance / count($actividades), 1) : 0.0;

// Índice de "hoy" en columnas
$hoyIdx = -1;
foreach ($columnas as $i => $f) {
    if ((string)$f === (string)$today) { $hoyIdx = $i; break; }
}

// Agrupación semanal de columnas (basada en lunes - semana ISO)
$weekGroups = [];
if (!empty($columnas)) {
    $current = null;
    foreach ($columnas as $f) {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', (string)$f);
        if (!$dt) continue;
        $year = (int)$dt->format('o');
        $week = (int)$dt->format('W');
        $key = $year . '-' . $week;
        if ($current === null || $current['key'] !== $key) {
            if ($current !== null) $weekGroups[] = $current;
            $current = [
                'key' => $key,
                'year' => $year,
                'week' => $week,
                'count' => 0,
                'first' => $f,
                'last' => $f,
            ];
        }
        $current['count']++;
        $current['last'] = $f;
    }
    if ($current !== null) $weekGroups[] = $current;
}

// Métricas por persona (responsable)
$perResponsable = [];
foreach ($actividades as $act) {
    $aid = (string)($act['id'] ?? '');
    $rid = (string)($act['responsable_id'] ?? '');
    $rnom = (string)($act['responsable'] ?? '');
    $key = $rid !== '' ? $rid : ('nom:' . $rnom);
    if (!isset($perResponsable[$key])) {
        $perResponsable[$key] = [
            'id' => $rid,
            'nombre' => $rnom !== '' ? $rnom : ($rid !== '' ? $rid : 'Sin responsable'),
            'total' => 0,
            'cumplidas' => 0,
            'atrasadas' => 0,
            'avance_sum' => 0.0,
        ];
    }
    $perResponsable[$key]['total']++;
    $ra = $resumenActividades[$aid] ?? null;
    if ($ra) {
        $perResponsable[$key]['avance_sum'] += (float)($ra['avance'] ?? 0);
        if (($ra['estado'] ?? '') === 'cumplido' || ($ra['estado'] ?? '') === 'excedido') {
            $perResponsable[$key]['cumplidas']++;
        }
        if (($ra['estado'] ?? '') === 'atrasado') {
            $perResponsable[$key]['atrasadas']++;
        }
    }
}
$perResponsableList = array_values($perResponsable);
foreach ($perResponsableList as &$pp) {
    $pp['avance'] = $pp['total'] > 0 ? round($pp['avance_sum'] / $pp['total'], 1) : 0.0;
    unset($pp['avance_sum']);
}
unset($pp);
usort($perResponsableList, function ($a, $b) {
    return ($b['avance'] ?? 0) <=> ($a['avance'] ?? 0);
});

// Export CSV (matriz del programa actual)
if (($_GET['export'] ?? '') === 'csv' && $programaActual) {
    $filename = 'programa_' . preg_replace('/[^a-zA-Z0-9]+/', '_', (string)($programaActual['nombre'] ?? 'programa')) . '_' . date('Ymd_His') . '.csv';
    while (ob_get_level() > 0) { @ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    // BOM para Excel
    fwrite($out, "\xEF\xBB\xBF");
    $head = ['No.','Actividad','Cantidad','Medición','Tipo','Responsable','Inicio','Fin','Avance %','Estado','L'];
    foreach ($columnas as $f) { $head[] = $f; }
    fputcsv($out, $head);
    foreach ($actividades as $a) {
        $aid = (string)($a['id'] ?? '');
        $ra = $resumenActividades[$aid] ?? ['avance'=>0,'estado'=>'sin_iniciar'];
        // Fila P
        $rowP = [
            (int)($a['numero'] ?? 0),
            (string)($a['actividad'] ?? ''),
            (string)($a['cantidad'] ?? ''),
            (string)($a['tipo_medicion'] ?? ''),
            (string)($a['tipo'] ?? ''),
            (string)($a['responsable'] ?? ''),
            (string)($a['fecha_inicio'] ?? ''),
            (string)($a['fecha_fin'] ?? ''),
            (string)($ra['avance'] ?? 0),
            (string)($ra['estado'] ?? ''),
            'P',
        ];
        foreach ($columnas as $f) { $rowP[] = (string)($avanceMap[$aid]['P'][$f] ?? ''); }
        fputcsv($out, $rowP);
        // Fila E
        $rowE = ['', '', '', '', '', '', '', '', '', '', 'E'];
        foreach ($columnas as $f) { $rowE[] = (string)($avanceMap[$aid]['E'][$f] ?? ''); }
        fputcsv($out, $rowE);
    }
    fclose($out);
    exit;
}

if (($_GET['msg'] ?? '') === 'created') $success = 'Programa creado correctamente.';
if (($_GET['msg'] ?? '') === 'saved') $success = 'Avances guardados.';
if (($_GET['msg'] ?? '') === 'ev_ok') $success = 'Evidencia cargada.';
if (($_GET['msg'] ?? '') === 'act_ok') $success = 'Actividad agregada al programa.';
if (($_GET['msg'] ?? '') === 'term_ok') $success = 'Estado de actividad actualizado.';
if (($_GET['msg'] ?? '') === 'prog_upd') $success = 'Datos del programa actualizados.';
if (($_GET['msg'] ?? '') === 'prog_del') $success = 'Programa eliminado.';
if (($_GET['msg'] ?? '') === 'act_del') $success = 'Actividad eliminada del programa.';
if (($_GET['msg'] ?? '') === 'act_ed') $success = 'Actividad actualizada.';

$feriados = $calService->getFeriadosLey();
$users = array_values(array_filter($userRepo->findAll(), function ($u) {
    return ($u['activo'] ?? true) ? true : false;
}));

$pageTitle = 'Programa de Trabajo — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Administrativo'], ['label' => 'Programa de Trabajo']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/administrativo/programa-trabajo-gantt.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';

