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
$totalCeldas = (int)ceil(($primerDia + $diasEnMes) / 7) * 7;
$hoy = date('Y-m-d');

$navNav = '
<div class="d-flex align-items-center gap-2">
    <a href="?mes=' . $mesAnterior . '&anio=' . $anioAnterior . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
    <span class="fw-bold px-2" style="color: var(--sigtae-navy); min-width: 160px; text-align: center;">' . $nombresMes[$mes] . ' ' . $anio . '</span>
    <a href="?mes=' . $mesSiguiente . '&anio=' . $anioSiguiente . '" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
    <a href="?mes=' . date('n') . '&anio=' . date('Y') . '" class="btn btn-primary btn-sm ms-2">Hoy</a>
</div>';
sigtae_page_header('Calendario', 'Tareas por fecha límite', $navNav);
?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 calendario-mes">
            <thead class="table-light">
                <tr>
                    <?php foreach (['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'] as $d): ?>
                        <th class="text-center small" style="width:14.28%"><?= $d ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($celda = 0; $celda < $totalCeldas; $celda++): ?>
                    <?php if ($celda % 7 === 0): ?><tr><?php endif; ?>
                    <?php
                    $dia = $celda - $primerDia + 1;
                    $fecha = ($dia >= 1 && $dia <= $diasEnMes) ? sprintf('%04d-%02d-%02d', $anio, $mes, $dia) : null;
                    $eventosDia = $fecha ? ($eventosPorFecha[$fecha] ?? []) : [];
                    $esHoy = ($fecha === $hoy);
                    ?>
                    <td class="align-top p-2" style="height:110px; vertical-align:top; <?= $esHoy ? 'background: var(--sigtae-cyan-soft);' : '' ?>">
                        <?php if ($dia >= 1 && $dia <= $diasEnMes): ?>
                            <div class="small fw-semibold <?= $esHoy ? 'text-primary' : 'text-muted' ?>"><?= $dia ?></div>
                            <?php foreach ($eventosDia as $ev): ?>
                                <a href="<?= htmlspecialchars($basePath ?? '') ?>/tarea.php?id=<?= urlencode($ev['id']) ?>"
                                   class="d-block small text-decoration-none mb-1 p-1 rounded"
                                   style="font-size: .7rem; background: #f1f5f9; color: var(--sigtae-navy); border-left: 3px solid var(--sigtae-cyan); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                   data-bs-toggle="tooltip" title="<?= htmlspecialchars($ev['titulo']) ?>">
                                    <?= sigtae_status_badge((string)($ev['estado'] ?? '')) ?>
                                    <?= htmlspecialchars($ev['folio']) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <?php if ($celda % 7 === 6): ?></tr><?php endif; ?>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>
