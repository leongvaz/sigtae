<?php
$snapshot = $snapshot ?? null;
$universeGeneratedAt = $universeGeneratedAt ?? null;
$universeTotal = $universeTotal ?? null;

$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header(
    'IUSA — SINAMED',
    'Listas derivadas del último snapshot del script diario (prep SIGAMI, envío y reversión). El universo proviene de calcomCompleto.json.',
    $actions
);

$lists = is_array($snapshot) ? ($snapshot['lists_for_ui'] ?? []) : [];
$envOk = is_array($lists['enviados_hoy_con_lectura'] ?? null) ? $lists['enviados_hoy_con_lectura'] : [];
$prep = is_array($lists['proximos_dos_dias_prep_sigami_6'] ?? null) ? $lists['proximos_dos_dias_prep_sigami_6'] : [];
$rev = is_array($lists['cohorte_revertir_a_sigami_4_hoy'] ?? null) ? $lists['cohorte_revertir_a_sigami_4_hoy'] : [];

$sendDetails = is_array($snapshot) ? ($snapshot['send_sinamed_today']['details'] ?? []) : [];
$prepBlock = is_array($snapshot) ? ($snapshot['prep_sigami_4_to_6'] ?? []) : [];
$revBlock = is_array($snapshot) ? ($snapshot['revert_sigami_6_to_4'] ?? []) : [];

$genAt = is_array($snapshot) ? (string)($snapshot['generated_at'] ?? '') : '';
$dryRun = is_array($snapshot) && !empty($snapshot['dry_run']);
?>

