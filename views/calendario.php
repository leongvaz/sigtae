<?php
$eventos = $eventos ?? [];
$mes = $mes ?? date('n');
$anio = $anio ?? date('Y');
$mesAnterior = $mes <= 1 ? 12 : $mes - 1;
$anioAnterior = $mes <= 1 ? $anio - 1 : $anio;
$mesSiguiente = $mes >= 12 ? 1 : $mes + 1;
$anioSiguiente = $mes >= 12 ? $anio + 1 : $anio;
$nombresMes = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$diasEnMes = (int)date('t', strtotime("{$anio}-{$mes}-01"));
$primerDia = (int)date('w', strtotime("{$anio}-{$mes}-01"));
$eventosPorFecha = [];
foreach ($eventos as $e) {
    $eventosPorFecha[$e['fecha']][] = $e;
}
function estadoColor($e) {
    $m = ['atendida'=>'success','vencida'=>'warning','incumplimiento'=>'danger','en_proceso'=>'info','asignada'=>'primary'];
    return $m[$e] ?? 'secondary';
}
$totalCeldas = (int)ceil(($primerDia + $diasEnMes) / 7) * 7;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold">Calendario</h1>
        <p class="text-muted mb-0">Tareas por fecha límite</p>
    </div>
    <nav>
        <a href="?mes=<?= $mesAnterior ?>&anio=<?= $anioAnterior ?>" class="btn btn-outline-secondary btn-sm">← Anterior</a>
        <span class="mx-3 fw-bold"><?= $nombresMes[$mes] ?> <?= $anio ?></span>
        <a href="?mes=<?= $mesSiguiente ?>&anio=<?= $anioSiguiente ?>" class="btn btn-outline-secondary btn-sm">Siguiente →</a>
    </nav>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 calendario-mes">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width:14%">Domingo</th>
                    <th class="text-center" style="width:14%">Lunes</th>
                    <th class="text-center" style="width:14%">Martes</th>
                    <th class="text-center" style="width:14%">Miércoles</th>
                    <th class="text-center" style="width:14%">Jueves</th>
                    <th class="text-center" style="width:14%">Viernes</th>
                    <th class="text-center" style="width:14%">Sábado</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($celda = 0; $celda < $totalCeldas; $celda++): ?>
                    <?php if ($celda % 7 === 0): ?><tr><?php endif; ?>
                    <?php
                    $dia = $celda - $primerDia + 1;
                    $fecha = ($dia >= 1 && $dia <= $diasEnMes) ? sprintf('%04d-%02d-%02d', $anio, $mes, $dia) : null;
                    $eventosDia = $fecha ? ($eventosPorFecha[$fecha] ?? []) : [];
                    ?>
                    <td class="align-top p-2" style="min-height:100px; vertical-align:top">
                        <?php if ($dia >= 1 && $dia <= $diasEnMes): ?>
                            <div class="small fw-semibold text-muted"><?= $dia ?></div>
                            <?php foreach ($eventosDia as $ev): ?>
                                <a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($ev['id']) ?>" class="d-block small badge bg-<?= estadoColor($ev['estado']) ?> text-decoration-none mb-1" title="<?= htmlspecialchars($ev['titulo']) ?>">
                                    <?= htmlspecialchars($ev['folio']) ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <?php if ($celda % 7 === 6): ?></tr><?php endif; ?>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>
