<?php
/**
 * Helpers de UI reutilizables para las vistas de SIGTAE.
 * Se incluye una sola vez en las vistas (o desde layout si se prefiere).
 */

if (!function_exists('sigtae_page_header')) {
    /**
     * Encabezado de página: título grande + subtítulo + botonera opcional.
     * @param string $title
     * @param string $subtitle
     * @param string $actionsHtml HTML listo para insertar a la derecha.
     */
    function sigtae_page_header(string $title, string $subtitle = '', string $actionsHtml = ''): void
    {
        ?>
        <div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="page-sub"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($actionsHtml !== ''): ?>
                <div class="page-actions"><?= $actionsHtml ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('sigtae_kpi_card')) {
    /**
     * Tarjeta KPI con icono, label y valor.
     * @param array{label:string, value:int|string, icon?:string, color?:string} $kpi
     */
    function sigtae_kpi_card(array $kpi): void
    {
        $icon  = $kpi['icon']  ?? 'bi-graph-up';
        $color = $kpi['color'] ?? 'var(--sigtae-cyan)';
        $label = (string)($kpi['label'] ?? '');
        $value = (string)($kpi['value'] ?? '0');
        ?>
        <div class="card kpi-card h-100" style="border-left-color: <?= htmlspecialchars($color) ?>;">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label"><?= htmlspecialchars($label) ?></div>
                    <div class="kpi-value" style="color: <?= htmlspecialchars($color) ?>"><?= htmlspecialchars($value) ?></div>
                </div>
                <i class="bi <?= htmlspecialchars($icon) ?> kpi-icon" style="color: <?= htmlspecialchars($color) ?>"></i>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('sigtae_status_badge')) {
    /**
     * Badge de estado unificado.
     */
    function sigtae_status_badge(string $estado): string
    {
        $estado = trim($estado);
        if ($estado === '') return '';
        $label = str_replace('_', ' ', $estado);
        return '<span class="badge-estado badge-estado-' . htmlspecialchars($estado) . '">' . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('sigtae_prioridad_badge')) {
    function sigtae_prioridad_badge(string $prioridad): string
    {
        $prioridad = trim($prioridad);
        if ($prioridad === '') return '';
        return '<span class="badge-prioridad badge-prioridad-' . htmlspecialchars($prioridad) . '">' . htmlspecialchars($prioridad) . '</span>';
    }
}

if (!function_exists('sigtae_dias_pill')) {
    /**
     * Render de los días restantes de una tarea, con tooltip explicativo.
     */
    function sigtae_dias_pill(?int $dias, string $estado): string
    {
        if (in_array($estado, ['atendida', 'cancelada'], true)) {
            return '<span class="dias-pill neutral" data-bs-toggle="tooltip" title="Tarea ya presentada / atendida">—</span>';
        }
        if ($dias === null) {
            return '<span class="dias-pill neutral">-</span>';
        }
        $cls = 'ok';
        if ($dias < 0) $cls = 'danger';
        elseif ($dias <= 3) $cls = 'warn';
        return '<span class="dias-pill ' . $cls . '" data-bs-toggle="tooltip" title="Días restantes para la fecha límite">' . (int)$dias . '</span>';
    }
}

if (!function_exists('sigtae_info_icon')) {
    /**
     * Icono de info con tooltip (para ayuda contextual, p. ej. títulos de gráficas).
     */
    function sigtae_info_icon(string $texto): string
    {
        return '<i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="'
            . htmlspecialchars($texto, ENT_QUOTES) . '" style="cursor: help; font-size: .9rem;"></i>';
    }
}

if (!function_exists('sigtae_chart_card_open')) {
    /**
     * Abre una card de gráfica con tooltip en el título.
     */
    function sigtae_chart_card_open(string $titulo, string $tooltip = '', int $height = 220): void
    {
        ?>
        <div class="card chart-card h-100">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <span><?= htmlspecialchars($titulo) ?> <?= $tooltip !== '' ? sigtae_info_icon($tooltip) : '' ?></span>
            </div>
            <div class="card-body p-2">
                <div class="chart-wrap" style="height: <?= (int)$height ?>px;">
        <?php
    }
}

if (!function_exists('sigtae_chart_card_close')) {
    function sigtae_chart_card_close(): void
    {
        ?>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('sigtae_user_chip')) {
    /**
     * Chip con nombre y, opcionalmente, RPE/oficina.
     * @param array|null $user
     */
    function sigtae_user_chip(?array $user, bool $withRpe = true): string
    {
        if (!$user) return '<span class="text-muted">—</span>';
        $nombre = htmlspecialchars($user['nombre'] ?? '');
        $rpe = htmlspecialchars($user['rpe'] ?? '');
        $inicial = mb_strtoupper(mb_substr(trim((string)($user['nombre'] ?? '?')), 0, 1));
        $html = '<span class="d-inline-flex align-items-center gap-2">';
        $html .= '<span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:var(--sigtae-cyan-soft);color:var(--sigtae-petrol);font-weight:600;font-size:.75rem;">' . $inicial . '</span>';
        $html .= '<span>' . $nombre . ($withRpe && $rpe !== '' ? ' <span class="text-muted small">(' . $rpe . ')</span>' : '') . '</span>';
        $html .= '</span>';
        return $html;
    }
}

if (!function_exists('sigtae_empty_state')) {
    /**
     * Estado vacío consistente.
     */
    function sigtae_empty_state(string $mensaje, string $icon = 'bi-inbox'): void
    {
        ?>
        <div class="text-center p-5 text-muted">
            <i class="bi <?= htmlspecialchars($icon) ?>" style="font-size: 2.5rem; opacity:.4;"></i>
            <p class="mt-2 mb-0 small"><?= htmlspecialchars($mensaje) ?></p>
        </div>
        <?php
    }
}

if (!function_exists('sigtae_timeline')) {
    /**
     * Timeline horizontal de pasos: Asignada -> En proceso -> Presentada -> Evaluada.
     * @param array $task
     */
    function sigtae_timeline(array $task): string
    {
        $estado = (string)($task['estado'] ?? '');
        $tieneEvidencia = !empty($task['evidencias']);
        $evaluada = ($task['evaluacion'] ?? null) !== null && !empty($task['dictamen']);
        $cancelada = ($estado === 'cancelada');

        $steps = [
            ['label' => 'Asignada', 'icon' => 'bi-clipboard-plus'],
            ['label' => 'En proceso', 'icon' => 'bi-arrow-repeat'],
            ['label' => 'Presentada', 'icon' => 'bi-upload'],
            ['label' => 'Evaluada', 'icon' => 'bi-check2-circle'],
        ];
        $out = '<div class="sigtae-timeline">';
        $currentIdx = 0;
        if (in_array($estado, ['en_proceso', 'incumplimiento'], true)) $currentIdx = 1;
        if ($tieneEvidencia && !$evaluada) $currentIdx = 2;
        if ($evaluada || $estado === 'atendida') $currentIdx = 3;

        foreach ($steps as $i => $s) {
            $cls = 'tl-step';
            if ($cancelada) {
                if ($i === $currentIdx) $cls .= ' error';
            } else {
                if ($i < $currentIdx) $cls .= ' done';
                elseif ($i === $currentIdx) $cls .= ' current';
            }
            $out .= '<span class="' . $cls . '"><i class="bi ' . $s['icon'] . '"></i> ' . $s['label'] . '</span>';
            if ($i < count($steps) - 1) {
                $out .= '<i class="bi bi-chevron-right tl-arrow"></i>';
            }
        }
        if ($cancelada) {
            $out .= '<span class="tl-step error ms-auto"><i class="bi bi-x-circle"></i> Cancelada</span>';
        }
        $out .= '</div>';
        return $out;
    }
}
