<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vecino Cercano — KD-Tree</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css?v=14">
    <script src="js/theme.js"></script>
    <script src="js/fontsize.js"></script>
</head>
<body>

<nav class="sidebar d-flex flex-column">
    <div class="sidebar-logo"><h1>KD-Tree</h1><small>Busqueda NN</small></div>
    <ul class="nav flex-column mb-auto">
        <li class="nav-item"><span class="sidebar-heading">Navegacion</span></li>
        <li class="nav-item"><a class="nav-link" href="index.php"><span class="icon">⊞</span><span>Visualizador</span></a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link active" href="nn.php"><span class="icon">🔍</span><span>Vecino Cercano</span></a></li>
        <li class="nav-item"><a class="nav-link" href="rango.php"><span class="icon">🎯</span><span>Busqueda por Rango</span></a></li>
        <li class="nav-item"><a class="nav-link" href="ayuda.php"><span class="icon">❓</span><span>Ayuda</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="small" style="opacity:.6;">Tam</span>
            <button class="fontsize-down">A-</button>
            <span id="fontsizeValue" class="small">100%</span>
            <button class="fontsize-up">A+</button>
        </div>
        <button class="theme-toggle w-100">☀</button>
    </div>
</nav>

<header class="navbar-theme px-3">
    <div class="d-flex align-items-center gap-2">
        <span class="badge-kyndryl">Vecino Cercano (NN)</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="small text-secondary">Busqueda del vecino mas cercano entre servidores SAP</span>
    </div>
</header>

<main class="main-content p-3">
    <div class="card-dash mb-3">
        <div class="card-header">Parametros del Servidor a Consultar</div>
        <div class="p-2">
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">CPU (%)</label>
                    <input type="number" class="form-control form-control-dash" id="nnCpu" step="0.1" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">Memoria (%)</label>
                    <input type="number" class="form-control form-control-dash" id="nnMem" step="0.1" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">HANA (%)</label>
                    <input type="number" class="form-control form-control-dash" id="nnHana" step="0.1" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">DRT (ms)</label>
                    <input type="number" class="form-control form-control-dash" id="nnDrt" step="1" placeholder="0-5000">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">Work Procs</label>
                    <input type="number" class="form-control form-control-dash" id="nnWp" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">Enqueue Locks</label>
                    <input type="number" class="form-control form-control-dash" id="nnEl" placeholder="0-500">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-kyndryl btn-sm" id="btnSearchNN">Buscar Vecino Cercano</button>
                <button class="btn btn-outline-kyndryl btn-sm" id="btnFillSearch">Usar seleccionado</button>
            </div>
        </div>
    </div>

    <div class="card-dash">
        <div class="card-header">Resultado de la Busqueda</div>
        <div id="searchResult" class="p-2">
            <div class="text-secondary text-center py-4 small">Complete los campos y presione "Buscar Vecino Cercano"</div>
        </div>
    </div>
</main>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="js/renderer.js?v=19"></script>
<script>
(function () {
    var byId = function (id) { return document.getElementById(id); };

    function getFormData() {
        var ids = ['nnCpu','nnMem','nnHana','nnDrt','nnWp','nnEl'];
        var keys = ['cpu_usage','memory_usage','hana_memory','dialog_response_time','work_processes','enqueue_locks'];
        var data = {};
        for (var i = 0; i < ids.length; i++) {
            var val = byId(ids[i])?.value;
            if (!val) { alert('Complete el campo ' + keys[i]); return null; }
            data[keys[i]] = val;
        }
        return data;
    }

    byId('btnSearchNN')?.addEventListener('click', function () {
        var data = getFormData();
        if (!data) return;
        var el = byId('searchResult');
        if (el) el.innerHTML = '<div class="text-secondary small">Buscando...</div>';

        fetch('php/nearestNeighbor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
        .then(function (r) { return r.json(); })
        .then(function (response) {
            if (response.success && response.result) {
                var r = response.result;
                var h = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;margin-top:0.5rem;">';
                if (r.neighbor) {
                    h += '<div style="padding:0.8rem;border-radius:6px;text-align:center;background:rgba(46,125,50,0.12);border:1px solid rgba(46,125,50,0.25);">';
                    h += '<div style="font-size:0.75rem;text-transform:uppercase;opacity:.6;">Servidor encontrado</div>';
                    h += '<div style="font-size:1.3rem;font-weight:700;color:#66bb6a;margin:0.3rem 0;">' + r.neighbor.server_name + '</div>';
                    h += '<div class="small" style="color:var(--bs-secondary-color);">Distancia: ' + r.distance.toFixed(4) + '</div>';
                    h += '<hr style="opacity:.2;">';
                    h += '<div class="small text-start" style="padding:0.3rem 0.5rem;background:var(--bs-tertiary-bg);border-radius:4px;">';
                    h += 'CPU: ' + r.neighbor.cpu_usage + '% | Mem: ' + r.neighbor.memory_usage + '%<br>';
                    h += 'HANA: ' + r.neighbor.hana_memory + '% | DRT: ' + r.neighbor.dialog_response_time + 'ms<br>';
                    h += 'WP: ' + r.neighbor.work_processes + ' | EL: ' + r.neighbor.enqueue_locks;
                    h += '</div></div>';
                } else {
                    h += '<div class="alert alert-info">Sin resultados</div>';
                }
                h += '<div style="padding:0.8rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);">';
                h += '<div style="font-size:0.75rem;text-transform:uppercase;opacity:.6;">Metricas de busqueda</div>';
                h += '<div style="font-size:1rem;font-weight:700;color:#66bb6a;margin-top:0.5rem;">' + r.time.toFixed(3) + 'ms</div>';
                h += '<div class="small" style="color:var(--bs-secondary-color);">' + r.comparisons + ' comparaciones</div>';
                h += '<div class="small" style="color:var(--bs-secondary-color);">' + r.visited + ' nodos visitados</div>';
                h += '</div></div>';
                if (el) el.innerHTML = h;
                if (r.neighbor && window.Renderer) Renderer.highlightNode(r.neighbor.id);
            } else {
                if (el) el.innerHTML = '<div class="alert alert-danger small">Error en la busqueda</div>';
            }
        })
        .catch(function () { if (el) el.innerHTML = '<div class="alert alert-danger small">Error de conexion</div>'; });
    });

    byId('btnFillSearch')?.addEventListener('click', function () {
        // Intentar cargar datos de un servidor aleatorio via getRecords
        fetch('php/getRecords.php?per_page=1&order=rand')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.data && data.data.length > 0) {
                    var row = data.data[0];
                    byId('nnCpu').value = row.cpu_usage;
                    byId('nnMem').value = row.memory_usage;
                    byId('nnHana').value = row.hana_memory;
                    byId('nnDrt').value = row.dialog_response_time;
                    byId('nnWp').value = row.work_processes;
                    byId('nnEl').value = row.enqueue_locks;
                }
            }).catch(function () {});
    });
})();
</script>
</body>
</html>
