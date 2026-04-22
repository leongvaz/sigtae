<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
$user = $auth->requireAuth();

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    header('Location: ' . $basePath . '/mis-tareas.php');
    exit;
}

$taskRepo = $container['repositories']['task'];
$stateService = $container['TaskStateService'];
$userRepo = $container['repositories']['user'];
$historyService = $container['HistoryService'];
$permissionService = $container['PermissionService'];
$constants = $container['constants'];

$task = $taskRepo->find($id);
if (!$task) {
    header('Location: ' . $basePath . '/mis-tareas.php');
    exit;
}
$task = $stateService->computeState($task);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $stateService->computeState($taskRepo->find($id));
    $action = $_POST['action'] ?? '';
    $responsablePost = $userRepo->find($task['responsable_id'] ?? '');
    $puedeEvaluarPost = empty($task['cancelada']) && $permissionService->canEvaluate($user, $task, $responsablePost);
    $esResponsable = ($task['responsable_id'] ?? '') === $user['id'];
    if (!empty($task['cancelada'])) {
        $error = 'La tarea está cancelada.';
    } elseif ($action === 'comentario_responsable' && $esResponsable) {
        $task['comentarios_responsable'] = trim($_POST['comentarios_responsable'] ?? '');
        $taskRepo->save($task);
        $historyService->log($task['id'], $user['id'], 'comentario_agregado', 'Comentario del responsable actualizado', []);
        $success = 'Comentario guardado.';
    } elseif ($action === 'evidencia' && $esResponsable) {
        $nombre = trim($_POST['ev_nombre'] ?? '') ?: 'Evidencia';
        $comentario = trim($_POST['ev_comentario'] ?? '');
        $evidencias = $task['evidencias'] ?? [];
        $version = count($evidencias) + 1;
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');

        // Normaliza $_FILES a una lista de archivos (soporta input con multiple).
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
        // Filtra "no file" (campo vacío)
        $archivos = array_values(array_filter($archivos, fn($f) => ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));

        $cfg = $container['config'] ?? [];
        $uploadBase = $cfg['upload_path'] ?? (dirname(__DIR__) . '/../storage/uploads');
        $uploadBase = rtrim((string)$uploadBase, "/\\");

        $nuevasEvidencias = [];

        if (count($archivos) === 0) {
            // Mantener compatibilidad: permite registrar evidencia solo-texto (sin adjunto)
            $nuevasEvidencias[] = [
                'id' => 'ev-' . uniqid('', true),
                'nombre_archivo' => $nombre,
                'ruta' => '',
                'nombre_original' => '',
                'tipo_archivo' => 'texto',
                'tipo_mime' => '',
                'tamaño' => 0,
                'version' => $version,
                'fecha_subida' => $now,
                'usuario_subida' => $user['id'],
                'comentario' => $comentario,
            ];
        } else {
            if (!is_dir($uploadBase) && !@mkdir($uploadBase, 0775, true) && !is_dir($uploadBase)) {
                $error = 'No se pudo crear la carpeta de uploads.';
            } else {
                $subdir = $uploadBase . DIRECTORY_SEPARATOR . 'tareas' . DIRECTORY_SEPARATOR . $task['id'];
                if (!is_dir($subdir) && !@mkdir($subdir, 0775, true) && !is_dir($subdir)) {
                    $error = 'No se pudo crear la carpeta de la tarea para uploads.';
                } else {
                    $allowed = [
                        // Imágenes
                        'jpg','jpeg','png','gif','webp',
                        // Video
                        'mp4','webm','mov',
                        // Documentos
                        'pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt',
                        // Otros comunes
                        'zip','rar',
                    ];

                    $idx = 0;
                    foreach ($archivos as $f) {
                        $idx++;
                        $err = (int)($f['error'] ?? UPLOAD_ERR_OK);
                        if ($err !== UPLOAD_ERR_OK) {
                            $error = 'No se pudo subir el archivo "' . htmlspecialchars($f['name']) . '" (código ' . $err . ').';
                            break;
                        }
                        $tmp = (string)($f['tmp_name'] ?? '');
                        $size = (int)($f['size'] ?? 0);
                        $nombreOriginal = (string)($f['name'] ?? '');

                        if ($size <= 0) {
                            $error = 'El archivo "' . $nombreOriginal . '" está vacío.';
                            break;
                        }

                        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                        if ($ext === '' || !in_array($ext, $allowed, true)) {
                            $error = 'Tipo de archivo no permitido: "' . $nombreOriginal . '".';
                            break;
                        }

                        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                        $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                        if ($finfo) { finfo_close($finfo); }

                        $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
                        $safeBase = $safeBase !== '' ? $safeBase : 'evidencia';
                        $fileName = 'ev_v' . $version . '_' . $idx . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $ext;
                        $dest = $subdir . DIRECTORY_SEPARATOR . $fileName;
                        if (!@move_uploaded_file($tmp, $dest)) {
                            $error = 'No se pudo guardar el archivo "' . $nombreOriginal . '" en el servidor.';
                            break;
                        }

                        $tipoArchivo = str_starts_with((string)$mime, 'image/')
                            ? 'imagen'
                            : (str_starts_with((string)$mime, 'video/') ? 'video' : 'documento');

                        $nuevasEvidencias[] = [
                            'id' => 'ev-' . uniqid('', true),
                            'nombre_archivo' => count($archivos) > 1 ? ($nombre . ' (' . $idx . '/' . count($archivos) . ')') : $nombre,
                            'ruta' => $dest,
                            'nombre_original' => $nombreOriginal,
                            'tipo_archivo' => $tipoArchivo,
                            'tipo_mime' => (string)$mime,
                            'tamaño' => $size,
                            'version' => $version,
                            'fecha_subida' => $now,
                            'usuario_subida' => $user['id'],
                            'comentario' => $comentario,
                        ];
                    }
                }
            }
        }

        if ($error === '' && count($nuevasEvidencias) > 0) {
            foreach ($nuevasEvidencias as $nev) {
                $evidencias[] = $nev;
            }
            $task['evidencias'] = $evidencias;
            $task = $stateService->computeState($task);
            $taskRepo->save($task);
            $cuantos = count($nuevasEvidencias);
            $historyService->log(
                $task['id'],
                $user['id'],
                'evidencia_subida',
                "Evidencia v{$version} agregada: {$nombre}" . ($cuantos > 1 ? " ({$cuantos} archivos)" : ''),
                []
            );
            $task = $taskRepo->find($id);
            $task = $stateService->computeState($task);
            $success = $cuantos > 1
                ? "Evidencia registrada ({$cuantos} archivos)."
                : 'Evidencia registrada.';
        }
    } elseif ($action === 'cancelar_tarea' && $permissionService->canCancelTask($user, $task)) {
        $motivo = trim($_POST['motivo_cancelacion'] ?? '');
        if ($motivo === '') {
            $error = 'Indique el motivo de cancelación.';
        } else {
            $task['cancelada'] = true;
            $task['motivo_cancelacion'] = $motivo;
            $task['cancelado_por_id'] = $user['id'];
            $task['cancelado_at'] = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('c');
            $task = $stateService->computeState($task);
            $taskRepo->save($task);
            $historyService->log($task['id'], $user['id'], 'tarea_cancelada', 'Tarea cancelada: ' . $motivo, []);
            $task = $stateService->computeState($taskRepo->find($id));
            $success = 'Tarea cancelada.';
        }
    } elseif ($action === 'reasignar_tarea') {
        $nid = trim($_POST['nuevo_responsable_id'] ?? '');
        $nuevo = $nid !== '' ? $userRepo->find($nid) : null;
        if (!$nuevo) {
            $error = 'Seleccione un responsable válido.';
        } elseif (!$permissionService->canReassignTask($user, $task, $nuevo)) {
            $error = 'No tiene permiso para reasignar a ese usuario.';
        } else {
            $ant = $task['responsable_id'] ?? '';
            $task['responsable_id'] = $nuevo['id'];
            $task['oficina_id'] = $nuevo['oficina_id'] ?? '';
            $task['departamento_id'] = $nuevo['departamento_id'] ?? '';
            $task['nivel_responsable'] = (int)($nuevo['nivel_jerarquico'] ?? 3);
            $task = $stateService->computeState($task);
            $taskRepo->save($task);
            $historyService->log(
                $task['id'],
                $user['id'],
                'tarea_reasignada',
                'Reasignación de responsable.',
                ['anterior' => $ant, 'nuevo' => $nuevo['id'] ?? '']
            );
            $task = $stateService->computeState($taskRepo->find($id));
            $success = 'Tarea reasignada.';
        }
    } elseif ($action === 'evaluar' && $puedeEvaluarPost) {
        $dictamen = $_POST['dictamen'] ?? '';
        $comentarios = trim($_POST['comentarios_evaluador'] ?? '');
        if ($dictamen === '') {
            $error = 'Seleccione un dictamen.';
        } elseif ($dictamen === 'insatisfactoria') {
            $task['tuvo_insatisfactoria'] = true;
            $task['pendiente_mejora'] = true;
            $task['dictamen'] = null;
            $task['evaluacion'] = null;
            $task['comentarios_evaluador'] = $comentarios;
            $task['porcentaje_cumplimiento'] = $stateService->porcentajeCumplimiento($task);
            $task = $stateService->computeState($task);
            $taskRepo->save($task);
            $historyService->log($task['id'], $user['id'], 'evaluacion_insatisfactoria', 'Evaluación insatisfactoria. Se requiere nuevo intento con evidencia. ' . $comentarios, []);
            $task = $taskRepo->find($id);
            $task = $stateService->computeState($task);
            $success = 'Evaluación registrada como insatisfactoria. El responsable puede cargar nueva evidencia para un nuevo intento.';
        } elseif ($dictamen === 'satisfactoria') {
            $huboInsatisfactoria = !empty($task['tuvo_insatisfactoria']);
            if ($huboInsatisfactoria) {
                $dictamenFinal = 'satisfactoria_fuera_tiempo';
                $task['evaluacion'] = 50;
            } else {
                $evs = $task['evidencias'] ?? [];
                $prim = $stateService->primeraFechaEvidencia($evs);
                $lim = $task['fecha_limite'] ?? null;
                if ($lim !== null && $lim !== '' && $prim && $prim > $lim) {
                    $dictamenFinal = 'satisfactoria_fuera_tiempo';
                    $task['evaluacion'] = 50;
                } else {
                    $dictamenFinal = 'satisfactoria';
                    $task['evaluacion'] = 100;
                }
            }
            $task['dictamen'] = $dictamenFinal;
            $task['comentarios_evaluador'] = $comentarios;
            $task['tuvo_insatisfactoria'] = false;
            $task['pendiente_mejora'] = false;
            $task['porcentaje_cumplimiento'] = $stateService->porcentajeCumplimiento($task);
            $task['fecha_entrega'] = $task['fecha_entrega'] ?? (count($task['evidencias'] ?? []) > 0 ? substr($task['evidencias'][0]['fecha_subida'] ?? '', 0, 10) : null);
            $task = $stateService->computeState($task);
            $taskRepo->save($task);
            $historyService->log($task['id'], $user['id'], 'evaluacion_registrada', "Evaluación: {$dictamenFinal}. {$comentarios}", []);
            $task = $taskRepo->find($id);
            $task = $stateService->computeState($task);
            $success = 'Evaluación registrada. Resultado: ' . ($dictamenFinal === 'satisfactoria' ? 'satisfactoria en tiempo' : 'satisfactoria fuera de tiempo') . ' (según plazo y evidencia).';
        } else {
            $error = 'Seleccione Satisfactoria o Insatisfactoria.';
        }
    }
}

