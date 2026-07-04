/**
 * Aplicacion principal KD-Tree SAP Monitor.
 */
const App = {
    state: {
        treeBuilt: false,
        treeData: null,
        currentRecords: [],
        selectedRecord: null,
    },

    init: function () {
        this.loadRecords();
        this.checkTreeStatus();
        this.bindEvents();
        this.initSidebarToggle();
        window.addEventListener('resize', () => this.syncStatsHeight());
        this.syncStatsHeight();
    },

    syncStatsHeight: function () {
        if (window.innerWidth < 992) {
            var card = document.getElementById('statsCard');
            if (card) card.style.height = '';
            return;
        }
        var leftCard = document.querySelector('.col-lg-8 > .card-dash');
        var statsCard = document.getElementById('statsCard');
        if (!leftCard || !statsCard) return;
        statsCard.style.height = leftCard.offsetHeight + 'px';
    },

    initSidebarToggle: function () {
        const btn = document.getElementById('btnSidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        if (!btn || !overlay) return;

        const close = () => { document.body.classList.remove('sidebar-open'); };

        btn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
        overlay.addEventListener('click', close);

        // Cerrar sidebar al hacer clic en links del sidebar en mobile
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) close();
            });
        });

        // Cerrar sidebar al redimensionar a desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) close();
        });
    },

    bindEvents: function () {
        document.getElementById('btnBuildTree')?.addEventListener('click', () => {
            this.openRebuildModal();
        });
        document.getElementById('btnConfirmRebuild')?.addEventListener('click', () => {
            if (this.state.treeBuilt) this.rebuildTree();
            else this.buildTree();
        });
        document.getElementById('btnSearchNN')?.addEventListener('click', () => this.searchNearestNeighbor());
        document.getElementById('btnRangeSearch')?.addEventListener('click', () => this.searchRange());
        document.getElementById('btnInsert')?.addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('modalInsert'));
            document.getElementById('insertServerName').value = 'SRV_' + Date.now().toString(36).toUpperCase();
            modal.show();
        });
        document.getElementById('formInsert')?.addEventListener('submit', (e) => { e.preventDefault(); this.insertRecord(); });
        document.getElementById('btnDelete')?.addEventListener('click', () => this.deleteRecord());
        document.getElementById('btnFillSearch')?.addEventListener('click', () => this.fillSearchForm());

        // Playback controls
        document.getElementById('btnStepBack')?.addEventListener('click', () => Renderer.goToStep(Renderer.currentStepIndex - 1));
        document.getElementById('btnStepFwd')?.addEventListener('click', () => Renderer.goToStep(Renderer.currentStepIndex + 1));
        document.getElementById('btnPlay')?.addEventListener('click', () => Renderer.play());
        document.getElementById('btnPause')?.addEventListener('click', () => Renderer.pause());
        document.getElementById('btnReset')?.addEventListener('click', () => Renderer.reset());

        // Speed slider
        const speedRange = document.getElementById('speedRange');
        const speedLabel = document.getElementById('speedLabel');
        if (speedRange && speedLabel) {
            speedRange.addEventListener('input', () => {
                const val = parseInt(speedRange.value);
                speedLabel.textContent = val;
                Renderer.playbackSpeed = 1100 - val * 90;
                if (Renderer.playing) { Renderer.pause(); Renderer.play(); }
            });
        }

        // Busqueda por ID en tabla
        document.getElementById('searchRecordInput')?.addEventListener('input', (e) => {
            this.filterRecords(e.target.value);
        });

        // Click en fila de tabla
        document.getElementById('recordsTableBody')?.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (row && row.dataset.id) this.selectRecord(parseInt(row.dataset.id));
        });
    },

    checkTreeStatus: function () {
        fetch('php/statistics.php?action=status')
            .then(r => r.json())
            .then(data => {
                this.state.treeBuilt = data.tree_built;
                if (data.total_records) {
                    const input = document.getElementById('rebuildNodeCount');
                    if (input) input.max = data.total_records;
                }
                if (data.tree_built) {
                    this.state.treeData = data.tree;
                    this.updateStats(data.statistics);
                    Renderer.clearCustomPositions();
                    Renderer.renderTree(data.tree);
                    this.syncStatsHeight();
                    document.getElementById('btnBuildTree').innerHTML = '<span class="icon">▶</span><span>Reconstruir</span>';
                    const badge = document.getElementById('treeBadge');
                    if (badge) { badge.textContent = 'Construido'; badge.className = 'badge bg-success'; }
                    // Generar pasos desde la estructura del arbol
                    const steps = this.generateBuildStepsFromTree(data.tree, data.statistics);
                    Renderer.setSteps(steps, false);
                }
            })
            .catch(err => console.error('Error:', err));
    },

    generateBuildStepsFromTree: function (tree, stats) {
        const steps = [];
        if (!tree) return steps;

        // Primer paso con la raiz resaltada
        const rootId = tree.id;
        steps.push({
            phase: 'split', nodeId: rootId, nodeIds: [rootId],
            visitedIds: [rootId], prunedIds: [], bestId: null,
            message: `<strong>Raiz</strong> — ${tree.name || 'ID:' + rootId} — corte por d${tree.dim} en ${tree.split?.toFixed(1) ?? '?'}` +
                (tree.children?.length ? ` → ${tree.children.length} hijos` : ' → hoja'),
            dim: tree.dim, splitVal: tree.split,
        });

        // Recorrer el resto del arbol
        const traverse = (node) => {
            if (!node) return;
            if (node.children) node.children.forEach(child => {
                steps.push({
                    phase: 'split', nodeId: child.id, nodeIds: child.id ? [child.id] : [],
                    visitedIds: [...steps[steps.length - 1]?.visitedIds ?? [], child.id].filter(Boolean),
                    prunedIds: [], bestId: null,
                    message: `Nodo <strong>${child.name || child.id}</strong> — corte por d${child.dim} en ${child.split?.toFixed(1) ?? '?'}` +
                        (child.children?.length ? ` → ${child.children.length} hijos` : ' → hoja'),
                    dim: child.dim, splitVal: child.split,
                });
                traverse(child);
            });
        };
        traverse(tree);
        return steps;
    },

    loadRecords: function (page = 1) {
        const loading = document.getElementById('recordsLoading');
        if (loading) loading.style.display = 'block';
        fetch(`php/getRecords.php?page=${page}&per_page=5000`)
            .then(r => r.json())
            .then(data => {
                this.state.currentRecords = data.data || [];
                this.renderRecordsTable(data);
                if (loading) loading.style.display = 'none';
            })
            .catch(err => { console.error('Error:', err); if (loading) loading.classList.remove('active'); });
    },

    renderRecordsTable: function (data) {
        const tbody = document.getElementById('recordsTableBody');
        if (!tbody) return;
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-secondary">Sin registros</td></tr>';
            return;
        }
        tbody.innerHTML = data.data.map(row => `
            <tr data-id="${row.id}" class="${this.state.selectedRecord?.id === row.id ? 'table-active' : ''}">
                <td>${row.id}</td>
                <td>${this.escapeHtml(row.server_name)}</td>
                <td>${row.cpu_usage}%</td>
                <td>${row.memory_usage}%</td>
            </tr>
        `).join('');
    },

    filterRecords: function (query) {
        const tbody = document.getElementById('recordsTableBody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('tr');
        const q = query.trim().toLowerCase();
        rows.forEach(row => {
            const id = row.dataset.id;
            if (!q) { row.style.display = ''; return; }
            row.style.display = id && id.includes(q) ? '' : 'none';
        });
    },

    selectRecord: function (id) {
        const record = this.state.currentRecords.find(r => r.id === id);
        if (record) {
            this.state.selectedRecord = record;
            document.getElementById('nnCpu').value = record.cpu_usage;
            document.getElementById('nnMem').value = record.memory_usage;
            document.getElementById('nnHana').value = record.hana_memory;
            document.getElementById('nnDrt').value = record.dialog_response_time;
            document.getElementById('nnWp').value = record.work_processes;
            document.getElementById('nnEl').value = record.enqueue_locks;
            document.querySelectorAll('#recordsTableBody tr').forEach(row => {
                row.classList.toggle('table-active', parseInt(row.dataset.id) === id);
            });
        }
    },

    buildTree: function () {
        const nodeCount = parseInt(document.getElementById('rebuildNodeCount')?.value) || 0;
        const btn = document.getElementById('btnBuildTree');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Construyendo...';

        let url = 'php/buildTree.php';
        const params = { method: 'POST' };
        if (nodeCount > 0) url += '?nodes=' + nodeCount;

        fetch(url, params)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.state.treeBuilt = true;
                    this.state.treeData = data.tree;
                    this.updateStats(data.statistics);
                    Renderer.clearCustomPositions();
                    Renderer.renderTree(data.tree);
                    this.syncStatsHeight();
                    btn.innerHTML = '<span class="icon">▶</span><span>Reconstruir</span>';
                    const badge = document.getElementById('treeBadge');
                    if (badge) { badge.textContent = 'Construido'; badge.className = 'badge bg-success'; }
                    this.showAlert(`Arbol construido: ${data.size} nodos en ${data.build_time.toFixed(2)}ms`, 'success');
                    const steps = this.generateBuildStepsFromTree(data.tree, data.statistics);
                    Renderer.setSteps(steps, false);
                    if (data.event_log) {
                        document.dispatchEvent(new CustomEvent('kdtree:event', { detail: { events: data.event_log } }));
                    }
                } else {
                    this.showAlert('Error: ' + (data.error || ''), 'error');
                }
                btn.disabled = false;
            })
            .catch(err => {
                this.showAlert('Error de conexion', 'error');
                btn.disabled = false;
                btn.textContent = 'Construir KD-Tree';
            });
    },

    rebuildTree: function () {
        if (!this.state.treeBuilt) return this.showAlert('Construya el arbol primero', 'error');
        const nodeCount = parseInt(document.getElementById('rebuildNodeCount')?.value) || 0;
        if (nodeCount < 1) return this.showAlert('Ingrese un numero valido de nodos', 'error');

        const btn = document.getElementById('btnRebuildTree');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Reconstruyendo...';

        fetch(`php/statistics.php?action=rebuild&nodes=${nodeCount}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.state.treeData = data.tree;
                    this.updateStats(data.statistics);
                    Renderer.clearCustomPositions();
                    Renderer.renderTree(data.tree);
                    this.syncStatsHeight();
                    const rebuildTime = data.rebuild_time ?? data.build_time;
                    this.showAlert(`Reconstruido con ${data.size} nodos en ${rebuildTime.toFixed(2)}ms`, 'success');
                    const steps = this.generateBuildStepsFromTree(data.tree, data.statistics);
                    Renderer.setSteps(steps, false);
                    if (data.event_log) {
                        document.dispatchEvent(new CustomEvent('kdtree:event', { detail: { events: data.event_log } }));
                    }
                } else {
                    this.showAlert('Error: ' + (data.error || ''), 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Reconstruir';
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Reconstruir'; this.showAlert('Error de conexion al reconstruir', 'error'); });
    },

    searchNearestNeighbor: function () {
        if (!this.state.treeBuilt) return this.showAlert('Construya el arbol primero', 'error');
        const data = this.getNNFormData();
        if (!data) return;

        const btn = document.getElementById('btnSearchNN');
        btn.disabled = true;
        btn.textContent = 'Buscando...';

        fetch('php/nearestNeighbor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .then(r => r.json())
            .then(response => {
                if (response.success && response.result) {
                    const result = response.result;
                    this.displayNeighborResult(result);
                    if (result.neighbor) Renderer.highlightNode(result.neighbor.id);
                    this.showAlert(`Vecino: ${result.neighbor?.server_name || 'N/A'} (dist: ${result.distance.toFixed(2)})`, 'success');
                    if (result.event_log) {
                        document.dispatchEvent(new CustomEvent('kdtree:event', { detail: { events: result.event_log } }));
                        // Cargar pasos de busqueda con reproduccion automatica
                        const steps = KDEngine.searchSteps(result.event_log);
                        Renderer.setSteps(steps, true);
                    }
                } else {
                    this.showAlert('Error en la busqueda', 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Buscar Vecino';
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Buscar Vecino'; });
    },

    searchRange: function () {
        if (!this.state.treeBuilt) return this.showAlert('Construya el arbol primero', 'error');
        const data = this.getRangeFormData();
        if (!data) return;

        const btn = document.getElementById('btnRangeSearch');
        btn.disabled = true;
        btn.textContent = 'Buscando...';

        fetch('php/rangeSearch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .then(r => r.json())
            .then(response => {
                if (response.success && response.result) {
                    this.displayRangeResult(response.result);
                    this.showAlert(`${response.result.total_found} puntos encontrados`, 'success');
                    if (response.result.event_log) {
                        document.dispatchEvent(new CustomEvent('kdtree:event', { detail: { events: response.result.event_log } }));
                        const steps = KDEngine.rangeSteps(response.result.event_log);
                        Renderer.setSteps(steps, true);
                    }
                }
                btn.disabled = false;
                btn.textContent = 'Buscar por Rango';
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Buscar por Rango'; });
    },

    displayNeighborResult: function (result) {
        const el = document.getElementById('searchResult');
        if (!el) return;
        if (!result.neighbor) {
            el.innerHTML = '<div class="alert alert-info py-2 small">Sin resultados</div>'; return;
        }
        el.innerHTML = `
            <div class="comparison-card mt-2">
                <div class="algo kdtree">
                    <div class="algo-name">Vecino mas cercano</div>
                    <div class="algo-time">${result.distance.toFixed(2)}</div>
                    <div class="algo-detail">Distancia Euclidiana</div>
                    <div class="text-start mt-2 small px-2" style="background:rgba(0,0,0,0.3);border-radius:4px;">
                        <strong class="text-info">${result.neighbor.server_name}</strong><br>
                        CPU:${result.neighbor.cpu_usage}% Mem:${result.neighbor.memory_usage}% HANA:${result.neighbor.hana_memory}%<br>
                        DRT:${result.neighbor.dialog_response_time}ms WP:${result.neighbor.work_processes} EL:${result.neighbor.enqueue_locks}
                    </div>
                </div>
                <div class="algo kdtree">
                    <div class="algo-name">Metricas</div>
                    <div class="algo-time">${result.time.toFixed(3)}ms</div>
                    <div class="algo-detail">${result.comparisons} comparaciones</div>
                    <div class="algo-detail">${result.visited} nodos visitados</div>
                </div>
            </div>`;
    },

    displayRangeResult: function (result) {
        const el = document.getElementById('rangeResult');
        if (!el) return;
        if (!result.points_found || result.points_found.length === 0) {
            el.innerHTML = '<div class="alert alert-info py-2 small">Sin resultados en el rango</div>'; return;
        }
        let html = `<div class="alert alert-success py-1 small">${result.total_found} encontrados | ${result.time.toFixed(3)}ms | ${result.comparisons} comp.</div>`;
        html += '<div class="table-container" style="max-height:200px;overflow-y:auto;"><table class="table table-dark table-sm table-hover mb-0"><thead><tr><th>Servidor</th><th>Dist</th><th>CPU</th><th>Mem</th></tr></thead><tbody>';
        result.points_found.slice(0, 30).forEach(p => {
            html += `<tr><td>${this.escapeHtml(p.point.server_name)}</td><td>${p.distance.toFixed(1)}</td><td>${p.point.cpu_usage}%</td><td>${p.point.memory_usage}%</td></tr>`;
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
    },

    insertRecord: function () {
        const data = {
            server_name: document.getElementById('insertServerName').value,
            cpu_usage: document.getElementById('insertCpu').value,
            memory_usage: document.getElementById('insertMem').value,
            hana_memory: document.getElementById('insertHana').value,
            dialog_response_time: document.getElementById('insertDrt').value,
            work_processes: document.getElementById('insertWp').value,
            enqueue_locks: document.getElementById('insertEl').value,
        };
        const serverName = data.server_name;
        const btn = document.querySelector('#formInsert button[type="submit"]');
        btn.disabled = true; btn.textContent = '...';

        fetch('php/insertPoint.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .then(r => r.json())
            .then(response => {
                if (response.success) {
                    this.showAlert(`Insertado ID:${response.record.id}`, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalInsert')).hide();
                    const events = [
                        { type: 'INSERT', description: `Insertando punto '${serverName}' (ID=${response.record.id}) en el arbol`, timestamp: Date.now() / 1000 },
                        { type: 'INSERT', description: `Insercion completada. Punto '${serverName}' agregado al KD-Tree`, timestamp: Date.now() / 1000 },
                    ];
                    if (response.tree_update?.tree_size) {
                        events.push({ type: 'INSERT', description: `Tamano actual del arbol: ${response.tree_update.tree_size} nodos`, timestamp: Date.now() / 1000 });
                    }
                    document.dispatchEvent(new CustomEvent('kdtree:event', { detail: { events } }));
                    this.state.selectedRecord = null;
                    this.loadRecords();
                    if (this.state.treeBuilt) this.checkTreeStatus();
                    this.loadRecords();
                    if (this.state.treeBuilt) this.checkTreeStatus();
                } else {
                    this.showAlert('Error: ' + (response.error || ''), 'error');
                }
                btn.disabled = false; btn.textContent = 'Insertar';
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Insertar'; });
    },

    openRebuildModal: function () {
        const total = this.state.currentRecords.length || 0;
        const input = document.getElementById('rebuildNodeCount');
        const info = document.getElementById('rebuildInfo');
        if (input) { input.max = total; input.value = Math.min(parseInt(input.value) || total, total); }
        if (info) info.textContent = `Total disponible: ${total} registros. Selecciona cuantos nodos usar.`;
        const modal = new bootstrap.Modal(document.getElementById('modalRebuild'));
        modal.show();
    },

    deleteRecord: function () {
        if (!this.state.selectedRecord) return this.showAlert('Seleccione un registro', 'error');
        if (!confirm(`Eliminar "${this.state.selectedRecord.server_name}"?`)) return;
        const recordName = this.state.selectedRecord.server_name;
        const recordId = this.state.selectedRecord.id;
        const btn = document.getElementById('btnDelete');
        btn.disabled = true; btn.textContent = '...';

        fetch('php/deletePoint.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: recordId }),
        })
            .then(r => r.json())
            .then(response => {
                if (response.success) {
                    this.showAlert('Eliminado', 'success');
                    const events = [
                        { type: 'DELETE', description: `Iniciando eliminacion del nodo '${recordName}' (ID=${recordId})`, timestamp: Date.now() / 1000 },
                        { type: 'DELETE', description: `Nodo '${recordName}' eliminado. Arbol reconstruido.`, timestamp: Date.now() / 1000 },
                    ];
                    document.dispatchEvent(new CustomEvent('kdtree:event', { detail: { events } }));
                    this.state.selectedRecord = null;
                    this.loadRecords();
                    if (this.state.treeBuilt) this.checkTreeStatus();
                }
                btn.disabled = false; btn.textContent = 'Eliminar';
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Eliminar'; });
    },

    fillSearchForm: function () {
        if (!this.state.selectedRecord) return this.showAlert('Seleccione un registro', 'error');
        const r = this.state.selectedRecord;
        document.getElementById('nnCpu').value = r.cpu_usage;
        document.getElementById('nnMem').value = r.memory_usage;
        document.getElementById('nnHana').value = r.hana_memory;
        document.getElementById('nnDrt').value = r.dialog_response_time;
        document.getElementById('nnWp').value = r.work_processes;
        document.getElementById('nnEl').value = r.enqueue_locks;
    },

    getNNFormData: function () {
        const ids = ['nnCpu','nnMem','nnHana','nnDrt','nnWp','nnEl'];
        const keys = ['cpu_usage','memory_usage','hana_memory','dialog_response_time','work_processes','enqueue_locks'];
        const data = {};
        for (let i = 0; i < ids.length; i++) {
            const val = document.getElementById(ids[i])?.value;
            if (!val) { this.showAlert(`Complete campo ${keys[i]}`, 'error'); return null; }
            data[keys[i]] = val;
        }
        return data;
    },

    getRangeFormData: function () {
        const ids = ['rsCpu','rsMem','rsHana','rsDrt','rsWp','rsEl'];
        const keys = ['cpu_usage','memory_usage','hana_memory','dialog_response_time','work_processes','enqueue_locks'];
        const data = {};
        for (let i = 0; i < ids.length; i++) {
            const val = document.getElementById(ids[i])?.value;
            if (!val) { this.showAlert(`Complete campo ${keys[i]}`, 'error'); return null; }
            data[keys[i]] = val;
        }
        const radius = document.getElementById('rangeRadius')?.value;
        if (!radius || radius <= 0) { this.showAlert('Radio invalido', 'error'); return null; }
        data.radius = parseFloat(radius);
        return data;
    },

    updateStats: function (stats) {
        if (!stats) return;
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? '0'; };
        set('statTotalNodes', stats.total_nodes);
        set('statTotalNodes2', stats.total_nodes);
        set('statHeight', stats.height);
        set('statHeight2', stats.height);
        set('statMaxDepth', stats.max_depth);
        set('statMinDepth', stats.min_depth);
        set('statAvgDepth', stats.avg_depth?.toFixed(2));
        set('statLeafNodes', stats.leaf_nodes);
        set('statInternalNodes', stats.internal_nodes);
        set('statDimensions', stats.dimensions);
        set('statDimensions2', stats.dimensions);
        set('statComplexityTime', stats.complexity_time);
        set('statComplexitySpace', stats.complexity_space);
        set('statMemory', stats.memory_estimate);
    },

    showAlert: function (message, type = 'info') {
        const container = document.getElementById('alerts');
        if (!container) return;
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'error' ? 'danger' : type} py-2 small d-flex justify-content-between align-items-center`;
        alert.innerHTML = `${message} <button type="button" class="btn-close btn-close-white btn-sm" onclick="this.parentElement.remove()"></button>`;
        container.appendChild(alert);
        setTimeout(() => { alert.style.opacity = '0'; alert.style.transition = 'opacity 0.5s'; setTimeout(() => alert.remove(), 500); }, 4000);
    },

    escapeHtml: function (text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
};

document.addEventListener('DOMContentLoaded', () => App.init());
