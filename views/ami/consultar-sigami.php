<?php
$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
<<<<<<< HEAD
sigtae_page_header(
    'Consultar SIGAMI',
    'Consulta `tlpnMedidor` y `tlpnIdSigAmi` en `kcentinel.dbo.telepnuevomedidor` (servidor ' . htmlspecialchars(\App\Services\AmiKcentinelConnection::SERVER) . ').',
    $actions
);
?>

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header card-header-accent"><i class="bi bi-info-circle me-1"></i> Uso</div>
            <div class="card-body small">
                <p class="mb-2">Pegue los medidores, <strong>uno por línea</strong>. Se eliminan duplicados y líneas vacías.</p>
                <p class="mb-0 text-muted">En pantalla solo se muestran hasta <strong>200</strong> filas de resultado; si hay más coincidencias, use <strong>Descargar CSV</strong> para el listado completo.</p>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header card-header-accent d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="bi bi-list-ul me-1"></i> Medidores</span>
                <span class="text-muted small" id="amiConsCount">0</span>
            </div>
            <div class="card-body">
                <label for="amiConsMedidores" class="form-label small fw-semibold mb-1">Lista de medidores</label>
                <textarea class="form-control font-monospace" id="amiConsMedidores" rows="14" placeholder="Ej:&#10;E807PH&#10;E806PH&#10;E808PH"></textarea>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-primary" type="button" id="btnAmiConsConsultar">
                        <i class="bi bi-search me-1"></i> Consultar
                    </button>
                    <button class="btn btn-outline-secondary" type="button" id="btnAmiConsLimpiar">
                        <i class="bi bi-eraser me-1"></i> Limpiar
                    </button>
                    <button class="btn btn-outline-success" type="button" id="btnAmiConsCsv" disabled title="Requiere al menos un medidor en la lista">
                        <i class="bi bi-filetype-csv me-1"></i> Descargar CSV
                    </button>
                </div>

                <div id="amiConsResultado" class="mt-3 alert d-none mb-0"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card d-none" id="amiConsStatsCard">
            <div class="card-header"><i class="bi bi-bar-chart-steps me-1"></i> Resumen</div>
            <div class="card-body py-2">
                <ul class="list-unstyled small mb-0" id="amiConsStats"></ul>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card d-none" id="amiConsTableCard">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-table me-1"></i> Resultado (vista previa)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0 w-100" id="tblConsultaSigami" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>tlpnMedidor</th>
                                <th>tlpnIdSigAmi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const base = window.SIGTAE_BASE_PATH || '';
    const api = base + '/api/ami-consultar-sigami.php';
    const medEl = document.getElementById('amiConsMedidores');
    const countEl = document.getElementById('amiConsCount');
    const resEl = document.getElementById('amiConsResultado');
    const statsCard = document.getElementById('amiConsStatsCard');
    const statsUl = document.getElementById('amiConsStats');
    const tableCard = document.getElementById('amiConsTableCard');
    const btnQ = document.getElementById('btnAmiConsConsultar');
    const btnClr = document.getElementById('btnAmiConsLimpiar');
    const btnCsv = document.getElementById('btnAmiConsCsv');
    const $tbl = jQuery('#tblConsultaSigami');

    function setAlert(kind, msg) {
        resEl.className = 'alert alert-' + kind + ' mb-0';
        resEl.innerHTML = msg ? String(msg).replace(/\n/g, '<br>') : '';
        resEl.classList.toggle('d-none', !msg);
    }

    function parseList() {
        const raw = String(medEl.value || '').trim();
        if (!raw) return [];
        const items = raw.split(/\r?\n/).map(s => String(s || '').trim()).filter(Boolean);
        const seen = new Set();
        const out = [];
        for (const m of items) {
            const k = m.toUpperCase();
            if (seen.has(k)) continue;
            seen.add(k);
            out.push(k);
        }
        return out;
    }

    function updateCount() {
        const n = parseList().length;
        countEl.textContent = n.toLocaleString('es-MX') + ' medidor(es) único(s)';
        btnCsv.disabled = n === 0;
    }

    function destroyDataTable() {
        if (jQuery.fn.DataTable.isDataTable($tbl[0])) {
            $tbl.DataTable().destroy();
        }
        $tbl.find('tbody').empty();
    }

    medEl.addEventListener('input', updateCount);
    updateCount();

    btnClr.addEventListener('click', function () {
        medEl.value = '';
        setAlert('', '');
        statsCard.classList.add('d-none');
        tableCard.classList.add('d-none');
        destroyDataTable();
        updateCount();
        medEl.focus();
    });

    function renderStats(st) {
        if (!st) {
            statsCard.classList.add('d-none');
            return;
        }
        statsCard.classList.remove('d-none');
        const lines = [
            '<li><strong>Pedidos:</strong> ' + Number(st.pedidos || 0).toLocaleString('es-MX') + '</li>',
            '<li><strong>Filas devueltas por la BD:</strong> ' + Number(st.filas_devueltas || 0).toLocaleString('es-MX') + '</li>',
            '<li><strong>Medidores de su lista encontrados:</strong> ' + Number(st.medidores_encontrados || 0).toLocaleString('es-MX') + '</li>',
            '<li><strong>No encontrados en BD:</strong> ' + Number(st.no_encontrados || 0).toLocaleString('es-MX') + '</li>',
            '<li><strong>Filas mostradas en tabla:</strong> ' + Number(st.mostrados_en_vista || 0).toLocaleString('es-MX') +
                (st.vista_truncada ? ' <span class="text-warning">(vista truncada)</span>' : '') + '</li>'
        ];
        statsUl.innerHTML = lines.join('');
    }

    function fillTable(rows) {
        destroyDataTable();
        const tbody = $tbl.find('tbody');
        rows.forEach(function (r) {
            const tr = jQuery('<tr/>');
            tr.append(jQuery('<td/>').text(r.medidor != null ? String(r.medidor) : ''));
            tr.append(jQuery('<td/>').text(r.id_sigami != null && r.id_sigami !== '' ? String(r.id_sigami) : ''));
            tbody.append(tr);
        });
        tableCard.classList.remove('d-none');
        if (rows.length === 0) return;
        $tbl.DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            order: [[0, 'asc']],
            pageLength: 50,
            lengthMenu: [[25, 50, 100, 250, 500, -1], [25, 50, 100, 250, 500, 'Todos']],
            scrollY: '420px',
            scrollCollapse: true
        });
    }

    btnQ.addEventListener('click', async function () {
        setAlert('', '');
        const medidores = parseList();
        if (!medidores.length) {
            setAlert('warning', 'No hay medidores para consultar.');
            return;
        }
        btnQ.disabled = true;
        btnQ.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Consultando…';
        try {
            const res = await fetch(api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
                body: JSON.stringify({ format: 'json', medidores: medidores })
            });
            const data = await res.json().catch(function () { return null; });
            if (!res.ok || !data || data.ok === false) {
                throw new Error((data && data.message) ? data.message : ('HTTP ' + res.status));
            }
            setAlert('success', data.message || 'Consulta completada.');
            renderStats(data.stats);
            fillTable(Array.isArray(data.rows) ? data.rows : []);
        } catch (e) {
            setAlert('danger', 'Error: ' + (e.message || String(e)));
            statsCard.classList.add('d-none');
            tableCard.classList.add('d-none');
            destroyDataTable();
        } finally {
            btnQ.disabled = false;
            btnQ.innerHTML = '<i class="bi bi-search me-1"></i> Consultar';
        }
    });

    btnCsv.addEventListener('click', async function () {
        const medidores = parseList();
        if (!medidores.length) {
            setAlert('warning', 'No hay medidores para exportar.');
            return;
        }
        btnCsv.disabled = true;
        const prevHtml = btnCsv.innerHTML;
        btnCsv.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Generando…';
        try {
            const res = await fetch(api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
                body: JSON.stringify({ format: 'csv', medidores: medidores })
            });
            const ct = (res.headers.get('Content-Type') || '').toLowerCase();
            if (!res.ok) {
                let msg = 'HTTP ' + res.status;
                if (ct.indexOf('json') >= 0) {
                    const j = await res.json().catch(function () { return null; });
                    if (j && j.message) msg = j.message;
                }
                throw new Error(msg);
            }
            const blob = await res.blob();
            const dispo = res.headers.get('Content-Disposition') || '';
            let fname = 'consulta_sigami.csv';
            const m = /filename\*=UTF-8''([^;\n]+)|filename="([^"]+)"/i.exec(dispo);
            if (m) fname = decodeURIComponent((m[1] || m[2] || fname).trim());
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fname;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            setAlert('success', 'Descarga iniciada: ' + fname);
        } catch (e) {
            setAlert('danger', 'CSV: ' + (e.message || String(e)));
        } finally {
            btnCsv.disabled = false;
            btnCsv.innerHTML = prevHtml;
        }
    });
})();
</script>
=======
sigtae_page_header('Consultar SIGAMI', 'Consultas del estado SIGAMI (próximamente)', $actions);
?>

<?php sigtae_empty_state('Módulo en construcción. Aquí irá la consulta de SIGAMI.', 'bi-search'); ?>

>>>>>>> a566762f56f34e258489665ef5183cfc57a69d90
