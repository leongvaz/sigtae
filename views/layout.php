<?php
if (!isset($currentUser)) $currentUser = null;
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'SIGTAE';
$pageSubtitle = $pageSubtitle ?? '';
$breadcrumb = $breadcrumb ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --sigtae-navy: #0c2340;
            --sigtae-petrol: #1a4d6d;
            --sigtae-cyan: #4a9fb8;
            --sigtae-cyan-soft: #e8f4f8;
            --sigtae-gray: #5c6b7a;
            --sigtae-gray-light: #e2e8ee;
            --sigtae-white: #f8fafc;
            --sigtae-success: #0d7d5c;
            --sigtae-warning: #c17d0a;
            --sigtae-danger: #b91c1c;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--sigtae-white); color: #1e293b; }
        .navbar-sigtae { background: linear-gradient(135deg, var(--sigtae-navy), var(--sigtae-petrol)); box-shadow: 0 4px 14px rgba(0,0,0,0.15); }
        .navbar-sigtae .navbar-brand { color: #fff !important; font-weight: 700; }
        .navbar-sigtae .nav-link { color: rgba(255,255,255,0.9) !important; font-weight: 500; }
        .navbar-sigtae .nav-link:hover, .navbar-sigtae .nav-link.active { color: #fff !important; background: rgba(255,255,255,0.12); border-radius: 8px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .card-header { background: linear-gradient(180deg, #fff, var(--sigtae-cyan-soft)); border-bottom: 1px solid var(--sigtae-gray-light); font-weight: 700; border-radius: 12px 12px 0 0; }
        .kpi-card { border-left: 4px solid var(--sigtae-cyan); }
        .badge-estado-asignada { background: #e0f2fe; color: #0369a1; }
        .badge-estado-en_proceso { background: #fef3c7; color: #b45309; }
        .badge-estado-incumplimiento { background: #fee2e2; color: #b91c1c; }
        .badge-estado-vencida { background: #fef3c7; color: #a16207; }
        .badge-estado-atendida { background: #d1fae5; color: #047857; }
        .sidebar-soft { background: #fff; border-right: 1px solid var(--sigtae-gray-light); }
        .navbar-sigtae .navbar-brand { font-size: 1.1rem; }
        main { min-height: calc(100vh - 56px); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-sigtae">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= htmlspecialchars($basePath) ?>/dashboard.php"><i class="bi bi-clipboard-check me-2"></i>SIGTAE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link <?= (basename($_SERVER['PHP_SELF'] ?? '') === 'dashboard.php') ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath) ?>/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/mis-tareas.php">Mis tareas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/asignar-tarea.php">Asignar tarea</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/seguimiento.php">Seguimiento</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/evaluacion.php">Evaluación</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/historial.php">Historial</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/ranking.php">Ranking</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($basePath) ?>/calendario.php">Calendario</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Admin</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/admin-usuarios.php">Usuarios</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/admin-delegaciones.php">Delegaciones</a></li>
                        </ul>
                    </li>
                </ul>
                <span class="navbar-text text-white me-3"><?= $currentUser ? htmlspecialchars($currentUser['nombre']) . ' (' . htmlspecialchars($currentUser['cargo'] ?? '') . ')' : '' ?></span>
                <a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($basePath) ?>/logout.php">Salir</a>
            </div>
        </div>
    </nav>
    <main class="container-fluid py-4">
        <?php if (!empty($breadcrumb)): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $i => $b): ?>
                        <li class="breadcrumb-item <?= $i === count($breadcrumb)-1 ? 'active' : '' ?>">
                            <?php if (!empty($b['url'])): ?><a href="<?= htmlspecialchars($basePath . $b['url']) ?>"><?php endif; ?>
                            <?= htmlspecialchars($b['label']) ?>
                            <?php if (!empty($b['url'])): ?></a><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <?php if (!empty($extraScripts)): foreach ((array)$extraScripts as $s): ?>
        <script src="<?= htmlspecialchars($s) ?>"></script>
    <?php endforeach; endif; ?>
    <?php if (!empty($inlineScript)): ?>
        <script><?= $inlineScript ?></script>
    <?php endif; ?>
</body>
</html>
