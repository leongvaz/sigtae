<?php
require_once __DIR__ . '/Login/comprobarSession.php';
require_once __DIR__ . '/Login/inactividad.php';

$user = $_SESSION['user'] ?? [];
$nombre = $user['nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG - Laboratorio (Sistema Integral de Gestión)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100">
    <header class="bg-white border-bottom shadow-sm">
        <div class="container-fluid px-4 py-3 d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0 text-dark">SIG - Laboratorio (Sistema Integral de Gestión)</h1>
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-primary btn-sm" title="Abrir norma de consulta"
                        data-bs-toggle="modal" data-bs-target="#modalNorma17025">
                    @ Norma 17025
                </button>
                <span class="text-muted small"><?= htmlspecialchars($nombre) ?></span>
                <a href="Login/cerrarSession.php" class="btn btn-outline-secondary btn-sm">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4 px-4">
        <div id="alert-container"></div>
        <div id="dashboard-loading" class="text-center py-5 text-muted">
            <div class="spinner-border" role="status"></div>
            <p class="mt-2">Cargando secciones...</p>
        </div>
        <div id="dashboard-cards" class="row g-4" style="display: none;"></div>
    </main>

    <!-- Modal: nodos (subíndices) -->
    <div class="modal fade" id="modalNodes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNodesTitle">Subíndices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="nodes-back-bar" class="mb-3" style="display: none;">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNodesBack">← Atrás</button>
                    </div>
                    <div id="nodes-loading" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                    <div id="nodes-table-wrap" style="display: none;">
                        <table class="table table-hover">
                            <thead><tr><th>Apartado</th><th class="text-end">Estado</th></tr></thead>
                            <tbody id="nodes-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: documentos (evidencias) -->
    <div class="modal fade" id="modalDocuments" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDocumentsTitle">Documentos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="documents-refs-812" class="border rounded p-3 mb-3 bg-light" style="display: none;"></div>
                    <div class="border rounded p-3 mb-3 bg-light" id="dropzone">
                        <p class="mb-1 text-muted">Arrastre archivos aquí o <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectFiles">Seleccionar archivo(s)</button></p>
                        <input type="file" id="inputFiles" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" style="display: none;">
                        <p class="small text-muted mb-0">PDF, JPG, PNG (máx. 25 MB)</p>
                        <div class="mt-2">
                            <label for="uploadComment" class="form-label small text-muted mb-0">Comentario (opcional)</label>
                            <input type="text" class="form-control form-control-sm" id="uploadComment" placeholder="Agregar un comentario a los archivos que suba..." maxlength="500">
                        </div>
                    </div>
                    <div id="documents-loading" class="text-center py-3 text-muted" style="display: none;">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                    <div id="documents-table-wrap">
                        <table class="table table-sm">
                            <thead><tr><th>Nombre</th><th>Tamaño</th><th>Fecha</th><th>Comentario</th><th class="text-end">Acciones</th></tr></thead>
                            <tbody id="documents-tbody"></tbody>
                        </table>
                    </div>
                    <div id="preview-panel" class="mt-3 border rounded p-3 bg-white" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Vista previa</strong>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClosePreview">Cerrar vista previa</button>
                        </div>
                        <div id="preview-content" class="border rounded overflow-auto" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Avance General -->
    <div class="modal fade" id="modalAvanceGeneral" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Avance General</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Sumatoria del avance de los requisitos (secciones 4 a 8).</p>
                    <div id="avance-general-loading" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                    <div id="avance-general-content" style="display: none;">
                        <table class="table table-sm">
                            <thead><tr><th>Sección</th><th class="text-end">Atendidos</th><th class="text-end">Total</th><th class="text-end">%</th></tr></thead>
                            <tbody id="avance-general-tbody"></tbody>
                            <tfoot><tr class="fw-bold"><td>Total</td><td id="avance-total-attended" class="text-end">0</td><td id="avance-total-total" class="text-end">0</td><td id="avance-total-pct" class="text-end">0%</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Auditoría (Interna / Externa) -->
    <div class="modal fade" id="modalAuditoria" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAuditoriaTitle">Auditoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="mb-2">Documentos de auditoría (máximo 2)</h6>
                    <div class="border rounded p-3 mb-4 bg-light">
                        <p class="mb-1 text-muted small">Arrastre o seleccione hasta 2 archivos.</p>
                        <input type="file" id="auditoriaFiles" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" multiple style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAuditoriaSelectFiles">Seleccionar archivo(s)</button>
                        <div id="auditoria-docs-loading" class="d-inline-block ms-2 text-muted" style="display: none;">Subiendo...</div>
                    </div>
                    <table class="table table-sm mb-4">
                        <thead><tr><th>Nombre</th><th>Tamaño</th><th>Fecha</th><th class="text-end">Acciones</th></tr></thead>
                        <tbody id="auditoria-docs-tbody"></tbody>
                    </table>
                    <hr>
                    <h6 class="mb-2">No conformidades</h6>
                    <p class="text-muted small mb-2">Agregue comentario, ID y PDF por cada no conformidad. Puede listar varias.</p>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <input type="text" id="ncRefId" class="form-control form-control-sm" placeholder="ID (ej. NC-001)" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <input type="text" id="ncComment" class="form-control form-control-sm" placeholder="Comentario" maxlength="500">
                            </div>
                            <div class="col-md-2">
                                <input type="file" id="ncFile" accept=".pdf" style="display: none;">
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnNcSelectFile">PDF</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnNcAdd">Agregar no conformidad</button>
                    </div>
                    <div id="nc-loading" class="text-center py-2 text-muted" style="display: none;"></div>
                    <table class="table table-sm">
                        <thead><tr><th>ID</th><th>Comentario</th><th>Documento</th><th class="text-end">Acciones</th></tr></thead>
                        <tbody id="noconformidades-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Norma 17025 -->
    <div class="modal fade" id="modalNorma17025" tabindex="-1" aria-labelledby="modalNorma17025Label" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNorma17025Label">Consulta: NMX-EC-17025-IMNC-2018</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe
                        src="data/NMX-EC-17025-IMNC-2018.pdf"
                        title="Norma NMX-EC-17025-IMNC-2018"
                        class="w-100 h-100 border-0"
                        style="min-height: calc(100vh - 70px);"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast placeholder -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
