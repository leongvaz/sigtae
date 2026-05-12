<?php
/** @var array|null $recepcion */
$recepcion = $recepcion ?? null;
$basePath = $basePath ?? '';

function met_es_fecha_larga(?string $ymd): string {
    $ymd = trim((string)$ymd);
    if ($ymd === '') return '';
    $ts = strtotime($ymd);
    if (!$ts) return $ymd;
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $dia = (int)date('w', $ts);
    $d = (int)date('d', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts);
    return ($dias[$dia] ?? '') . ', ' . $d . ' de ' . ($meses[$m] ?? '') . ' de ' . $y;
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$embedImg = function(array $paths): string {
    foreach ($paths as $p) {
        $p = (string)$p;
        if ($p === '' || !is_file($p)) continue;
        $bin = @file_get_contents($p);
        if ($bin === false || $bin === '') continue;
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : (($ext === 'webp') ? 'image/webp' : 'image/png');
        return 'data:' . $mime . ';base64,' . base64_encode($bin);
    }
    return '';
};

$logoGobMxCfe = $embedImg([
    __DIR__ . '/../../public/assets/metrologia/logo_gobmx_cfe.png',
    __DIR__ . '/../../public/assets/metrologia/logo_gobmx_cfe.jpg',
    'C:\\Users\\leonm\\.cursor\\projects\\c-Users-leonm-Documents-01-Desarrollos-PHP-Laboratorio-sigtae\\assets\\c__Users_leonm_AppData_Roaming_Cursor_User_workspaceStorage_2009b9266f7c27dd0b53bf9e8a4c4eae_images_image-e86a1d87-4685-4af6-a1da-b72cce8f18e7.png',
]);
$logoSIG = $embedImg([
    __DIR__ . '/../../public/assets/metrologia/logo_sig.png',
    __DIR__ . '/../../public/assets/metrologia/logo_sig.jpg',
    'C:\\Users\\leonm\\.cursor\\projects\\c-Users-leonm-Documents-01-Desarrollos-PHP-Laboratorio-sigtae\\assets\\c__Users_leonm_AppData_Roaming_Cursor_User_workspaceStorage_2009b9266f7c27dd0b53bf9e8a4c4eae_images_image-a93a0122-d0ec-4008-b7f1-4af8890855c5.png',
]);
$logoValle = $embedImg([
    __DIR__ . '/../../public/assets/metrologia/logo_valle_mexico_centro.png',
    __DIR__ . '/../../public/assets/metrologia/logo_valle_mexico_centro.jpg',
    'C:\\Users\\leonm\\.cursor\\projects\\c-Users-leonm-Documents-01-Desarrollos-PHP-Laboratorio-sigtae\\assets\\c__Users_leonm_AppData_Roaming_Cursor_User_workspaceStorage_2009b9266f7c27dd0b53bf9e8a4c4eae_images_image-b3ebd093-15ee-43f3-856f-3aae8e2664b7.png',
]);

$domicilio = 'CAMINO REAL A TOLUCA NO. 570, COL. EL CUERNITO, ALCALDÍA ÁLVARO OBREGÓN, CDMX, CP 01220';
$areaRecibe = 'LABORATORIO DE MEDICION';
$recepcionId = (string)($recepcion['id'] ?? '');
$folioRecepcion = (string)($recepcion['folio_recepcion'] ?? '');
$fechaRecepcion = (string)($recepcion['fecha_recepcion'] ?? '');
$motivo = (string)($recepcion['motivo_recepcion'] ?? '');
$recibeNombre = (string)($recepcion['recibe']['nombre'] ?? '');
$recibeRpe = (string)($recepcion['recibe']['rpe'] ?? '');
$recibeArea = (string)($recepcion['recibe']['area'] ?? 'METROLOGIA');
$recibeZona = (string)($recepcion['recibe']['zona'] ?? 'DM-000');
$entregaNombre = (string)($recepcion['entrega']['nombre'] ?? '');
$entregaRpe = (string)($recepcion['entrega']['rpe'] ?? '');
$entregaArea = (string)($recepcion['entrega']['area'] ?? '');
$entregaZona = (string)($recepcion['entrega']['zona'] ?? '');
$equipos = (array)($recepcion['equipos'] ?? []);

// Relleno a 16 filas para coincidir con el formato oficial.
$rows = [];
for ($i = 0; $i < 16; $i++) {
    $rows[] = $equipos[$i] ?? null;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formato de recepción <?= h($folioRecepcion ?: $recepcionId) ?></title>
    <style>
        :root{
            --green:#8cc63e;
            --green2:#7fb938;
            --border:#2b2b2b;
            --gray:#f4f6f8;
        }
        *{ box-sizing:border-box; }
        body{
            margin:0;
            font-family: "Segoe UI", Arial, sans-serif;
            color:#000;
            background:#fff;
        }
        .toolbar{
            padding:10px 14px;
            border-bottom:1px solid #ddd;
            display:flex;
            gap:8px;
            align-items:center;
            justify-content:space-between;
        }
        .btn{
            display:inline-block;
            padding:8px 10px;
            border:1px solid #999;
            background:#fff;
            border-radius:6px;
            cursor:pointer;
            font-size:14px;
            text-decoration:none;
            color:#111;
        }
        .btn.primary{ border-color:#2f6fdd; color:#2f6fdd; }
        .btn:hover{ background:#f6f6f6; }

        .page{
            width: 1050px;
            max-width: 100%;
            margin: 14px auto 18px;
            padding: 0 10px 14px;
        }

        .header-top{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            margin-bottom:6px;
        }
        .hdr-left{ width: 340px; }
        .hdr-center{
            flex:1;
            text-align:center;
            font-size:11px;
            line-height:1.2;
            padding-top:2px;
        }
        .hdr-right{
            width: 320px;
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            gap:6px;
        }
        .logo-left{
            width: 100%;
            max-height: 58px;
            object-fit: contain;
        }
        .logo-sig{
            width: 250px;
            max-height: 62px;
            object-fit: contain;
        }
        .logo-valle{
            width: 250px;
            max-height: 34px;
            object-fit: contain;
        }

        .band{
            background: var(--green);
            border:1px solid var(--border);
            padding:8px 10px;
            font-weight:800;
            text-align:center;
            text-transform:uppercase;
            letter-spacing:.2px;
        }

        .meta{
            width:100%;
            border-collapse:collapse;
            border:1px solid var(--border);
            border-top:0;
            margin-bottom:8px;
        }
        .meta td{
            border:1px solid var(--border);
            padding:6px 8px;
            font-size:12px;
        }
        .meta .k{ background: var(--green); font-weight:700; width:160px; text-transform:uppercase; }
        .meta .v{ background:#fff; }

        .tbl{
            width:100%;
            border-collapse:collapse;
            border:1px solid var(--border);
        }
        .tbl th, .tbl td{
            border:1px solid var(--border);
            padding:6px 6px;
            font-size:12px;
            vertical-align:middle;
        }
        .tbl thead th{
            background: var(--green);
            font-weight:800;
            text-transform:uppercase;
        }
        .tbl .subhead{
            background: var(--green);
            font-weight:800;
            text-transform:uppercase;
        }
        .tbl .num{ width:38px; text-align:center; font-weight:700; background:#f7fbf1; }
        .tbl .w-marca{ width:120px; }
        .tbl .w-modelo{ width:120px; }
        .tbl .w-serie{ width:130px; }
        .tbl .w-desc{ width:220px; }
        .tbl .w-obs{ width:220px; }
        .tbl .w-folio{ width:90px; text-align:center; font-weight:700; }

        .footer-grid{
            width:100%;
            border-collapse:collapse;
            border:1px solid var(--border);
            margin-top:8px;
        }
        .footer-grid td{
            border:1px solid var(--border);
            padding:6px 8px;
            font-size:12px;
        }
        .footer-grid .k{ background: var(--green); font-weight:800; text-transform:uppercase; }
        .footer-grid .sig{ height:56px; }
        .footer-grid .nota{ background:#ffeb3b; font-weight:800; color:#d21; }
        .code{
            font-size:11px;
            text-align:right;
            margin-top:6px;
            color:#111;
            font-weight:700;
        }

        @media print{
            .toolbar{ display:none !important; }
            .page{ margin:0; padding:0; width:auto; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div style="display:flex; gap:8px; align-items:center;">
            <a class="btn" href="<?= h($basePath) ?>/metrologia-recepcion.php?rid=<?= urlencode($recepcionId) ?>">Volver</a>
            <button class="btn primary" onclick="window.print()">Imprimir</button>
        </div>
        <div style="font-size:12px; color:#555;">
            Folio recepción: <strong><?= h($folioRecepcion) ?></strong>
        </div>
    </div>

    <div class="page">
        <div class="header-top">
            <div class="hdr-left">
                <?php if ($logoGobMxCfe): ?>
                    <img class="logo-left" src="<?= h($logoGobMxCfe) ?>" alt="Gobierno de México | CFE">
                <?php else: ?>
                    <div style="font-weight:800;">Gobierno de México</div>
                    <div style="font-weight:800;">CFE</div>
                <?php endif; ?>
            </div>
            <div class="hdr-center">
                <div style="font-weight:800;">DIRECCIÓN DE OPERACIÓN</div>
                <div>Sistema Integral de Gestión</div>
                <div style="font-weight:800;">SUBDIRECCIÓN DE DISTRIBUCIÓN</div>
                <div style="font-weight:800;">DIVISIÓN VALLE DE MÉXICO CENTRO</div>
                <div style="font-weight:800;">OFICINA DE METROLOGÍA</div>
            </div>
            <div class="hdr-right">
                <?php if ($logoSIG): ?>
                    <img class="logo-sig" src="<?= h($logoSIG) ?>" alt="SIG">
                <?php else: ?>
                    <div style="font-weight:800; text-align:right;">SIG</div>
                <?php endif; ?>
                <?php if ($logoValle): ?>
                    <img class="logo-valle" src="<?= h($logoValle) ?>" alt="Valle de México centro">
                <?php else: ?>
                    <div style="font-size:11px; color:#444; font-weight:700;">Valle de México centro</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="band">RECEPCIÓN DE EQUIPOS POR PARTE DEL LABORATORIO DE MEDICIÓN DIVISIONAL</div>
        <table class="meta">
            <tr>
                <td class="k">ÁREA QUE RECIBE</td>
                <td class="v"><?= h($areaRecibe) ?></td>
            </tr>
            <tr>
                <td class="k">DOMICILIO</td>
                <td class="v"><?= h($domicilio) ?></td>
            </tr>
        </table>

        <table class="tbl">
            <thead>
            <tr>
                <th>No.</th>
                <th class="w-marca">Marca</th>
                <th class="w-modelo">Modelo</th>
                <th class="w-serie">Serie</th>
                <th class="w-desc">Descripción</th>
                <th class="w-obs">Observaciones</th>
                <th class="w-folio">Folio</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $eq): ?>
                <?php $r = is_array($eq) ? $eq : []; ?>
                <tr>
                    <td class="num"><?= (int)($i + 1) ?></td>
                    <td><?= h($r['marca'] ?? '') ?></td>
                    <td><?= h($r['modelo'] ?? '') ?></td>
                    <td><?= h($r['serie'] ?? '') ?></td>
                    <td><?= h($r['descripcion'] ?? '') ?></td>
                    <td><?= h($r['observaciones'] ?? '') ?></td>
                    <td class="w-folio"><?= h($r['folio'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <table class="footer-grid">
            <tr>
                <td class="k" style="width:280px;">Motivo por el que se reciben:</td>
                <td><?= h($motivo) ?></td>
                <td class="k" style="width:260px; text-align:center;">EQUIPOS PARA CALIBRACIÓN</td>
            </tr>
            <tr>
                <td colspan="3" style="padding:0;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="border-right:1px solid var(--border); width:50%; padding:0;">
                                <div class="band" style="border:0; border-bottom:1px solid var(--border);">RECIBE</div>
                                <table style="width:100%; border-collapse:collapse;">
                                    <tr><td class="k" style="width:90px;">NOMBRE:</td><td><?= h($recibeNombre) ?></td></tr>
                                    <tr><td class="k">RPE:</td><td><?= h($recibeRpe) ?></td></tr>
                                    <tr><td class="k">ÁREA:</td><td><?= h(mb_strtoupper($recibeArea ?: 'METROLOGIA')) ?></td></tr>
                                    <tr><td class="k">ZONA:</td><td><?= h($recibeZona) ?></td></tr>
                                    <tr><td class="sig" colspan="2" style="text-align:center; font-weight:700;">FIRMA</td></tr>
                                </table>
                            </td>
                            <td style="width:50%; padding:0;">
                                <div class="band" style="border:0; border-bottom:1px solid var(--border);">ENTREGA</div>
                                <table style="width:100%; border-collapse:collapse;">
                                    <tr><td class="k" style="width:90px;">NOMBRE:</td><td><?= h($entregaNombre) ?></td></tr>
                                    <tr><td class="k">RPE:</td><td><?= h($entregaRpe) ?></td></tr>
                                    <tr><td class="k">ZONA:</td><td><?= h($entregaZona) ?></td></tr>
                                    <tr><td class="k">ÁREA:</td><td><?= h(mb_strtoupper($entregaArea)) ?></td></tr>
                                    <tr><td class="sig" colspan="2" style="text-align:center; font-weight:700;">FIRMA</td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="nota" colspan="2">NOTA</td>
                <td style="text-align:right; font-weight:700;"><?= h(met_es_fecha_larga($fechaRecepcion)) ?></td>
            </tr>
        </table>

        <div class="code">O-4413-L02-R04</div>
        <?php if ($folioRecepcion): ?>
            <div class="code" style="text-align:left; font-weight:600; color:#444;">Referencia SIGTAE: <?= h($folioRecepcion) ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

