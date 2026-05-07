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
$canAdminMetEquipos = $isSuper || in_array(strtoupper(trim((string)($currentUser['rpe'] ?? ''))), ['G46B8','9MMUY'], true);
$canAmi = \App\Services\AmiGuard::canAccess($currentUser ?? null);

// Sidebar colapsable: grupos -> subgrupos -> enlaces
$navTree = [
    [
        'id' => 'adm',
        'label' => 'Administrativo',
        'icon' => 'bi-grid-1x2',
        'children' => [
            [
                'id' => 'adm-comp',
                'label' => 'Compromisos',
                'icon' => 'bi-clipboard-check',
                'children' => [
                    ['url' => '/dashboard.php',     'label' => 'Dashboard',     'icon' => 'bi-speedometer2', 'match' => ['dashboard.php']],
                    ['url' => '/mis-tareas.php',    'label' => 'Mis tareas',    'icon' => 'bi-list-task',    'match' => ['mis-tareas.php']],
                    ['url' => '/asignar-tarea.php', 'label' => 'Asignar tarea', 'icon' => 'bi-plus-square',  'match' => ['asignar-tarea.php'], 'visible' => $puedeAsignar],
                    ['url' => '/calendario.php',    'label' => 'Calendario',    'icon' => 'bi-calendar3',    'match' => ['calendario.php']],
                ],
            ],
            [
                'id' => 'adm-prog',
                'label' => 'Programa de trabajo',
                'icon' => 'bi-bar-chart-steps',
                'children' => [
                    ['url' => '/programa-trabajo-gantt.php', 'label' => 'Programa de Trabajo', 'icon' => 'bi-bar-chart-steps', 'match' => ['programa-trabajo-gantt.php']],
                ],
            ],
            [
                'id' => 'adm-prod',
                'label' => 'Productividad',
                'icon' => 'bi-graph-up',
                'children' => [
                    ['url' => '/ranking.php',    'label' => 'Ranking',    'icon' => 'bi-trophy',        'match' => ['ranking.php']],
                    ['url' => '/evaluacion.php', 'label' => 'Evaluación', 'icon' => 'bi-check2-square', 'match' => ['evaluacion.php']],
                ],
            ],
            [
                'id' => 'adm-gestion',
                'label' => 'Gestión',
                'icon' => 'bi-kanban',
                'children' => [
                    ['url' => '/seguimiento.php', 'label' => 'Seguimiento', 'icon' => 'bi-search',        'match' => ['seguimiento.php']],
                    ['url' => '/reportes.php',    'label' => 'Reportes',    'icon' => 'bi-graph-up',      'match' => ['reportes.php']],
                    ['url' => '/historial.php',   'label' => 'Historial',   'icon' => 'bi-clock-history', 'match' => ['historial.php']],
                ],
            ],
        ],
    ],
    [
        'id' => 'met',
        'label' => 'Metrología',
        'icon' => 'bi-rulers',
        'children' => [
            [
                'id' => 'met-cal',
                'label' => 'Calibración',
                'icon' => 'bi-activity',
                'children' => [
                    ['url' => '/metrologia-recepcion.php',      'label' => 'Recepción',         'icon' => 'bi-box-arrow-in-down',  'match' => ['metrologia-recepcion.php']],
                    ['url' => '/metrologia-bitacora.php',       'label' => 'Bitácora',          'icon' => 'bi-journal-text',       'match' => ['metrologia-bitacora.php']],
                    ['url' => '#', 'label' => 'Orden de trabajo (próximamente)',  'icon' => 'bi-clipboard2-check',   'match' => [], 'disabled' => true],
                    ['url' => '#', 'label' => 'Entrega (próximamente)',           'icon' => 'bi-box-arrow-up-right', 'match' => [], 'disabled' => true],
                    ['url' => '#', 'label' => 'Informes (próximamente)',          'icon' => 'bi-journal-text',       'match' => [], 'disabled' => true],
                    // Antes vivían en "Metrología > Gestión"
                    ['url' => '/metrologia-dashboard.php',    'label' => 'Dashboard',         'icon' => 'bi-speedometer',    'match' => ['metrologia-dashboard.php']],
                    ['url' => '/metrologia-solicitudes.php',  'label' => 'Solicitudes',       'icon' => 'bi-inbox',          'match' => ['metrologia-solicitudes.php']],
                    ['url' => '/metrologia-expedientes.php',  'label' => 'Expedientes',       'icon' => 'bi-folder2-open',   'match' => ['metrologia-expedientes.php','metrologia-expediente.php']],
                    ['url' => '/metrologia-autorizacion.php', 'label' => 'Autorización',      'icon' => 'bi-shield-check',   'match' => ['metrologia-autorizacion.php']],
                    ['url' => '/metrologia-reportes.php',     'label' => 'Concentrado Zonas', 'icon' => 'bi-table',          'match' => ['metrologia-reportes.php']],
                    ['url' => '/metrologia-programa.php',     'label' => 'Programa anual',    'icon' => 'bi-calendar2-week', 'match' => ['metrologia-programa.php']],
                    ['url' => '/metrologia-equipos.php',      'label' => 'Catálogo de equipos', 'icon' => 'bi-box-seam',      'match' => ['metrologia-equipos.php'], 'visible' => $canAdminMetEquipos],
                ],
            ],
            [
                'id' => 'met-prod',
                'label' => 'Productividad',
                'icon' => 'bi-graph-up',
                'children' => [
                    ['url' => '/tareas-metrologia.php', 'label' => 'Productividad', 'icon' => 'bi-graph-up', 'match' => ['tareas-metrologia.php']],
                ],
            ],
            [
                'id' => 'met-sig',
                'label' => 'SIG',
                'icon' => 'bi-diagram-3',
                'children' => [
                    ['url' => '/metrologia-sig.php', 'label' => 'SIG', 'icon' => 'bi-diagram-3', 'match' => ['metrologia-sig.php']],
                ],
            ],
            [
                'id' => 'met-admin',
                'label' => 'Administración',
                'icon' => 'bi-database-gear',
                'children' => [
                    ['url' => '#', 'label' => 'Base de datos (próximamente)', 'icon' => 'bi-database', 'match' => [], 'disabled' => true],
                ],
                'visible' => false,
            ],
        ],
    ],
    [
        'id' => 'prep',
        'label' => 'Preparación de medidores',
        'icon' => 'bi-tools',
        'children' => [
            [
                'id' => 'prep-prod',
                'label' => 'Productividad',
                'icon' => 'bi-graph-up',
                'children' => [
                    ['url' => '/tareas-preparacion.php', 'label' => 'Productividad', 'icon' => 'bi-graph-up', 'match' => ['tareas-preparacion.php']],
                ],
            ],
            [
                'id' => 'prep-control',
                'label' => 'Control de medidores y sellos',
                'icon' => 'bi-boxes',
                'children' => [
                    ['url' => '/prep-compra-nacional.php', 'label' => 'Compra nacional', 'icon' => 'bi-cart', 'match' => ['prep-compra-nacional.php']],
                    ['url' => '/prep-muestreos.php', 'label' => 'Muestreos', 'icon' => 'bi-clipboard2-data', 'match' => ['prep-muestreos.php']],
                ],
            ],
            [
                'id' => 'prep-minutas',
                'label' => 'Minutas de supervisión',
                'icon' => 'bi-file-earmark-text',
                'children' => [
                    ['url' => '/prep-minutas-supervision.php', 'label' => 'Minutas de supervisión', 'icon' => 'bi-file-earmark-text', 'match' => ['prep-minutas-supervision.php']],
                ],
            ],
            [
                'id' => 'prep-soltras',
                'label' => 'Soltras',
                'icon' => 'bi-diagram-3',
                'children' => [
                    ['url' => '/prep-soltras.php', 'label' => 'Soltras', 'icon' => 'bi-diagram-3', 'match' => ['prep-soltras.php']],
                ],
            ],
            [
                'id' => 'prep-entrega',
                'label' => 'Entrega de medidores',
                'icon' => 'bi-calendar-check',
                'children' => [
                    ['url' => '/prep-entrega-medidores.php', 'label' => 'Entrega de medidores', 'icon' => 'bi-calendar-check', 'match' => ['prep-entrega-medidores.php']],
                ],
            ],
        ],
    ],
    [
        'id' => 'ami',
        'label' => 'AMI',
        'icon' => 'bi-router',
        'visible' => $canAmi,
        'children' => [
            [
                'id' => 'ami-sigami',
                'label' => 'SIGAMI',
                'icon' => 'bi-arrow-repeat',
                'children' => [
                    ['url' => '/ami-cambio-sigami.php',    'label' => 'Cambio SIGAMI',     'icon' => 'bi-pencil-square', 'match' => ['ami-cambio-sigami.php']],
                    ['url' => '/ami-consultar-sigami.php', 'label' => 'Consultar SIGAMI', 'icon' => 'bi-search',        'match' => ['ami-consultar-sigami.php']],
                    ['url' => '/ami-iusa-sinamed.php', 'label' => 'IUSA — SINAMED', 'icon' => 'bi-cloud-arrow-up', 'match' => ['ami-iusa-sinamed.php']],
                ],
            ],
        ],
    ],
    [
        'id' => 'admin',
        'label' => 'Admin',
        'icon' => 'bi-gear',
        'visible' => $isSuper || $canUserAdmin,
        'children' => [
            [
                'id' => 'admin-sistema',
                'label' => 'Sistema',
                'icon' => 'bi-gear-wide-connected',
                'children' => [
                    ['url' => '/admin-usuarios.php',     'label' => 'Usuarios',     'icon' => 'bi-people',      'match' => ['admin-usuarios.php'],     'visible' => $canUserAdmin],
                    ['url' => '/admin-oficinas.php',     'label' => 'Oficinas',     'icon' => 'bi-building',    'match' => ['admin-oficinas.php'],     'visible' => $isSuper],
                    ['url' => '/admin-delegaciones.php', 'label' => 'Delegaciones', 'icon' => 'bi-person-gear', 'match' => ['admin-delegaciones.php']],
                ],
            ],
        ],
    ],
];