<?php if (!$snapshot): ?>
    <div class="alert alert-warning mb-4">
        <i class="bi bi-info-circle me-1"></i>
        Aún no hay <code>storage/json/sinamed_automation/snapshot.json</code>.
        Ejecute el script Python <code>implementar/EnvioSinamed/sinamed_diario_automatico.py</code>
        con su archivo <code>config_sinamed_automatizado.local.json</code> (véase el ejemplo incluido).
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Última corrida</div>
                    <div class="fs-6 fw-semibold"><?= htmlspecialchars($genAt ?: '—') ?></div>
                    <?php if ($dryRun): ?>
                        <span class="badge text-bg-secondary mt-2">dry_run</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Prep SIGAMI (4→6), fecha envío +2 días</div>
                    <div class="fs-6 fw-semibold"><?= htmlspecialchars((string)($prepBlock['target_send_date'] ?? '—')) ?></div>
                    <div class="small text-muted"><?= (int)($prepBlock['count_calendar'] ?? 0) ?> medidor(es) · SQL rows <?= (int)($prepBlock['sql_rows_reported'] ?? 0) ?></div>
                    <?php if (!empty($prepBlock['sql_error'])): ?>
                        <div class="small text-danger mt-1"><?= htmlspecialchars((string)$prepBlock['sql_error']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Revertir SIGAMI (6→4), envío hace 2 días</div>
                    <div class="fs-6 fw-semibold"><?= htmlspecialchars((string)($revBlock['reference_send_date'] ?? '—')) ?></div>
                    <div class="small text-muted"><?= (int)($revBlock['count_calendar'] ?? 0) ?> medidor(es) · SQL rows <?= (int)($revBlock['sql_rows_reported'] ?? 0) ?></div>
                    <?php if (!empty($revBlock['sql_error'])): ?>
                        <div class="small text-danger mt-1"><?= htmlspecialchars((string)$revBlock['sql_error']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($snapshot['send_sinamed_today']['post_error'])): ?>
        <div class="alert alert-danger">
            <strong>Error envío SINAMED:</strong>
            <?= htmlspecialchars((string)$snapshot['send_sinamed_today']['post_error']) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-env-tab" data-bs-toggle="tab" data-bs-target="#tab-env" type="button" role="tab">
            Enviados hoy <?php if ($snapshot): ?><span class="badge text-bg-success ms-1"><?= count($envOk) ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-prep-tab" data-bs-toggle="tab" data-bs-target="#tab-prep" type="button" role="tab">
            Próximo envío (+2 días) <?php if ($snapshot): ?><span class="badge text-bg-primary ms-1"><?= count($prep) ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-rev-tab" data-bs-toggle="tab" data-bs-target="#tab-rev" type="button" role="tab">
            Revertir a 4 (hoy) <?php if ($snapshot): ?><span class="badge text-bg-warning ms-1"><?= count($rev) ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-uni-tab" data-bs-toggle="tab" data-bs-target="#tab-uni" type="button" role="tab">
            Universo
            <?php if ($universeTotal !== null): ?>
                <span class="badge text-bg-secondary ms-1"><?= (int)$universeTotal ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-env" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cloud-upload me-1"></i> Envío del día (detalle)</span>
                <span class="text-muted small"><?= $snapshot ? count($sendDetails) : 0 ?> filas</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$snapshot): ?>
                    <?php sigtae_empty_state('Sin datos.', 'bi-cloud-upload'); ?>
                <?php elseif (empty($sendDetails)): ?>
                    <?php sigtae_empty_state('No hay medidores programados para envío en la fecha de corrida o el snapshot no incluye detalle.', 'bi-cloud-upload'); ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Medidor</th>
                                    <th>Cuenta</th>
                                    <th>Ciclo</th>
                                    <th>Lectura enviada</th>
                                    <th>Recibido</th>
                                    <th>OK</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach ($sendDetails as $row): ?>
                                    <?php if (!is_array($row)) continue; ?>
                                    <tr class="<?= !empty($row['ok']) ? '' : 'table-warning' ?>">
                                        <td class="font-monospace"><?= htmlspecialchars((string)($row['medidor'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['cuenta'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['ciclo'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['delivered'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['received'] ?? '')) ?></td>
                                        <td><?= !empty($row['ok']) ? 'Sí' : 'No' ?></td>
                                        <td class="text-danger"><?= htmlspecialchars((string)($row['error'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <p class="small text-muted mt-2 mb-0">
            La columna «Lectura enviada» corresponde al valor enviado en el POST (cuando la consulta PostgreSQL y el callback respondieron correctamente).
        </p>
    </div>

    <div class="tab-pane fade" id="tab-prep" role="tabpanel">
        <div class="card">
            <div class="card-header"><i class="bi bi-arrow-right-circle me-1"></i> Cohorte prevista para SIGAMI 6 (envío en dos días según calcom)</div>
            <div class="card-body p-0">
                <?php if (!$snapshot): ?>
                    <?php sigtae_empty_state('Sin datos.', 'bi-arrow-right-circle'); ?>
                <?php elseif (empty($prep)): ?>
                    <?php sigtae_empty_state('Lista vacía para esta fecha.', 'bi-arrow-right-circle'); ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light small"><tr><th>Medidor</th><th>Cuenta</th><th>Ciclo</th></tr></thead>
                            <tbody class="small">
                                <?php foreach ($prep as $row): ?>
                                    <?php if (!is_array($row)) continue; ?>
                                    <tr>
                                        <td class="font-monospace"><?= htmlspecialchars((string)($row['medidor'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['cuenta'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['ciclo'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-rev" role="tabpanel">
        <div class="card">
            <div class="card-header"><i class="bi bi-arrow-counterclockwise me-1"></i> Cohorte a regresar a SIGAMI 4 (fecha de envío = hoy − 2)</div>
            <div class="card-body p-0">
                <?php if (!$snapshot): ?>
                    <?php sigtae_empty_state('Sin datos.', 'bi-arrow-counterclockwise'); ?>
                <?php elseif (empty($rev)): ?>
                    <?php sigtae_empty_state('Lista vacía para esta fecha.', 'bi-arrow-counterclockwise'); ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light small"><tr><th>Medidor</th><th>Cuenta</th><th>Ciclo</th></tr></thead>
                            <tbody class="small">
                                <?php foreach ($rev as $row): ?>
                                    <?php if (!is_array($row)) continue; ?>
                                    <tr>
                                        <td class="font-monospace"><?= htmlspecialchars((string)($row['medidor'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['cuenta'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['ciclo'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-uni" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-globe2 me-1"></i> Universo (calcomCompleto)</span>
                <?php if ($universeGeneratedAt): ?>
                    <span class="text-muted small">Generado: <?= htmlspecialchars($universeGeneratedAt) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($universeTotal === null || $universeTotal === 0): ?>
                    <?php sigtae_empty_state('Ejecute el script para generar universe_sinamed.json.', 'bi-globe2'); ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle w-100" id="tablaUniversoSinamed" style="width:100%">
                            <thead class="table-light small">
                                <tr><th>Medidor</th><th>Cuenta</th><th>Ciclo</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($universeTotal !== null && $universeTotal > 0): ?>
<script>
(function () {
    const base = window.SIGTAE_BASE_PATH || '';
    const langUrl = base + '/vendor/datatables/i18n/es-ES.json';
    const api = base + '/api/sinamed-automation.php?resource=universe';
    $('#tablaUniversoSinamed').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: api, type: 'GET' },
        pageLength: 50,
        order: [[0, 'asc']],
        columns: [
            { data: 0, title: 'Medidor', className: 'font-monospace' },
            { data: 1, title: 'Cuenta' },
            { data: 2, title: 'Ciclo' }
        ],
        language: { url: langUrl }
    });
})();
</script>
<?php endif; ?>