$task = $stateService->computeState($taskRepo->find($id));
$asignador = $userRepo->find($task['asignador_id'] ?? '');
$responsable = $userRepo->find($task['responsable_id'] ?? '');
$puedeEvaluar = empty($task['cancelada']) && $permissionService->canEvaluate($user, $task, $responsable);
$esResponsable = ($task['responsable_id'] ?? '') === $user['id'];
$esAsignador = ($task['asignador_id'] ?? '') === $user['id'];
$puedeCancelar = $permissionService->canCancelTask($user, $task);
$candidatosReasignar = [];
foreach ($permissionService->getAssignableUsers($user) as $cand) {
    if (($cand['id'] ?? '') === ($task['responsable_id'] ?? '')) {
        continue;
    }
    if ($permissionService->canReassignTask($user, $task, $cand)) {
        $candidatosReasignar[] = $cand;
    }
}

$dictamenOpts = $constants['dictamen'] ?? [];
$historial = $historyService->getByTask($id);

$pageTitle = 'Tarea ' . ($task['folio'] ?? '') . ' — SIGTAE';
$breadcrumb = [['label' => 'Inicio', 'url' => '/dashboard.php'], ['label' => 'Mis tareas', 'url' => '/mis-tareas.php'], ['label' => $task['folio'] ?? '']];
$currentUser = $user;
ob_start();
include dirname(__DIR__) . '/views/tarea.php';
$content = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
