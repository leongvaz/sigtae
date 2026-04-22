<?php
$withState = $withState ?? [];
$userRepo = $userRepo ?? null;

// Conteos por estado para los chips
$counts = ['total' => count($withState)];
foreach ($withState as $t) {
    $e = $t['estado'] ?? 'otro';
    $counts[$e] = ($counts[$e] ?? 0) + 1;
}
?>

<?php sigtae_page_header('Mis tareas', 'Tareas asignadas a usted'); ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small me-2"><i class="bi bi-funnel me-1"></i>Filtrar por estado:</span>
            <span class="sigtae-chip active" data-filter="">Todos <strong class="ms-1"><?= (int)$counts['total'] ?></strong></span>
            <?php
            $chips = [
                'asignada'       => ['Asignadas',       'bi-circle'],
                'en_proceso'     => ['En proceso',      'bi-arrow-repeat'],
                'incumplimiento' => ['Incumplimiento',  'bi-x-octagon'],
                'vencida'        => ['Vencidas',        'bi-exclamation-triangle'],
                'atendida'       => ['Atendidas',       'bi-check2-circle'],
                'cancelada'      => ['Canceladas',      'bi-slash-circle'],
            ];
            foreach ($chips as $key => [$label, $icon]):
                $n = $counts[$key] ?? 0;
                if ($n === 0) continue;
            ?>
                <span class="sigtae-chip" data-filter="<?= htmlspecialchars($key) ?>">
                    <i class="bi <?= $icon ?>"></i> <?= htmlspecialchars($label) ?>
                    <strong class="ms-1"><?= (int)$n ?></strong>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($withState)): ?>
            <?php sigtae_empty_state('No tiene tareas asignadas.', 'bi-clipboard-check'); ?>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaMisTareas">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Título</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Límite</th>
                        <th>Días</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withState as $t): ?>
                        <tr data-estado="<?= htmlspecialchars((string)($t['estado'] ?? '')) ?>">
                            <td><a class="fw-semibold" href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>"><?= htmlspecialchars($t['folio'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars(mb_substr($t['titulo'] ?? '', 0, 60)) ?></td>
                            <td><?= sigtae_prioridad_badge((string)($t['prioridad'] ?? '')) ?></td>
                            <td><?= sigtae_status_badge((string)($t['estado'] ?? '')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($t['fecha_limite'] ?? '—') ?></td>
                            <td><?= sigtae_dias_pill(isset($t['dias_restantes']) ? (int)$t['dias_restantes'] : null, (string)($t['estado'] ?? '')) ?></td>
                            <td class="text-end"><a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($t['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver
                            </a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($withState)): ?>
<script>
$(function() {
    const dt = $('#tablaMisTareas').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[4,'asc']],
        drawCallback: function() { if (window.sigtaeInitTooltips) window.sigtaeInitTooltips(); }
    });

    // Filtro por chips
    $(document).on('click', '.sigtae-chip[data-filter]', function() {
        $('.sigtae-chip[data-filter]').removeClass('active');
        $(this).addClass('active');
        const v = $(this).data('filter');
        $.fn.dataTable.ext.search = [];
        if (v) {
            $.fn.dataTable.ext.search.push(function(settings, row, idx) {
                const tr = dt.row(idx).node();
                return $(tr).attr('data-estado') === v;
            });
        }
        dt.draw();
    });
});
</script>
<?php endif; ?>
