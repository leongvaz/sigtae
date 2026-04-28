(function () {
    'use strict';

    var base = (window.__SIG_MODULE_BASE != null ? String(window.__SIG_MODULE_BASE) : '') ||
        (location.pathname.replace(/\/index\.php$/, '').replace(/\/$/, '') || '');
    var appBase = (window.__SIGTAE_APP_BASE != null ? String(window.__SIGTAE_APP_BASE) : '');
    function api(path) { return base + (path.startsWith('/') ? path : '/api/' + path); }
    function loginUrl() { return (appBase || '') + '/login.php?expired=1'; }

    var modalNodes = null;
    var modalDocuments = null;
    var nodesStack = [];
    var currentSectionId = null;
    var currentSectionTitle = '';
    var currentNodeId = null;
    var currentNodeTitle = '';
    var onSectionsRefetch = null;

    function showAlert(msg, type) {
        type = type || 'danger';
        var el = document.getElementById('alert-container');
        el.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show">' + escapeHtml(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
    function showToast(msg) {
        var container = document.getElementById('toast-container');
        var id = 'toast-' + Date.now();
        container.innerHTML = '<div class="toast align-items-center text-white bg-dark border-0" id="' + id + '" role="alert"><div class="d-flex"><div class="toast-body">' + escapeHtml(msg) + '</div></div></div>';
        var toastEl = document.getElementById(id);
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
    }
    function fetchOptions(extra) {
        var o = { credentials: 'same-origin' };
        if (extra) { for (var k in extra) o[k] = extra[k]; }
        return o;
    }

    function loadSections() {
        var loading = document.getElementById('dashboard-loading');
        var cardsWrap = document.getElementById('dashboard-cards');
        fetch(api('sections.php'), fetchOptions())
            .then(function (r) {
                if (r.status === 401) {
                    var u = new URL(r.url);
                    location.href = loginUrl();
                    return Promise.reject(new Error('No autenticado'));
                }
                return r.json();
            })
            .then(function (sections) {
                loading.style.display = 'none';
                cardsWrap.style.display = 'flex';
                renderCards(sections);
            })
            .catch(function (err) {
                loading.style.display = 'none';
                cardsWrap.style.display = 'flex';
                showAlert(err.message || 'Error al cargar secciones');
            });
    }

    function renderCards(sections) {
        var wrap = document.getElementById('dashboard-cards');
        wrap.innerHTML = '';
        sections.forEach(function (s) {
            var isActive = s.isActive === true;
            var card = document.createElement('div');
            card.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';
            var showNum = (s.id >= 4 && s.id <= 8);
            var titleBlock = showNum
                ? ('<h6 class="card-title mb-1">' + escapeHtml(s.title) + '</h6>' +
                   '<p class="card-section-num my-2">' + s.id + '</p>')
                : '<h6 class="card-title">' + escapeHtml(s.title) + '</h6>';
            card.innerHTML =
                '<div class="card h-100 ' + (isActive ? '' : 'disabled') + '" data-section-id="' + s.id + '" data-section-title="' + escapeHtml(s.title) + '" data-active="' + (isActive ? '1' : '0') + '">' +
                '  <div class="card-body">' +
                titleBlock +
                (isActive
                    ? ('<div class="progress mb-2" style="height: 8px;"><div class="progress-bar" role="progressbar" style="width: ' + (s.progressPercent || 0) + '%"></div></div>' +
                       '<p class="small text-muted mb-0">' + (s.attendedLeaves || 0) + '/' + (s.totalLeaves || 0) + ' atendidos</p>')
                    : '<span class="badge bg-warning text-dark">Próximamente</span>') +
                '  </div></div>';
            wrap.appendChild(card);
            card.querySelector('.card').addEventListener('click', function () {
                if (this.getAttribute('data-active') !== '1') return;
                var sectionId = parseInt(this.getAttribute('data-section-id'), 10);
                var sectionTitle = this.getAttribute('data-section-title');
                if (sectionId === 9) {
                    openAvanceGeneralModal();
                } else if (sectionId === 10 || sectionId === 11) {
                    openAuditoriaModal(sectionId === 10 ? 'interna' : 'externa', sectionTitle);
                } else {
                    openNodesModal(sectionId, sectionTitle);
                }
            });
        });
    }

    function openNodesModal(sectionId, sectionTitle) {
        currentSectionId = sectionId;
        currentSectionTitle = sectionTitle;
        nodesStack = [{ sectionId: sectionId, parentId: null, title: sectionTitle }];
        if (!modalNodes) modalNodes = new bootstrap.Modal(document.getElementById('modalNodes'));
        document.getElementById('modalNodesTitle').textContent = sectionTitle;
        document.getElementById('nodes-back-bar').style.display = 'none';
        loadNodesInModal();
        modalNodes.show();
    }

    function loadNodesInModal() {
        var cur = nodesStack[nodesStack.length - 1];
        var q = '?sectionId=' + encodeURIComponent(cur.sectionId);
        if (cur.parentId != null) q += '&parentId=' + encodeURIComponent(cur.parentId);
        document.getElementById('nodes-loading').style.display = 'block';
        document.getElementById('nodes-table-wrap').style.display = 'none';
        fetch(api('nodes.php') + q, fetchOptions())
            .then(function (r) {
                if (r.status === 401) { location.href = loginUrl(); return Promise.reject(); }
                return r.json();
            })
            .then(function (nodes) {
                document.getElementById('nodes-loading').style.display = 'none';
                document.getElementById('nodes-table-wrap').style.display = 'block';
                document.getElementById('nodes-back-bar').style.display = nodesStack.length > 1 ? 'block' : 'none';
                var cur = nodesStack[nodesStack.length - 1];
                if (nodes.length === 0 && nodesStack.length > 1 && cur.parentId) {
                    modalNodes.hide();
                    openDocumentsModal(cur.parentId, cur.title);
                    return;
                }
                var tbody = document.getElementById('nodes-tbody');
                tbody.innerHTML = '';
                nodes.forEach(function (n) {
                    var tr = document.createElement('tr');
                    var estado = n.isLeaf
                        ? (n.attended ? '<span class="badge bg-success">Atendido</span>' : '<span class="badge bg-secondary">Pendiente</span>')
                        : (n.totalLeaves != null ? '<span class="text-muted small">' + (n.attendedLeaves || 0) + '/' + n.totalLeaves + ' atendidos</span>' : '');
                    tr.innerHTML = '<td>' + escapeHtml(n.title) + '</td><td class="text-end">' + estado + '</td>';
                    tr.style.cursor = 'pointer';
                    tr.addEventListener('click', function () {
                        if (n.isLeaf) {
                            modalNodes.hide();
                            openDocumentsModal(n.id, n.title);
                        } else {
                            nodesStack.push({ sectionId: currentSectionId, parentId: n.id, title: n.title });
                            document.getElementById('modalNodesTitle').textContent = n.title;
                            loadNodesInModal();
                        }
                    });
                    tbody.appendChild(tr);
                });
            })
            .catch(function () {});
    }

    document.getElementById('btnNodesBack').addEventListener('click', function () {
        if (nodesStack.length <= 1) return;
        nodesStack.pop();
        var cur = nodesStack[nodesStack.length - 1];
        document.getElementById('modalNodesTitle').textContent = cur.title;
        loadNodesInModal();
    });

    function openDocumentsModal(nodeId, nodeTitle) {
        currentNodeId = nodeId;
        currentNodeTitle = nodeTitle;
        if (!modalDocuments) modalDocuments = new bootstrap.Modal(document.getElementById('modalDocuments'));
        document.getElementById('modalDocumentsTitle').textContent = 'Documentos: ' + nodeTitle;
        document.getElementById('preview-panel').style.display = 'none';
        var refsPanel = document.getElementById('documents-refs-812');
        if (refsPanel) {
            refsPanel.style.display = nodeId === '8.1.2' ? 'block' : 'none';
            if (nodeId === '8.1.2') renderRefs812Links(refsPanel);
        }
        loadDocumentsInModal();
        modalDocuments.show();
    }

    function navigateToNodeInModal(sectionId, sectionTitle, nodeId, nodeTitle) {
        modalDocuments.hide();
        currentSectionId = sectionId;
        currentSectionTitle = sectionTitle;
        nodesStack = [
            { sectionId: sectionId, parentId: null, title: sectionTitle },
            { sectionId: sectionId, parentId: nodeId, title: nodeTitle }
        ];
        document.getElementById('modalNodesTitle').textContent = nodeTitle;
        document.getElementById('nodes-back-bar').style.display = 'block';
        loadNodesInModal();
        modalNodes.show();
    }

    var refs812 = [
        { id: '8.2', title: '8.2 Documentación del sistema de gestión (Opción A)' },
        { id: '8.3', title: '8.3 Control de documentos del sistema de gestión (Opción A)' },
        { id: '8.4', title: '8.4 Control de registros (Opción A)' },
        { id: '8.5', title: '8.5 Acciones para abordar riesgos y oportunidades (Opción A)' },
        { id: '8.6', title: '8.6 Mejora (Opción A)' },
        { id: '8.7', title: '8.7 Acciones correctivas (Opción A)' },
        { id: '8.8', title: '8.8 Auditorías internas (Opción A)' },
        { id: '8.9', title: '8.9 Revisiones por la dirección (Opción A)' }
    ];
    function renderRefs812Links(container) {
        if (!container || currentSectionId != 8) return;
        var html = '<p class="mb-2 small text-muted">Opción A: un sistema de gestión del laboratorio debe tratar lo siguiente. Clic en un apartado para ir a ese punto:</p><ul class="list-unstyled mb-0">';
        refs812.forEach(function (r) {
            html += '<li class="mb-1"><a href="#" class="link-ref-812" data-node-id="' + escapeHtml(r.id) + '" data-node-title="' + escapeHtml(r.title) + '">' + escapeHtml(r.title) + ' — clic para ir</a></li>';
        });
        html += '</ul>';
        container.innerHTML = html;
        container.querySelectorAll('.link-ref-812').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                navigateToNodeInModal(8, currentSectionTitle, this.getAttribute('data-node-id'), this.getAttribute('data-node-title'));
            });
        });
    }

    function loadDocumentsInModal() {
        if (!currentNodeId) return;
        document.getElementById('documents-loading').style.display = 'block';
        fetch(api('documents.php') + '?nodeId=' + encodeURIComponent(currentNodeId), fetchOptions())
            .then(function (r) {
                if (r.status === 401) { location.href = loginUrl(); return Promise.reject(); }
                return r.json();
            })
            .then(function (docs) {
                document.getElementById('documents-loading').style.display = 'none';
                var tbody = document.getElementById('documents-tbody');
                tbody.innerHTML = '';
                if (docs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">No hay documentos. Suba archivos arriba.</td></tr>';
                } else {
                    docs.forEach(function (d) {
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + escapeHtml(d.originalName) + '</td>' +
                            '<td>' + formatSize(d.size) + '</td>' +
                            '<td>' + formatDate(d.createdAt) + '</td>' +
                            '<td class="text-muted small">' + (d.comment ? escapeHtml(d.comment) : '—') + '</td>' +
                            '<td class="text-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-preview" data-id="' + escapeHtml(d.id) + '" data-mime="' + escapeHtml(d.mimeType || '') + '">Vista previa</button>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + escapeHtml(d.id) + '">Eliminar</button></td>';
                        tbody.appendChild(tr);
                    });
                    tbody.querySelectorAll('.btn-preview').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            showPreview(this.getAttribute('data-id'), this.getAttribute('data-mime'));
                        });
                    });
                    tbody.querySelectorAll('.btn-delete').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm('¿Eliminar este documento?')) return;
                            deleteDocument(this.getAttribute('data-id'));
                        });
                    });
                }
            })
            .catch(function () {
                document.getElementById('documents-loading').style.display = 'none';
            });
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    function formatDate(iso) {
        try {
            var d = new Date(iso);
            return d.toLocaleDateString('es-MX') + ' ' + d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return iso;
        }
    }

    function showPreview(docId, mime) {
        var panel = document.getElementById('preview-panel');
        var content = document.getElementById('preview-content');
        var url = api('document_inline.php') + '?id=' + encodeURIComponent(docId);
        content.innerHTML = '';
        mime = (mime || '').toLowerCase();
        if (mime.indexOf('pdf') !== -1) {
            content.innerHTML = '<iframe src="' + url + '" class="w-100" style="height: 70vh; border: 0;"></iframe>';
        } else if (mime.indexOf('image') !== -1) {
            content.innerHTML = '<img src="' + url + '" alt="Vista previa" class="img-fluid">';
        } else {
            content.innerHTML = '<p class="text-muted p-3">Vista previa no disponible para este tipo.</p>';
        }
        panel.style.display = 'block';
    }
    document.getElementById('btnClosePreview').addEventListener('click', function () {
        document.getElementById('preview-panel').style.display = 'none';
        document.getElementById('preview-content').innerHTML = '';
    });

    function deleteDocument(docId) {
        fetch(api('document_delete.php'), fetchOptions({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ documentId: docId })
        }))
            .then(function (r) {
                if (r.status === 401) { location.href = loginUrl(); return; }
                return r.json();
            })
            .then(function (data) {
                if (data && data.ok) {
                    loadDocumentsInModal();
                    if (typeof onSectionsRefetch === 'function') onSectionsRefetch();
                }
            });
    }

    var dropzone = document.getElementById('dropzone');
    var inputFiles = document.getElementById('inputFiles');
    document.getElementById('btnSelectFiles').addEventListener('click', function () { inputFiles.click(); });
    inputFiles.addEventListener('change', function () {
        if (this.files.length) uploadFiles(this.files);
    });
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', function () { dropzone.classList.remove('dragover'); });
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
    });

    function uploadFiles(files) {
        if (!currentNodeId) return;
        var fd = new FormData();
        fd.append('nodeId', currentNodeId);
        var commentEl = document.getElementById('uploadComment');
        if (commentEl && commentEl.value.trim()) {
            fd.append('comment', commentEl.value.trim());
        }
        for (var i = 0; i < files.length; i++) {
            fd.append('files[]', files[i]);
        }
        document.getElementById('documents-loading').style.display = 'block';
        fetch(api('documents.php'), fetchOptions({
            method: 'POST',
            body: fd
        }))
            .then(function (r) {
                if (r.status === 401) { location.href = loginUrl(); return Promise.reject(); }
                return r.json();
            })
            .then(function (data) {
                document.getElementById('documents-loading').style.display = 'none';
                if (data && data.added && data.added.length) {
                    if (commentEl) commentEl.value = '';
                    loadDocumentsInModal();
                    if (typeof onSectionsRefetch === 'function') onSectionsRefetch();
                }
                if (data && data.errors && data.errors.length) {
                    showAlert(data.errors.join('; '));
                }
            })
            .catch(function () {
                document.getElementById('documents-loading').style.display = 'none';
            });
    }

    onSectionsRefetch = function () { loadSections(); };

    var modalAvanceGeneral = null;
    function openAvanceGeneralModal() {
        if (!modalAvanceGeneral) modalAvanceGeneral = new bootstrap.Modal(document.getElementById('modalAvanceGeneral'));
        document.getElementById('avance-general-loading').style.display = 'block';
        document.getElementById('avance-general-content').style.display = 'none';
        modalAvanceGeneral.show();
        fetch(api('sections.php'), fetchOptions())
            .then(function (r) { return r.status === 401 ? Promise.reject() : r.json(); })
            .then(function (sections) {
                document.getElementById('avance-general-loading').style.display = 'none';
                document.getElementById('avance-general-content').style.display = 'block';
                var tbody = document.getElementById('avance-general-tbody');
                tbody.innerHTML = '';
                var totalA = 0, totalB = 0;
                sections.filter(function (s) { return s.id >= 4 && s.id <= 8; }).forEach(function (s) {
                    totalA += s.attendedLeaves || 0;
                    totalB += s.totalLeaves || 0;
                    var pct = (s.totalLeaves > 0) ? Math.round((s.attendedLeaves / s.totalLeaves) * 100) : 0;
                    tbody.innerHTML += '<tr><td>' + escapeHtml(s.title) + '</td><td class="text-end">' + (s.attendedLeaves || 0) + '</td><td class="text-end">' + (s.totalLeaves || 0) + '</td><td class="text-end">' + pct + '%</td></tr>';
                });
                document.getElementById('avance-total-attended').textContent = totalA;
                document.getElementById('avance-total-total').textContent = totalB;
                document.getElementById('avance-total-pct').textContent = (totalB > 0 ? Math.round((totalA / totalB) * 100) : 0) + '%';
            })
            .catch(function () {
                document.getElementById('avance-general-loading').style.display = 'none';
                showAlert('Error al cargar avance');
            });
    }

    var modalAuditoria = null;
    var currentAuditoriaTipo = '';
    function openAuditoriaModal(tipo, title) {
        currentAuditoriaTipo = tipo;
        if (!modalAuditoria) modalAuditoria = new bootstrap.Modal(document.getElementById('modalAuditoria'));
        document.getElementById('modalAuditoriaTitle').textContent = title;
        document.getElementById('ncRefId').value = '';
        document.getElementById('ncComment').value = '';
        document.getElementById('ncFile').value = '';
        var btnAdd = document.getElementById('btnNcAdd');
        btnAdd.textContent = 'Agregar no conformidad';
        btnAdd.removeAttribute('data-edit-id');
        loadAuditoriaDocs();
        loadNoconformidades();
        modalAuditoria.show();
    }
    function loadAuditoriaDocs() {
        var nodeId = currentAuditoriaTipo === 'interna' ? 'auditoria_interna' : 'auditoria_externa';
        fetch(api('documents.php') + '?nodeId=' + encodeURIComponent(nodeId), fetchOptions())
            .then(function (r) { return r.json(); })
            .then(function (docs) {
                var tbody = document.getElementById('auditoria-docs-tbody');
                tbody.innerHTML = '';
                docs.forEach(function (d) {
                    tbody.innerHTML += '<tr><td>' + escapeHtml(d.originalName) + '</td><td>' + formatSize(d.size) + '</td><td>' + formatDate(d.createdAt) + '</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-aud-del" data-id="' + escapeHtml(d.id) + '">Eliminar</button></td></tr>';
                });
                tbody.querySelectorAll('.btn-aud-del').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('¿Eliminar este documento?')) return;
                        deleteAuditoriaDoc(this.getAttribute('data-id'));
                    });
                });
            });
    }
    function deleteAuditoriaDoc(docId) {
        fetch(api('document_delete.php'), fetchOptions({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ documentId: docId })
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data && data.ok) loadAuditoriaDocs(); });
    }
    document.getElementById('btnAuditoriaSelectFiles').addEventListener('click', function () {
        document.getElementById('auditoriaFiles').click();
    });
    document.getElementById('auditoriaFiles').addEventListener('change', function () {
        var files = this.files;
        if (!files || !files.length) return;
        var nodeId = currentAuditoriaTipo === 'interna' ? 'auditoria_interna' : 'auditoria_externa';
        var inputEl = this;
        fetch(api('documents.php') + '?nodeId=' + encodeURIComponent(nodeId), fetchOptions())
            .then(function (r) { return r.json(); })
            .then(function (docs) {
                var maxNew = Math.max(0, 2 - docs.length);
                if (files.length > maxNew) {
                    showAlert('Solo se permiten 2 documentos. Suba como máximo ' + maxNew + ' más.');
                    inputEl.value = '';
                    return;
                }
                var fd = new FormData();
                fd.append('nodeId', nodeId);
                for (var i = 0; i < files.length && i < maxNew; i++) fd.append('files[]', files[i]);
                document.getElementById('auditoria-docs-loading').style.display = 'inline-block';
                fetch(api('documents.php'), fetchOptions({ method: 'POST', body: fd }))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        document.getElementById('auditoria-docs-loading').style.display = 'none';
                        inputEl.value = '';
                        if (data && data.added && data.added.length) loadAuditoriaDocs();
                        if (data && data.errors && data.errors.length) showAlert(data.errors.join('; '));
                    })
                    .catch(function () { document.getElementById('auditoria-docs-loading').style.display = 'none'; });
            });
        inputEl.value = '';
    });

    function loadNoconformidades() {
        document.getElementById('noconformidades-tbody').innerHTML = '<tr><td colspan="4" class="text-muted text-center">Cargando...</td></tr>';
        fetch(api('noconformidades.php') + '?tipo=' + encodeURIComponent(currentAuditoriaTipo), fetchOptions())
            .then(function (r) { return r.json(); })
            .then(function (list) {
                var tbody = document.getElementById('noconformidades-tbody');
                tbody.innerHTML = '';
                if (!list || list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No hay no conformidades registradas.</td></tr>';
                } else {
                    list.forEach(function (nc) {
                        var docCell = nc.originalName ? escapeHtml(nc.originalName) + ' <a href="' + api('document_inline.php') + '?id=' + encodeURIComponent(nc.documentId) + '" target="_blank" class="small">Ver</a>' : '—';
                        tbody.innerHTML += '<tr data-nc-id="' + escapeHtml(nc.id) + '" data-nc-ref="' + escapeHtml(nc.refId || '') + '" data-nc-comment="' + escapeHtml(nc.comment || '') + '"><td>' + escapeHtml(nc.refId || '') + '</td><td>' + escapeHtml(nc.comment || '') + '</td><td>' + docCell + '</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary btn-nc-edit me-1">Editar</button><button type="button" class="btn btn-sm btn-outline-danger btn-nc-del">Eliminar</button></td></tr>';
                    });
                    tbody.querySelectorAll('.btn-nc-del').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (!confirm('¿Eliminar esta no conformidad?')) return;
                            var id = this.closest('tr').getAttribute('data-nc-id');
                            deleteNoconformidad(id);
                        });
                    });
                    tbody.querySelectorAll('.btn-nc-edit').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var tr = this.closest('tr');
                            document.getElementById('ncRefId').value = tr.getAttribute('data-nc-ref') || '';
                            document.getElementById('ncComment').value = tr.getAttribute('data-nc-comment') || '';
                            document.getElementById('btnNcAdd').setAttribute('data-edit-id', tr.getAttribute('data-nc-id'));
                            document.getElementById('btnNcAdd').textContent = 'Guardar cambios';
                        });
                    });
                }
            })
            .catch(function () {
                document.getElementById('noconformidades-tbody').innerHTML = '<tr><td colspan="4" class="text-danger text-center">Error al cargar.</td></tr>';
            });
    }
    function deleteNoconformidad(id) {
        fetch(api('noconformidades.php'), fetchOptions({
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, tipo: currentAuditoriaTipo })
        }))
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data && data.ok) loadNoconformidades(); });
    }
    document.getElementById('btnNcSelectFile').addEventListener('click', function () {
        document.getElementById('ncFile').click();
    });
    document.getElementById('btnNcAdd').addEventListener('click', function () {
        var refId = document.getElementById('ncRefId').value.trim();
        var comment = document.getElementById('ncComment').value.trim();
        var fileInput = document.getElementById('ncFile');
        var editId = this.getAttribute('data-edit-id');
        if (editId) {
            fetch(api('noconformidades.php'), fetchOptions({
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: editId, tipo: currentAuditoriaTipo, refId: refId, comment: comment })
            }))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        document.getElementById('ncRefId').value = '';
                        document.getElementById('ncComment').value = '';
                        document.getElementById('ncFile').value = '';
                        document.getElementById('btnNcAdd').removeAttribute('data-edit-id');
                        document.getElementById('btnNcAdd').textContent = 'Agregar no conformidad';
                        loadNoconformidades();
                    } else if (data && data.error) showAlert(data.error);
                });
            return;
        }
        if (!refId && !comment && (!fileInput.files || !fileInput.files.length)) {
            showAlert('Indique al menos ID, comentario o adjunte un PDF.');
            return;
        }
        var fd = new FormData();
        fd.append('tipo', currentAuditoriaTipo);
        fd.append('refId', refId);
        fd.append('comment', comment);
        if (fileInput.files && fileInput.files.length) fd.append('file', fileInput.files[0]);
        document.getElementById('nc-loading').style.display = 'block';
        document.getElementById('nc-loading').textContent = 'Guardando...';
        fetch(api('noconformidades.php'), fetchOptions({ method: 'POST', body: fd }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                document.getElementById('nc-loading').style.display = 'none';
                if (data && data.ok) {
                    document.getElementById('ncRefId').value = '';
                    document.getElementById('ncComment').value = '';
                    document.getElementById('ncFile').value = '';
                    loadNoconformidades();
                } else if (data && data.error) showAlert(data.error);
            })
            .catch(function () { document.getElementById('nc-loading').style.display = 'none'; });
    });

    document.getElementById('modalNodes').addEventListener('hidden.bs.modal', function () {
        nodesStack = [];
    });

    document.getElementById('modalDocuments').addEventListener('hidden.bs.modal', function () {
        if (nodesStack.length > 0) {
            loadNodesInModal();
            modalNodes.show();
        }
    });

    loadSections();
})();
