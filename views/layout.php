<?php
if (!isset($currentUser)) $currentUser = null;
$basePath     = $basePath ?? '';
$pageTitle    = $pageTitle ?? 'SIGTAE';
$pageSubtitle = $pageSubtitle ?? '';
$breadcrumb   = $breadcrumb ?? [];

require_once __DIR__ . '/partials/ui.php';

// Determina la página activa para resaltar en el sidebar
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isSuper       = !empty($currentUser['es_super_admin']);
$canUserAdmin  = $isSuper;
$puedeAsignar  = !empty($currentUser['puede_asignar']) || $isSuper;

// Estructura del sidebar: secciones con items
$navSections = [
    [
        'title' => 'Operación',
        'items' => [
            ['url' => '/dashboard.php',      'label' => 'Dashboard',     'icon' => 'bi-speedometer2', 'match' => ['dashboard.php']],
            ['url' => '/mis-tareas.php',     'label' => 'Mis tareas',    'icon' => 'bi-list-task',    'match' => ['mis-tareas.php']],
            ['url' => '/asignar-tarea.php',  'label' => 'Asignar tarea', 'icon' => 'bi-plus-square',  'match' => ['asignar-tarea.php'], 'visible' => $puedeAsignar],
            ['url' => '/calendario.php',     'label' => 'Calendario',    'icon' => 'bi-calendar3',    'match' => ['calendario.php']],
        ],
    ],
    [
        'title' => 'Por oficina',
        'items' => [
            ['url' => '/tareas-metrologia.php',   'label' => 'Metrología',              'icon' => 'bi-rulers',   'match' => ['tareas-metrologia.php']],
            ['url' => '/tareas-preparacion.php',  'label' => 'Prep. de medidores',      'icon' => 'bi-tools',    'match' => ['tareas-preparacion.php']],
        ],
    ],
    [
        'title' => 'Gestión',
        'items' => [
            ['url' => '/seguimiento.php', 'label' => 'Seguimiento', 'icon' => 'bi-search',        'match' => ['seguimiento.php']],
            ['url' => '/evaluacion.php',  'label' => 'Evaluación',  'icon' => 'bi-check2-square', 'match' => ['evaluacion.php']],
            ['url' => '/reportes.php',    'label' => 'Reportes',    'icon' => 'bi-graph-up',      'match' => ['reportes.php']],
            ['url' => '/historial.php',   'label' => 'Historial',   'icon' => 'bi-clock-history', 'match' => ['historial.php']],
            ['url' => '/ranking.php',     'label' => 'Ranking',     'icon' => 'bi-trophy',        'match' => ['ranking.php']],
        ],
    ],
    [
        'title' => 'Administración',
        'visible' => $isSuper || $canUserAdmin,
        'items' => [
            ['url' => '/admin-usuarios.php',     'label' => 'Usuarios',     'icon' => 'bi-people',      'match' => ['admin-usuarios.php'],     'visible' => $canUserAdmin],
            ['url' => '/admin-oficinas.php',     'label' => 'Oficinas',     'icon' => 'bi-building',    'match' => ['admin-oficinas.php'],     'visible' => $isSuper],
            ['url' => '/admin-delegaciones.php', 'label' => 'Delegaciones', 'icon' => 'bi-person-gear', 'match' => ['admin-delegaciones.php']],
        ],
    ],
];

