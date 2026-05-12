<?php
$actions = '<a href="' . htmlspecialchars($basePath ?? '') . '/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>';
sigtae_page_header('Cambio SIGAMI', 'Actualizar estado de medidores (SIGAMI/SINAMED)', $actions);
?>

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header card-header-accent"><i class="bi bi-sliders me-1"></i> Parámetros</div>
            <div class="card-body">
                <label for="amiEstado" class="form-label small fw-semibold mb-1">Estado de los medidores</label>
                <select class="form-select form-select-sm" id="amiEstado" style="max-width: 28rem;">
                    <option value="2">LANDIS (2)</option>
                    <option value="3">ENERI (3)</option>
                    <option value="4">ALDESA (4)</option>
                    <option value="6">SINAMED (6)</option>
                    <option value="7">SIGAMI CENTRALIZADO (7)</option>
                </select>
                <div class="form-text">El cambio actualiza `tlpnIdSigAmi` en `kcentinel.dbo.TELEPNUEVOMEDIDOR`.</div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header card-header-accent d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="bi bi-list-ul me-1"></i> Medidores</span>
                <span class="text-muted small" id="amiCount">0</span>
            </div>
            <div class="card-body">
                <label for="amiMedidores" class="form-label small fw-semibold mb-1">Pega los medidores aquí (uno por línea)</label>
                <textarea class="form-control font-monospace" id="amiMedidores" rows="14" placeholder="Ej:&#10;E807PH&#10;E806PH&#10;E808PH"></textarea>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-primary" type="button" id="btnAmiActualizar">
                        <i class="bi bi-arrow-repeat me-1"></i> Actualizar
                    </button>
                    <button class="btn btn-outline-secondary" type="button" id="btnAmiLimpiar">
                        <i class="bi bi-eraser me-1"></i> Limpiar
                    </button>
                </div>

                <div id="amiResultado" class="mt-3 alert d-none mb-0"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const base = window.SIGTAE_BASE_PATH || '';
    const api = base + '/api/ami-cambio-sigami.php';
    const estadoEl = document.getElementById('amiEstado');
    const medEl = document.getElementById('amiMedidores');
    const resEl = document.getElementById('amiResultado');
    const countEl = document.getElementById('amiCount');
    const btn = document.getElementById('btnAmiActualizar');
    const btnClr = document.getElementById('btnAmiLimpiar');

    function setAlert(kind, msg) {
        resEl.className = 'alert alert-' + kind;
        resEl.textContent = msg || '';
        resEl.classList.toggle('d-none', !msg);
    }
    function parseList() {
        const raw = String(medEl.value || '').trim();
        if (!raw) return [];
        const items = raw.split(/\r?\n/).map(s => String(s||'').trim()).filter(Boolean);
        // unique, preserve order
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
        countEl.textContent = n + ' medidor(es)';
    }
    medEl.addEventListener('input', updateCount);
    updateCount();

    btnClr.addEventListener('click', () => {
        medEl.value = '';
        setAlert('', '');
        updateCount();
        medEl.focus();
    });

    btn.addEventListener('click', async () => {
        setAlert('', '');
        const medidores = parseList();
        if (!medidores.length) return setAlert('warning', 'No hay medidores para enviar.');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Actualizando…';
        try {
            const res = await fetch(api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' },
                body: JSON.stringify({ estado: parseInt(estadoEl.value, 10), medidores })
            });
            const data = await res.json().catch(() => null);
            if (!res.ok || !data || data.ok === false) {
                throw new Error((data && data.message) ? data.message : ('HTTP ' + res.status));
            }
            setAlert('success', data.message || 'Actualización completada.');
        } catch (e) {
            setAlert('danger', 'Error: ' + (e.message || String(e)));
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Actualizar';
        }
    });
})();
</script>