function sigtaeNavIsActive(array $item, string $currentScript): bool {
    return in_array($currentScript, (array)($item['match'] ?? []), true);
}

function sigtaeNavTreeHasActive(array $node, string $currentScript): bool {
    if (!empty($node['match']) && sigtaeNavIsActive($node, $currentScript)) return true;
    foreach ((array)($node['children'] ?? []) as $ch) {
        if (isset($ch['visible']) && !$ch['visible']) continue;
        if (sigtaeNavTreeHasActive((array)$ch, $currentScript)) return true;
    }
    return false;
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
    <?php if (!empty($extraStyles)): foreach ((array)$extraStyles as $href): ?>
        <link href="<?= htmlspecialchars($href) ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
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

        /* ========== Scrollbars (acorde al UI) ========== */
        html { scrollbar-gutter: stable; }
        body {
            scrollbar-width: thin;
            scrollbar-color: rgba(74,159,184,0.55) rgba(226,232,238,0.55);
        }
        /* WebKit */
        body::-webkit-scrollbar { width: 10px; height: 10px; }
        body::-webkit-scrollbar-track { background: rgba(226,232,238,0.55); }
        body::-webkit-scrollbar-thumb {
            background: rgba(74,159,184,0.55);
            border-radius: 999px;
            border: 2px solid rgba(226,232,238,0.55);
        }
        body::-webkit-scrollbar-thumb:hover { background: rgba(74,159,184,0.75); }

        /* Sidebar nav scrollbar */
        .sigtae-sidebar .sb-nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.35) rgba(255,255,255,0.10);
        }
        .sigtae-sidebar .sb-nav::-webkit-scrollbar { width: 10px; }
        .sigtae-sidebar .sb-nav::-webkit-scrollbar-track { background: rgba(255,255,255,0.10); }
        .sigtae-sidebar .sb-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.30);
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,0.10);
        }
        .sigtae-sidebar .sb-nav::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.45); }

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
            overflow-x: hidden;
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
        .sigtae-sidebar .sb-group { padding: .15rem .35rem; }
        .sigtae-sidebar .sb-group-toggle,
        .sigtae-sidebar .sb-sub-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .55rem 1rem;
            color: #cfd9e3;
            font-size: .93rem;
            white-space: normal;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s, border-color .15s;
            cursor: pointer;
            user-select: none;
            text-align: left;
        }
        .sigtae-sidebar .sb-sub-toggle { padding-left: 1.2rem; font-size: .9rem; opacity: .95; }
        .sigtae-sidebar .sb-group-toggle:hover,
        .sigtae-sidebar .sb-sub-toggle:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .sigtae-sidebar .sb-group-toggle[aria-expanded="true"],
        .sigtae-sidebar .sb-sub-toggle[aria-expanded="true"] {
            /* Activo (menús superiores) estilo "pill" */
            position: relative;
            background: rgba(255,255,255,0.10);
            color: #fff;
            border-left-color: transparent;
            border-radius: 999px;
            margin: 0.12rem 0.6rem;
            padding-left: calc(1rem - 0.2rem);
            box-shadow:
                0 6px 16px rgba(0,0,0,0.16),
                inset 0 0 0 1px rgba(255,255,255,0.08);
        }
        .sigtae-sidebar .sb-group-toggle[aria-expanded="true"] .sb-dot,
        .sigtae-sidebar .sb-sub-toggle[aria-expanded="true"] .sb-dot {
            background: var(--sigtae-cyan);
            box-shadow: 0 0 0 3px rgba(74,159,184,0.20);
            opacity: 1;
        }
        .sigtae-sidebar .sb-caret { margin-left: auto; font-size: .9rem; opacity: .75; transition: transform .15s ease; }
        .sigtae-sidebar .sb-group-toggle[aria-expanded="true"] .sb-caret,
        .sigtae-sidebar .sb-sub-toggle[aria-expanded="true"] .sb-caret { transform: rotate(180deg); opacity: .95; }
        .sigtae-sidebar .sb-collapse { padding-left: .25rem; }
        .sigtae-sidebar .sb-link.sb-leaf { padding-left: 1.9rem; }
        .sigtae-sidebar .sb-link.disabled,
        .sigtae-sidebar .sb-link[aria-disabled="true"] { opacity: .55; pointer-events: none; }
        .sigtae-sidebar .sb-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(255,255,255,0.65);
            flex: 0 0 8px;
            margin-left: 6px;
        }
        .sigtae-sidebar .sb-sub-toggle .sb-dot { opacity: .8; width: 6px; height: 6px; flex-basis: 6px; }
        .sigtae-sidebar .sb-link.sb-leaf .sb-dot { opacity: .75; width: 5px; height: 5px; flex-basis: 5px; margin-left: 10px; }
        .sigtae-sidebar .sb-group-toggle span,
        .sigtae-sidebar .sb-sub-toggle span,
        .sigtae-sidebar .sb-link span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sigtae-sidebar .sb-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .55rem 1rem;
            color: #cfd9e3;
            font-size: .93rem;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s, border-color .15s;
            white-space: normal;
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
            /* Selección tipo "pill" (solo cambia el activo) */
            position: relative;
            background: rgba(255,255,255,0.12);
            color: #fff;
            font-weight: 600;
            border-left-color: transparent;
            border-radius: 999px;
            margin: 0.15rem 0.6rem;
            padding-left: calc(1rem - 0.2rem);
            box-shadow:
                0 6px 16px rgba(0,0,0,0.18),
                inset 0 0 0 1px rgba(255,255,255,0.10);
        }
        .sigtae-sidebar .sb-link.active .sb-dot {
            background: var(--sigtae-cyan);
            box-shadow: 0 0 0 3px rgba(74,159,184,0.22);
            opacity: 1;
        }
        .sigtae-sidebar .sb-link.active::after {
            content: '';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255,255,255,0.75);
            opacity: 0.85;
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
        body.sb-collapsed .sigtae-sidebar .sb-link span:not(.sb-dot),
        body.sb-collapsed .sigtae-sidebar .sb-section,
        body.sb-collapsed .sigtae-sidebar .sb-group-toggle span:not(.sb-dot),
        body.sb-collapsed .sigtae-sidebar .sb-sub-toggle span:not(.sb-dot),
        body.sb-collapsed .sigtae-sidebar .sb-caret,
        body.sb-collapsed .sigtae-sidebar .sb-collapse,
        body.sb-collapsed .sigtae-sidebar .sb-foot { display: none; }
        body.sb-collapsed .sigtae-sidebar .sb-group-toggle,
        body.sb-collapsed .sigtae-sidebar .sb-sub-toggle { justify-content: center; padding-left: 0; padding-right: 0; }
        body.sb-collapsed .sigtae-sidebar .sb-dot { margin-left: 0; }
        body.sb-collapsed .sigtae-sidebar .sb-link { justify-content: center; padding-left: 0; padding-right: 0; }
        body.sb-collapsed .sigtae-main { margin-left: var(--sb-width-collapsed); width: calc(100% - var(--sb-width-collapsed)); }
        body.sb-collapsed .sigtae-sidebar .sb-link.active {
            margin: 0.2rem 0.5rem;
            padding-left: 0;
            padding-right: 0;
            border-radius: 14px;
        }
        body.sb-collapsed .sigtae-sidebar .sb-link.active::after { display: none; }

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
            width: calc(100% - var(--sb-width));
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

        /* Clickable */
        .sigtae-clickable { cursor: pointer; }
        .sigtae-clickable:hover { outline: 2px solid rgba(74,159,184,0.25); outline-offset: 2px; }

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
            <?php foreach ($navTree as $group): ?>
                <?php if (isset($group['visible']) && !$group['visible']) continue; ?>
                <?php $groupOpen = sigtaeNavTreeHasActive((array)$group, $currentScript); ?>
                <div class="sb-group">
                    <button class="sb-group-toggle" type="button"
                            data-sigtae-toggle="collapse"
                            data-sigtae-target="#sbgrp-<?= htmlspecialchars($group['id'] ?? '') ?>"
                            aria-expanded="<?= $groupOpen ? 'true' : 'false' ?>"
                            title="<?= htmlspecialchars($group['label'] ?? '') ?>">
                        <span class="sb-dot" aria-hidden="true"></span>
                        <span><?= htmlspecialchars($group['label'] ?? '') ?></span>
                        <i class="bi bi-chevron-down sb-caret"></i>
                    </button>
                    <div class="collapse sb-collapse <?= $groupOpen ? 'show' : '' ?>" id="sbgrp-<?= htmlspecialchars($group['id'] ?? '') ?>">
                        <?php foreach ((array)($group['children'] ?? []) as $sub): ?>
                            <?php if (isset($sub['visible']) && !$sub['visible']) continue; ?>
                            <?php $subOpen = sigtaeNavTreeHasActive((array)$sub, $currentScript); ?>
                            <div class="sb-sub">
                                <button class="sb-sub-toggle" type="button"
                                        data-sigtae-toggle="collapse"
                                        data-sigtae-target="#sbsub-<?= htmlspecialchars($group['id'] ?? '') ?>-<?= htmlspecialchars($sub['id'] ?? '') ?>"
                                        aria-expanded="<?= $subOpen ? 'true' : 'false' ?>"
                                        title="<?= htmlspecialchars($sub['label'] ?? '') ?>">
                                    <span class="sb-dot" aria-hidden="true"></span>
                                    <span><?= htmlspecialchars($sub['label'] ?? '') ?></span>
                                    <i class="bi bi-chevron-down sb-caret"></i>
                                </button>
                                <div class="collapse sb-collapse <?= $subOpen ? 'show' : '' ?>" id="sbsub-<?= htmlspecialchars($group['id'] ?? '') ?>-<?= htmlspecialchars($sub['id'] ?? '') ?>">
                                    <?php
                                    $leafs = array_filter((array)($sub['children'] ?? []), function ($it) {
                                        return !isset($it['visible']) || $it['visible'];
                                    });
                                    ?>
                                    <?php foreach ($leafs as $it): ?>
                                        <?php
                                        $disabled = !empty($it['disabled']);
                                        $href = $disabled ? '#' : ($basePath . ($it['url'] ?? '#'));
                                        ?>
                                        <a class="sb-link sb-leaf <?= sigtaeNavIsActive($it, $currentScript) ? 'active' : '' ?> <?= $disabled ? 'disabled' : '' ?>"
                                           href="<?= htmlspecialchars($href) ?>"
                                           title="<?= htmlspecialchars($it['label'] ?? '') ?>"
                                           aria-disabled="<?= $disabled ? 'true' : 'false' ?>"
                                           data-sigtae-match="<?= htmlspecialchars(json_encode(array_values((array)($it['match'] ?? []))), ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="sb-dot" aria-hidden="true"></span>
                                            <span><?= htmlspecialchars($it['label'] ?? '') ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>
        <div class="sb-foot">
            <div>SIGTAE · v1.0</div>
            <div>Laboratorio de Medición</div>
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

    <!-- Modal global SIGTAE (para cards/gráficas interactivas) -->
    <div class="modal fade" id="sigtaeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sigtaeModalTitle">Detalle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="sigtaeModalBody"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        window.SIGTAE_BASE_PATH = <?= json_encode($basePath ?? '') ?>;

        // Sidebar toggle con persistencia
        (function() {
            const body = document.body;
            const KEY = 'sigtae_sb_collapsed';
            if (window.innerWidth >= 992 && localStorage.getItem(KEY) === '1') {
                body.classList.add('sb-collapsed');
            }
            function autoSidebarWidth() {
                // Evitar en móvil y en modo colapsado
                if (window.innerWidth < 992) return;
                if (body.classList.contains('sb-collapsed')) return;
                const sb = document.querySelector('.sigtae-sidebar');
                if (!sb) return;
                const labels = sb.querySelectorAll('.sb-group-toggle span:not(.sb-dot), .sb-sub-toggle span:not(.sb-dot), a.sb-link span:not(.sb-dot)');
                let max = 0;
                labels.forEach(function(el) {
                    max = Math.max(max, el.scrollWidth || 0);
                });
                // 8 (dot) + gaps/paddings/caret aprox; cap para no ocupar demasiado
                const desired = Math.min(340, Math.max(240, max + 120));
                document.documentElement.style.setProperty('--sb-width', desired + 'px');
            }
            window.sigtaeAutoSidebarWidth = autoSidebarWidth;
            const btn = document.getElementById('sbToggle');
            if (btn) {
                btn.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        body.classList.toggle('sb-open');
                    } else {
                        body.classList.toggle('sb-collapsed');
                        localStorage.setItem(KEY, body.classList.contains('sb-collapsed') ? '1' : '0');
                        // si vuelve a expandirse, recalcula ancho
                        if (!body.classList.contains('sb-collapsed')) autoSidebarWidth();
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
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', autoSidebarWidth);
            } else {
                autoSidebarWidth();
            }
            window.addEventListener('resize', function() {
                // micro-debounce
                clearTimeout(window.__sigtaeSbResizeT);
                window.__sigtaeSbResizeT = setTimeout(autoSidebarWidth, 120);
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

        // Modal helpers
        (function() {
            function ensureModal() {
                const el = document.getElementById('sigtaeModal');
                if (!el || !window.bootstrap) return null;
                return bootstrap.Modal.getOrCreateInstance(el);
            }
            window.sigtaeOpenModal = function(title, html) {
                const m = ensureModal();
                if (!m) return;
                document.getElementById('sigtaeModalTitle').textContent = title || 'Detalle';
                document.getElementById('sigtaeModalBody').innerHTML = html || '';
                m.show();
                if (window.sigtaeInitTooltips) window.sigtaeInitTooltips();
            };
            window.sigtaeOpenModalLoading = function(title) {
                window.sigtaeOpenModal(title, '<div class="text-center text-muted p-4"><div class="spinner-border" role="status"></div><div class="small mt-2">Cargando…</div></div>');
            };
            window.sigtaeFetchJson = async function(url) {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
                const data = await res.json();
                if (!res.ok || !data || data.ok === false) {
                    throw new Error((data && data.message) ? data.message : ('HTTP ' + res.status));
                }
                return data;
            };

            // Click handler para cards KPI
            function bindClickable() {
                document.querySelectorAll('[data-sigtae-onclick]').forEach(function(el) {
                    if (el.__sigtaeBound) return;
                    el.__sigtaeBound = true;
                    el.addEventListener('click', function() {
                        const expr = el.getAttribute('data-sigtae-onclick');
                        if (!expr) return;
                        try { (new Function(expr))(); } catch (e) { console.error(e); }
                    });
                    el.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
                    });
                });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindClickable);
            else bindClickable();
            window.sigtaeBindClickable = bindClickable;
        })();

        // Dashboard modals (global, disponible aun con PJAX)
        (function() {
            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, function(c) {
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]) || c;
                });
            }
            function renderTasksTable(items) {
                const basePath = window.SIGTAE_BASE_PATH || '';
                let html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">'
                    + '<thead class="table-light"><tr><th>Folio</th><th>Título</th><th>Estado</th><th>Límite</th><th>Prioridad</th><th></th></tr></thead><tbody>';
                for (const t of items) {
                    html += '<tr>'
                        + '<td class="fw-semibold">' + escapeHtml(t.folio) + '</td>'
                        + '<td>' + escapeHtml(t.titulo) + '</td>'
                        + '<td class="small">' + escapeHtml(t.estado) + '</td>'
                        + '<td class="small text-muted">' + escapeHtml(t.fecha_limite) + '</td>'
                        + '<td class="small">' + escapeHtml(t.prioridad) + '</td>'
                        + '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="' + basePath + '/tarea.php?id=' + encodeURIComponent(t.id) + '"><i class="bi bi-eye"></i></a></td>'
                        + '</tr>';
                }
                html += '</tbody></table></div>';
                return html;
            }
            window.sigtaeDashboardOpenEstado = async function(estado) {
                const mapa = {
                    asignada:'Asignadas',
                    en_proceso:'En proceso',
                    vencida:'Atendidas fuera de tiempo',
                    incumplimiento:'Incumplimiento',
                    atendida:'Atendidas dentro de tiempo',
                    cancelada:'Canceladas'
                };
                const title = 'Tareas — ' + (mapa[estado] || estado);
                window.sigtaeOpenModalLoading(title);
                try {
                    const basePath = window.SIGTAE_BASE_PATH || '';
                    const data = await window.sigtaeFetchJson(basePath + '/api/tareas.php?action=by_estado&estado=' + encodeURIComponent(estado));
                    const items = data.items || [];
                    window.sigtaeOpenModal(title, items.length ? renderTasksTable(items) : '<div class="text-muted small">No hay tareas para este filtro.</div>');
                } catch (e) {
                    window.sigtaeOpenModal(title, '<div class="alert alert-danger mb-0">No se pudo cargar el detalle. ' + escapeHtml(e.message) + '</div>');
                }
            };
            window.sigtaeDashboardOpenEstados = async function(estados, title) {
                const t = title || 'Tareas';
                window.sigtaeOpenModalLoading(t);
                try {
                    const basePath = window.SIGTAE_BASE_PATH || '';
                    const data = await window.sigtaeFetchJson(basePath + '/api/tareas.php?action=by_estados&estados=' + encodeURIComponent((estados || []).join(',')));
                    const items = data.items || [];
                    window.sigtaeOpenModal(t, items.length ? renderTasksTable(items) : '<div class="text-muted small">No hay tareas para este filtro.</div>');
                } catch (e) {
                    window.sigtaeOpenModal(t, '<div class="alert alert-danger mb-0">No se pudo cargar el detalle. ' + escapeHtml(e.message) + '</div>');
                }
            };
            window.sigtaeDashboardOpenPendientesEvaluacion = async function() {
                const title = 'Tareas — Pendientes de evaluación';
                window.sigtaeOpenModalLoading(title);
                try {
                    const basePath = window.SIGTAE_BASE_PATH || '';
                    const data = await window.sigtaeFetchJson(basePath + '/api/tareas.php?action=pendiente_evaluacion');
                    const items = data.items || [];
                    window.sigtaeOpenModal(title, items.length ? renderTasksTable(items) : '<div class="text-muted small">No hay tareas para este filtro.</div>');
                } catch (e) {
                    window.sigtaeOpenModal(title, '<div class="alert alert-danger mb-0">No se pudo cargar el detalle. ' + escapeHtml(e.message) + '</div>');
                }
            };
            window.sigtaeDashboardOpenPrioridad = async function(prioridad) {
                const mapa = { alta:'Alta', media:'Media', baja:'Baja' };
                const title = 'Tareas — Prioridad ' + (mapa[prioridad] || prioridad);
                window.sigtaeOpenModalLoading(title);
                try {
                    const basePath = window.SIGTAE_BASE_PATH || '';
                    const data = await window.sigtaeFetchJson(basePath + '/api/tareas.php?action=by_prioridad&prioridad=' + encodeURIComponent(prioridad));
                    const items = data.items || [];
                    window.sigtaeOpenModal(title, items.length ? renderTasksTable(items) : '<div class="text-muted small">No hay tareas para este filtro.</div>');
                } catch (e) {
                    window.sigtaeOpenModal(title, '<div class="alert alert-danger mb-0">No se pudo cargar el detalle. ' + escapeHtml(e.message) + '</div>');
                }
            };
            window.sigtaeDashboardOpenOficina = async function(oficinaId, oficinaNombre) {
                const title = 'Tareas — ' + (oficinaNombre || oficinaId || 'Oficina');
                window.sigtaeOpenModalLoading(title);
                try {
                    const basePath = window.SIGTAE_BASE_PATH || '';
                    const data = await window.sigtaeFetchJson(basePath + '/api/tareas.php?action=by_oficina&oficina_id=' + encodeURIComponent(oficinaId));
                    const items = data.items || [];
                    window.sigtaeOpenModal(title, items.length ? renderTasksTable(items) : '<div class="text-muted small">No hay tareas para este filtro.</div>');
                } catch (e) {
                    window.sigtaeOpenModal(title, '<div class="alert alert-danger mb-0">No se pudo cargar el detalle. ' + escapeHtml(e.message) + '</div>');
                }
            };

            window.sigtaeDashboardOpenResponsable = async function(responsableId, nombre) {
                const title = 'Tareas — ' + (nombre || 'Responsable');
                window.sigtaeOpenModalLoading(title);
                try {
                    const basePath = window.SIGTAE_BASE_PATH || '';
                    const data = await window.sigtaeFetchJson(basePath + '/api/tareas.php?action=by_responsable&responsable_id=' + encodeURIComponent(responsableId));
                    const items = data.items || [];
                    window.sigtaeOpenModal(title, items.length ? renderTasksTable(items) : '<div class="text-muted small">No hay tareas para este filtro.</div>');
                } catch (e) {
                    window.sigtaeOpenModal(title, '<div class="alert alert-danger mb-0">No se pudo cargar el detalle. ' + escapeHtml(e.message) + '</div>');
                }
            };
        })();

        // Programa de Trabajo: modal de evidencias (global, funciona con PJAX)
        (function() {
            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, function(c) {
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]) || c;
                });
            }

            function getEvidenceMap() {
                const el = document.querySelector('.sigtae-content #evidenciasData');
                if (!el) return {};
                try { return JSON.parse(el.textContent || '{}') || {}; } catch (e) { return {}; }
            }

            function renderEvidenceList(map, actividadId, fecha) {
                const items = (((map || {})[actividadId] || {})[fecha] || []);
                const tb = document.querySelector('.sigtae-content #evList');
                const cnt = document.querySelector('.sigtae-content #evCount');
                if (cnt) cnt.textContent = items.length + ' archivo(s)';
                if (!tb) return;
                tb.innerHTML = '';
                for (const ev of items) {
                    const tr = document.createElement('tr');
                    const fileLabel = escapeHtml(ev.nombre_original || ev.nombre_archivo || '');
                    const comment = escapeHtml(ev.comentario || '');
                    const usr = escapeHtml(ev.usuario_carga || '');
                    const fec = escapeHtml(ev.fecha_carga || ev.created_at || '');
                    const link = (window.SIGTAE_BASE_PATH||'') + '/programa-evidencia.php?id=' + encodeURIComponent(ev.id || '');
                    tr.innerHTML =
                        '<td class="small">' + fileLabel + '</td>'
                        + '<td class="small text-muted">' + comment + '</td>'
                        + '<td class="small">' + usr + '</td>'
                        + '<td class="small text-muted">' + fec + '</td>'
                        + '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="' + link + '" target="_blank" rel="noopener" title="Descargar"><i class="bi bi-download"></i></a></td>';
                    tb.appendChild(tr);
                }
            }

            function wireDropzone() {
                const drop = document.querySelector('.sigtae-content #ptEvDrop');
                const files = document.querySelector('.sigtae-content #ptEvFiles');
                if (!drop || !files) return;
                if (drop.__wired) return;
                drop.__wired = true;
                drop.addEventListener('click', function() { files.click(); });
                drop.addEventListener('dragover', function(ev) { ev.preventDefault(); drop.classList.add('border-primary'); });
                drop.addEventListener('dragleave', function() { drop.classList.remove('border-primary'); });
                drop.addEventListener('drop', function(ev) {
                    ev.preventDefault();
                    drop.classList.remove('border-primary');
                    if (ev.dataTransfer && ev.dataTransfer.files && ev.dataTransfer.files.length) {
                        files.files = ev.dataTransfer.files;
                    }
                });
            }

            document.addEventListener('show.bs.modal', function(e) {
                const modalEl = e && e.target ? e.target : null;
                if (!modalEl || modalEl.id !== 'modalEvidencias') return;
                const btn = e.relatedTarget;
                if (!btn) return;
                const programaId = btn.getAttribute('data-programa-id') || '';
                const actividadId = btn.getAttribute('data-actividad-id') || '';
                const fecha = btn.getAttribute('data-fecha') || '';

                const title = document.querySelector('.sigtae-content #modalEvidenciasTitle');
                const meta = document.querySelector('.sigtae-content #modalEvidenciasMeta');
                if (title) title.textContent = 'Evidencias — ' + fecha;
                if (meta) meta.textContent = 'Actividad: ' + actividadId + ' · Programa: ' + programaId;

                const pid = document.querySelector('.sigtae-content #evProgramaId');
                const aid = document.querySelector('.sigtae-content #evActividadId');
                const fe = document.querySelector('.sigtae-content #evFecha');
                if (pid) pid.value = programaId;
                if (aid) aid.value = actividadId;
                if (fe) fe.value = fecha;

                renderEvidenceList(getEvidenceMap(), actividadId, fecha);
            });

            document.addEventListener('shown.bs.modal', function(e) {
                const modalEl = e && e.target ? e.target : null;
                if (!modalEl || modalEl.id !== 'modalEvidencias') return;
                wireDropzone();
            });

            window.addEventListener('sigtae:pageLoaded', function() {
                wireDropzone();
            });
        })();

        // Navegación tipo PJAX (sidebar) para evitar recargas completas
        (function() {
            const SEL_CONTENT = '.sigtae-content';
            const SEL_BREAD = '.tb-breadcrumb';
            function pathBasename(pathname) {
                const p = (pathname || '').replace(/\/+/g, '/');
                const parts = p.split('/').filter(Boolean);
                return parts.length ? parts[parts.length - 1] : '';
            }
            function expandParentsForActiveLink(activeLink) {
                if (!activeLink || !window.bootstrap || !bootstrap.Collapse) return;
                const collapses = [];
                let el = activeLink.parentElement;
                while (el) {
                    if (el.classList && el.classList.contains('collapse')) collapses.push(el);
                    el = el.parentElement;
                }
                collapses.reverse().forEach(function(c) {
                    try { bootstrap.Collapse.getOrCreateInstance(c, { toggle: false }).show(); } catch (_) {}
                    const id = c.getAttribute('id');
                    if (!id) return;
                    document.querySelectorAll('[data-sigtae-target="#' + CSS.escape(id) + '"]').forEach(function(btn) {
                        btn.setAttribute('aria-expanded', 'true');
                    });
                });
            }
            function updateSidebarActiveForUrl(href) {
                const u = new URL(href, window.location.href);
                const currentScript = pathBasename(u.pathname) || '';
                const links = document.querySelectorAll('.sigtae-sidebar a.sb-link');
                let bestEl = null;
                let bestPrio = -1;
                let bestHrefLen = -1;
                links.forEach(function(a) {
                    a.classList.remove('active');
                    let matchers = [];
                    const raw = a.getAttribute('data-sigtae-match');
                    if (raw) {
                        try { matchers = JSON.parse(raw); } catch (_) { matchers = []; }
                    }
                    if (!Array.isArray(matchers)) matchers = [];
                    if (matchers.indexOf(currentScript) === -1) return;
                    const prio = matchers.length;
                    const hrefLen = (a.getAttribute('href') || '').length;
                    if (prio > bestPrio || (prio === bestPrio && hrefLen > bestHrefLen)) {
                        bestEl = a;
                        bestPrio = prio;
                        bestHrefLen = hrefLen;
                    }
                });
                if (bestEl) {
                    bestEl.classList.add('active');
                    expandParentsForActiveLink(bestEl);
                }
            }
            window.sigtaeUpdateSidebarActive = updateSidebarActiveForUrl;

            // Sidebar colapsables: click + persistencia (desktop)
            (function initSidebarCollapsibles() {
                const KEY = 'sigtae_sb_open_groups_v1';
                function loadState() {
                    try { return JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch { return {}; }
                }
                function saveState(state) {
                    try { localStorage.setItem(KEY, JSON.stringify(state || {})); } catch (_) {}
                }
                function syncExpanded(btn, isOpen) {
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }
                function applyPersisted() {
                    if (!window.bootstrap || !bootstrap.Collapse) return;
                    const state = loadState();
                    // Solo abrir automáticamente los contenedores que formen parte de la ruta del enlace activo.
                    // Esto evita que queden desplegados grupos de una sesión anterior en páginas no relacionadas.
                    const activeLink = document.querySelector('.sigtae-sidebar a.sb-link.active');
                    const activeCollapseIds = new Set();
                    if (activeLink) {
                        let el = activeLink.parentElement;
                        while (el) {
                            if (el.classList && el.classList.contains('collapse')) {
                                const id = el.getAttribute('id');
                                if (id) activeCollapseIds.add(id);
                            }
                            el = el.parentElement;
                        }
                    }
                    document.querySelectorAll('[data-sigtae-toggle="collapse"]').forEach(function(btn) {
                        const target = btn.getAttribute('data-sigtae-target');
                        if (!target) return;
                        const id = target.replace(/^#/, '');
                        const c = document.getElementById(id);
                        if (!c) return;
                        if (state[id] === true && activeCollapseIds.has(id)) {
                            try { bootstrap.Collapse.getOrCreateInstance(c, { toggle: false }).show(); } catch (_) {}
                            syncExpanded(btn, true);
                        }
                    });
                }
                function bind() {
                    document.querySelectorAll('[data-sigtae-toggle="collapse"]').forEach(function(btn) {
                        if (btn.__sigtaeBound) return;
                        btn.__sigtaeBound = true;
                        btn.addEventListener('click', function(e) {
                            if (!window.bootstrap || !bootstrap.Collapse) return;
                            // cuando el sidebar está colapsado, no expandir (solo iconos)
                            if (document.body.classList.contains('sb-collapsed')) return;
                            const target = btn.getAttribute('data-sigtae-target');
                            if (!target) return;
                            const id = target.replace(/^#/, '');
                            const c = document.getElementById(id);
                            if (!c) return;
                            e.preventDefault();
                            const inst = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
                            const willOpen = !c.classList.contains('show');
                            if (willOpen) {
                                function hideCollapse(el) {
                                    if (!el) return;
                                    const oid = el.getAttribute('id');
                                    try { bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide(); } catch (_) {}
                                    if (oid) {
                                        document.querySelectorAll('[data-sigtae-target="#' + CSS.escape(oid) + '"]').forEach(function(b) {
                                            syncExpanded(b, false);
                                        });
                                        if (window.innerWidth >= 992) {
                                            const st = loadState();
                                            st[oid] = false;
                                            saveState(st);
                                        }
                                    }
                                }
                                // Acordeón: al abrir, cierra otros del mismo nivel
                                const isGroup = id.startsWith('sbgrp-');
                                const isSub = id.startsWith('sbsub-');
                                if (isGroup) {
                                    // Al cambiar de grupo principal, cierra también TODOS los submenús abiertos
                                    document.querySelectorAll('.sigtae-sidebar .collapse.show[id^="sbsub-"]').forEach(function(openSub) {
                                        hideCollapse(openSub);
                                    });
                                    document.querySelectorAll('.sigtae-sidebar .collapse.show[id^="sbgrp-"]').forEach(function(other) {
                                        if (other === c) return;
                                        hideCollapse(other);
                                    });
                                } else if (isSub) {
                                    // solo dentro del mismo grupo (prefijo sbsub-<grp>-)
                                    const parts = id.split('-');
                                    const grpId = parts.length >= 3 ? parts[1] : '';
                                    const prefix = grpId ? ('sbsub-' + grpId + '-') : 'sbsub-';
                                    document.querySelectorAll('.sigtae-sidebar .collapse.show[id^="' + prefix.replace(/"/g,'') + '"]').forEach(function(other) {
                                        if (other === c) return;
                                        hideCollapse(other);
                                    });
                                }
                                inst.show();
                            } else {
                                inst.hide();
                            }
                            syncExpanded(btn, willOpen);
                            // solo persistir en desktop
                            if (window.innerWidth >= 992) {
                                const st = loadState();
                                st[id] = willOpen;
                                saveState(st);
                            }
                        });
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() { bind(); applyPersisted(); });
                } else {
                    bind(); applyPersisted();
                }
                window.sigtaeBindSidebarCollapsibles = function() { bind(); applyPersisted(); };
            })();
            function sameOrigin(href) {
                try {
                    const u = new URL(href, window.location.href);
                    return u.origin === window.location.origin;
                } catch { return false; }
            }
            async function navigate(href, push) {
                const u = new URL(href, window.location.href);
                if (!sameOrigin(u.href)) { window.location.href = u.href; return; }
                // no pjax para logout / archivos
                if (u.pathname.endsWith('/logout.php')) { window.location.href = u.href; return; }

                const contentEl = document.querySelector(SEL_CONTENT);
                if (!contentEl) { window.location.href = u.href; return; }
                contentEl.style.opacity = '0.55';
                try {
                    const res = await fetch(u.href, { headers: { 'X-SIGTAE-PJAX': '1' } });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContent = doc.querySelector(SEL_CONTENT);
                    const newBread = doc.querySelector(SEL_BREAD);
                    if (!newContent) throw new Error('Respuesta PJAX inválida.');
                    // Reemplaza contenido
                    contentEl.innerHTML = newContent.innerHTML;
                    // Ejecuta scripts inline dentro del contenido (necesario para Chart.js/DataTables)
                    (function executeScripts(root) {
                        const scripts = Array.from(root.querySelectorAll('script'));
                        for (const s of scripts) {
                            const ns = document.createElement('script');
                            // Copia atributos útiles
                            for (const a of Array.from(s.attributes || [])) ns.setAttribute(a.name, a.value);
                            if (s.src) {
                                ns.src = s.src;
                                ns.async = false;
                            } else {
                                ns.text = s.textContent || '';
                            }
                            s.parentNode && s.parentNode.replaceChild(ns, s);
                        }
                    })(contentEl);
                    if (newBread && document.querySelector(SEL_BREAD)) {
                        document.querySelector(SEL_BREAD).innerHTML = newBread.innerHTML;
                    }
                    document.title = doc.title || document.title;
                    if (push) history.pushState({ href: u.href }, '', u.href);
                    // Reinits
                    if (window.sigtaeInitTooltips) window.sigtaeInitTooltips();
                    if (window.sigtaeBindClickable) window.sigtaeBindClickable();
                    if (window.sigtaeBindSidebarCollapsibles) window.sigtaeBindSidebarCollapsibles();
                    if (window.sigtaeAutoSidebarWidth) window.sigtaeAutoSidebarWidth();
                    updateSidebarActiveForUrl(u.href);
                    // Evento para que cada vista (si necesita) se reinicialice
                    window.dispatchEvent(new CustomEvent('sigtae:pageLoaded', { detail: { href: u.href } }));
                } catch (e) {
                    console.error(e);
                    window.location.href = u.href;
                } finally {
                    contentEl.style.opacity = '';
                }
            }
            document.addEventListener('click', function(e) {
                const a = e.target.closest('a');
                if (!a) return;
                if (a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const href = a.getAttribute('href');
                if (!href) return;
                if (href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
                // Sólo intercepta navegación del sidebar/topbar
                if (!a.closest('.sigtae-sidebar') && !a.closest('.sigtae-topbar')) return;
                // Para páginas que cargan JS/CSS por vista (módulos embebidos), forzar recarga completa.
                // SIG (Metrología) necesita reinicialización completa.
                if (href.indexOf('/metrologia-sig.php') !== -1) return;
                e.preventDefault();
                navigate(href, true);
            });
            window.addEventListener('popstate', function(e) {
                const href = (e.state && e.state.href) ? e.state.href : window.location.href;
                navigate(href, false);
            });
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.sigtaeBindSidebarCollapsibles) window.sigtaeBindSidebarCollapsibles();
                    updateSidebarActiveForUrl(window.location.href);
                });
            } else {
                if (window.sigtaeBindSidebarCollapsibles) window.sigtaeBindSidebarCollapsibles();
                updateSidebarActiveForUrl(window.location.href);
            }
        })();
    </script>
    <?php if (!empty($inlineScript)): ?>
        <script><?= $inlineScript ?></script>
    <?php endif; ?>
    <?php if (!empty($extraScripts)): foreach ((array)$extraScripts as $s): ?>
        <script src="<?= htmlspecialchars($s) ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>