function sigtaeNavIsActive(array $item, string $currentScript): bool {
    return in_array($currentScript, (array)($item['match'] ?? []), true);
}
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
            --sigtae-bg: #f4f6f9;
            --sigtae-white: #ffffff;
            --sigtae-success: #0d7d5c;
            --sigtae-warning: #c17d0a;
            --sigtae-danger: #b91c1c;
            --sb-width: 240px;
            --sb-width-collapsed: 68px;
            --tb-height: 56px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--sigtae-bg);
            color: #1e293b;
            margin: 0;
            min-height: 100vh;
        }
        a { text-decoration: none; }

        /* ========== Sidebar ========== */
        .sigtae-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sb-width);
            background: linear-gradient(180deg, var(--sigtae-navy) 0%, var(--sigtae-petrol) 100%);
            color: #cfd9e3;
            z-index: 1030;
            transition: width 0.2s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sigtae-sidebar .sb-brand {
            display: flex; align-items: center; gap: .6rem;
            height: var(--tb-height);
            padding: 0 1rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            white-space: nowrap;
        }
        .sigtae-sidebar .sb-brand i { font-size: 1.3rem; color: var(--sigtae-cyan); }
        .sigtae-sidebar .sb-nav {
            flex: 1;
            overflow-y: auto;
            padding: .75rem 0 1.5rem;
        }
        .sigtae-sidebar .sb-section {
            padding: .9rem 1rem .25rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            font-weight: 600;
            white-space: nowrap;
        }
        .sigtae-sidebar .sb-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .55rem 1rem;
            color: #cfd9e3;
            font-size: .93rem;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s, border-color .15s;
            white-space: nowrap;
        }
        .sigtae-sidebar .sb-link i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .sigtae-sidebar .sb-link:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }
        .sigtae-sidebar .sb-link.active {
            background: rgba(74,159,184,0.18);
            color: #fff;
            border-left-color: var(--sigtae-cyan);
            font-weight: 600;
        }
        .sigtae-sidebar .sb-foot {
            padding: .75rem 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: .75rem;
            color: rgba(255,255,255,0.5);
            white-space: nowrap;
        }

        /* Collapsed state */
        body.sb-collapsed .sigtae-sidebar { width: var(--sb-width-collapsed); }
        body.sb-collapsed .sigtae-sidebar .sb-brand span,
        body.sb-collapsed .sigtae-sidebar .sb-link span,
        body.sb-collapsed .sigtae-sidebar .sb-section,
        body.sb-collapsed .sigtae-sidebar .sb-foot { display: none; }
        body.sb-collapsed .sigtae-sidebar .sb-link { justify-content: center; padding-left: 0; padding-right: 0; }
        body.sb-collapsed .sigtae-main { margin-left: var(--sb-width-collapsed); }

        /* ========== Topbar ========== */
        .sigtae-topbar {
            position: sticky; top: 0;
            height: var(--tb-height);
            background: #fff;
            border-bottom: 1px solid var(--sigtae-gray-light);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            gap: 1rem;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .sigtae-topbar .tb-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--sigtae-navy);
            cursor: pointer;
            padding: .25rem .5rem;
            border-radius: 6px;
        }
        .sigtae-topbar .tb-toggle:hover { background: var(--sigtae-cyan-soft); }
        .sigtae-topbar .tb-breadcrumb .breadcrumb { margin: 0; font-size: .875rem; }
        .sigtae-topbar .tb-breadcrumb .breadcrumb-item.active { color: var(--sigtae-navy); font-weight: 500; }
        .sigtae-topbar .tb-user {
            display: flex; align-items: center; gap: .6rem;
            padding: .2rem .5rem;
            border-radius: 8px;
        }
        .sigtae-topbar .tb-user .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--sigtae-petrol), var(--sigtae-cyan));
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: .85rem;
        }
        .sigtae-topbar .tb-user .user-info { line-height: 1.1; }
        .sigtae-topbar .tb-user .user-info .name { font-size: .85rem; font-weight: 600; color: var(--sigtae-navy); }
        .sigtae-topbar .tb-user .user-info .role { font-size: .72rem; color: var(--sigtae-gray); }

        /* ========== Main ========== */
        .sigtae-main {
            margin-left: var(--sb-width);
            transition: margin-left 0.2s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sigtae-content {
            padding: 1.25rem 1.5rem 2rem;
            flex: 1;
        }
        .page-header {
            margin-bottom: 1.25rem;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--sigtae-navy);
            margin: 0 0 .25rem;
        }
        .page-header .page-sub {
            color: var(--sigtae-gray);
            font-size: .9rem;
            margin: 0;
        }

        /* ========== Cards / Badges ========== */
        .card { border: 1px solid var(--sigtae-gray-light); border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--sigtae-gray-light);
            font-weight: 600;
            color: var(--sigtae-navy);
            border-radius: 10px 10px 0 0;
            padding: .7rem 1rem;
            font-size: .95rem;
        }
        .card-header.card-header-accent {
            background: linear-gradient(180deg, #fff, var(--sigtae-cyan-soft));
        }
        .kpi-card { border-left: 4px solid var(--sigtae-cyan); }
        .kpi-card .kpi-icon {
            font-size: 1.75rem;
            color: var(--sigtae-cyan);
            opacity: 0.75;
        }
        .kpi-card .kpi-label { font-size: .75rem; color: var(--sigtae-gray); text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
        .kpi-card .kpi-value { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--sigtae-navy); }

        /* Estado badges unificadas */
        .badge-estado { font-size: .72rem; font-weight: 600; padding: .35em .6em; border-radius: 999px; text-transform: capitalize; }
        .badge-estado-asignada     { background: #dbeafe; color: #1d4ed8; }
        .badge-estado-en_proceso   { background: #fef3c7; color: #b45309; }
        .badge-estado-incumplimiento { background: #fee2e2; color: #b91c1c; }
        .badge-estado-vencida      { background: #fde68a; color: #a16207; }
        .badge-estado-atendida     { background: #d1fae5; color: #047857; }
        .badge-estado-cancelada    { background: #e5e7eb; color: #374151; }

        /* Prioridad badges */
        .badge-prioridad { font-size: .7rem; font-weight: 600; padding: .3em .55em; border-radius: 999px; text-transform: capitalize; }
        .badge-prioridad-alta { background: #fee2e2; color: #b91c1c; }
        .badge-prioridad-media { background: #fef3c7; color: #b45309; }
        .badge-prioridad-baja { background: #e5e7eb; color: #374151; }

        /* Chart cards */
        .chart-card .chart-wrap { position: relative; width: 100%; }

        /* Timeline */
        .sigtae-timeline {
            display: flex; align-items: center; gap: .5rem;
            padding: .5rem 0;
            flex-wrap: wrap;
        }
        .sigtae-timeline .tl-step {
            display: flex; align-items: center; gap: .5rem;
            padding: .4rem .75rem;
            background: #f1f5f9;
            border-radius: 999px;
            font-size: .82rem;
            color: var(--sigtae-gray);
        }
        .sigtae-timeline .tl-step.done { background: #d1fae5; color: #047857; }
        .sigtae-timeline .tl-step.current { background: var(--sigtae-cyan-soft); color: var(--sigtae-petrol); font-weight: 600; }
        .sigtae-timeline .tl-step.error { background: #fee2e2; color: #b91c1c; }
        .sigtae-timeline .tl-arrow { color: #cbd5e1; font-size: .9rem; }

        /* Chips */
        .sigtae-chip {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: var(--sigtae-gray);
            font-size: .78rem;
            border: 1px solid transparent;
        }
        .sigtae-chip.active { background: var(--sigtae-cyan-soft); color: var(--sigtae-petrol); border-color: var(--sigtae-cyan); font-weight: 600; }

        /* Días restantes barra */
        .dias-pill { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: .78rem; font-weight: 600; min-width: 40px; text-align: center; }
        .dias-pill.ok { background: #d1fae5; color: #047857; }
        .dias-pill.warn { background: #fef3c7; color: #b45309; }
        .dias-pill.danger { background: #fee2e2; color: #b91c1c; }
        .dias-pill.neutral { background: #e5e7eb; color: #6b7280; }

        /* Mobile */
        @media (max-width: 991px) {
            .sigtae-sidebar { transform: translateX(-100%); transition: transform .2s ease, width .2s ease; }
            body.sb-open .sigtae-sidebar { transform: translateX(0); }
            .sigtae-main { margin-left: 0 !important; }
            body.sb-open::after {
                content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.45); z-index: 1025;
            }
        }

        /* Tooltips: asegurar visibilidad */
        .tooltip { z-index: 1080; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <aside class="sigtae-sidebar" aria-label="Menú lateral">
        <a class="sb-brand" href="<?= htmlspecialchars($basePath) ?>/dashboard.php">
            <i class="bi bi-clipboard-check"></i>
            <span>SIGTAE</span>
        </a>
        <nav class="sb-nav">
            <?php foreach ($navSections as $section): ?>
                <?php if (isset($section['visible']) && !$section['visible']) continue; ?>
                <?php
                $visibleItems = array_filter($section['items'], fn($it) => !isset($it['visible']) || $it['visible']);
                if (empty($visibleItems)) continue;
                ?>
                <div class="sb-section"><?= htmlspecialchars($section['title']) ?></div>
                <?php foreach ($visibleItems as $it): ?>
                    <a class="sb-link <?= sigtaeNavIsActive($it, $currentScript) ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . $it['url']) ?>" title="<?= htmlspecialchars($it['label']) ?>">
                        <i class="bi <?= htmlspecialchars($it['icon']) ?>"></i>
                        <span><?= htmlspecialchars($it['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sb-foot">
            <div>SIGTAE · v1.0</div>
            <div>Laboratorio de Metrología</div>
        </div>
    </aside>

    <div class="sigtae-main">
        <header class="sigtae-topbar">
            <button type="button" class="tb-toggle" id="sbToggle" aria-label="Alternar menú">
                <i class="bi bi-list"></i>
            </button>
            <div class="tb-breadcrumb flex-grow-1">
                <?php if (!empty($breadcrumb)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <?php foreach ($breadcrumb as $i => $b): ?>
                                <li class="breadcrumb-item <?= $i === count($breadcrumb)-1 ? 'active' : '' ?>">
                                    <?php if (!empty($b['url']) && $i !== count($breadcrumb)-1): ?>
                                        <a href="<?= htmlspecialchars($basePath . $b['url']) ?>"><?= htmlspecialchars($b['label']) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($b['label']) ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>
            </div>
            <?php if ($currentUser): ?>
                <div class="tb-user">
                    <div class="user-avatar"><?= strtoupper(mb_substr(trim($currentUser['nombre'] ?? '?'), 0, 1)) ?></div>
                    <div class="user-info d-none d-md-block">
                        <div class="name"><?= htmlspecialchars($currentUser['nombre'] ?? '') ?></div>
                        <div class="role"><?= htmlspecialchars($currentUser['cargo'] ?? '') ?></div>
                    </div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($basePath) ?>/logout.php" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Salir</span>
                </a>
            <?php endif; ?>
        </header>

        <main class="sigtae-content">
            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Sidebar toggle con persistencia
        (function() {
            const body = document.body;
            const KEY = 'sigtae_sb_collapsed';
            if (window.innerWidth >= 992 && localStorage.getItem(KEY) === '1') {
                body.classList.add('sb-collapsed');
            }
            const btn = document.getElementById('sbToggle');
            if (btn) {
                btn.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        body.classList.toggle('sb-open');
                    } else {
                        body.classList.toggle('sb-collapsed');
                        localStorage.setItem(KEY, body.classList.contains('sb-collapsed') ? '1' : '0');
                    }
                });
            }
            // Cerrar sidebar móvil al hacer click fuera
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && body.classList.contains('sb-open')) {
                    const sb = document.querySelector('.sigtae-sidebar');
                    if (sb && !sb.contains(e.target) && !btn.contains(e.target)) {
                        body.classList.remove('sb-open');
                    }
                }
            });
        })();

        // Inicializa todos los tooltips de Bootstrap (llamado SIEMPRE después de cargar bootstrap.bundle)
        (function() {
            function initTooltips() {
                if (!window.bootstrap || !window.bootstrap.Tooltip) return;
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                    if (!bootstrap.Tooltip.getInstance(el)) new bootstrap.Tooltip(el);
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTooltips);
            } else {
                initTooltips();
            }
            // Re-inicializar si algún script añade más tarde (p. ej. DataTables)
            window.sigtaeInitTooltips = initTooltips;
        })();
    </script>
    <?php if (!empty($extraScripts)): foreach ((array)$extraScripts as $s): ?>
        <script src="<?= htmlspecialchars($s) ?>"></script>
    <?php endforeach; endif; ?>
    <?php if (!empty($inlineScript)): ?>
        <script><?= $inlineScript ?></script>
    <?php endif; ?>
</body>
</html>
