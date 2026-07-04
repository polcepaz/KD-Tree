<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busqueda por Rango — KD-Tree</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css?v=14">
    <script src="js/theme.js"></script>
    <script src="js/fontsize.js"></script>
</head>
<body>

<nav class="sidebar d-flex flex-column">
    <div class="sidebar-logo"><h1>KD-Tree</h1><small>Busqueda por Rango</small></div>
    <ul class="nav flex-column mb-auto">
        <li class="nav-item"><span class="sidebar-heading">Navegacion</span></li>
        <li class="nav-item"><a class="nav-link" href="index.php"><span class="icon">⊞</span><span>Visualizador</span></a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="nn.php"><span class="icon">🔍</span><span>Vecino Cercano</span></a></li>
        <li class="nav-item"><a class="nav-link active" href="rango.php"><span class="icon">🎯</span><span>Busqueda por Rango</span></a></li>
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
        <span class="badge-kyndryl">Busqueda por Rango</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="small text-secondary">Encuentra servidores con metricas similares dentro de un radio</span>
    </div>
</header>

<main class="main-content p-3">
    <div class="card-dash mb-3">
        <div class="card-header">Parametros de Busqueda</div>
        <div class="p-2">
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">CPU (%)</label>
                    <input type="number" class="form-control form-control-dash" id="rsCpu" step="0.1" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">Memoria (%)</label>
                    <input type="number" class="form-control form-control-dash" id="rsMem" step="0.1" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">HANA (%)</label>
                    <input type="number" class="form-control form-control-dash" id="rsHana" step="0.1" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">DRT (ms)</label>
                    <input type="number" class="form-control form-control-dash" id="rsDrt" step="1" placeholder="0-5000">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">Work Procs</label>
                    <input type="number" class="form-control form-control-dash" id="rsWp" placeholder="0-100">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label small">Enqueue Locks</label>
                    <input type="number" class="form-control form-control-dash" id="rsEl" placeholder="0-500">
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small">Radio de busqueda</label>
                    <input type="number" class="form-control form-control-dash" id="rangeRadius" step="1" min="1" value="50" placeholder="Radio">
                </div>
            </div>
            <button class="btn btn-kyndryl btn-sm" id="btnRangeSearch">Buscar por Rango</button>
        </div>
    </div>

    <div class="card-dash">
        <div class="card-header">Resultados</div>
        <div id="rangeResult" class="p-2">
            <div class="text-secondary text-center py-4 small">Complete los campos y presione "Buscar por Rango"</div>
        </div>
    </div>
</main>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="js/renderer.js?v=19"></script>
<script>
(function () {
    var byId = function (id) { return document.getElementById(id); };

    function getFormData() {
        var ids = ['rsCpu','rsMem','rsHana','rsDrt','rsWp','rsEl'];
        var keys = ['cpu_usage','memory_usage','hana_memory','dialog_response_time','work_processes','enqueue_locks'];
        var data = {};
        for (var i = 0; i < ids.length; i++) {
            var val = byId(ids[i])?.value;
            if (!val) { alert('Complete el campo ' + keys[i]); return null; }
            data[keys[i]] = val;
        }
        var radius = byId('rangeRadius')?.value;
        if (!radius || radius <= 0) { alert('Radio invalido'); return null; }
        data.radius = parseFloat(radius);
        return data;
    }

    byId('btnRangeSearch')?.addEventListener('click', function () {
        var data = getFormData();
        if (!data) return;
        var el = byId('rangeResult');
        if (el) el.innerHTML = '<div class="text-secondary small">Buscando...</div>';

        fetch('php/rangeSearch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
        .then(function (r) { return r.json(); })
        .then(function (response) {
            if (response.success && response.result) {
                var r = response.result;
                var h = '<div class="alert alert-success py-1 small">' + r.total_found + ' servidores encontrados (radio: ' + data.radius + ')</div>';
                h += '<div class="small" style="color:var(--bs-secondary-color);margin-bottom:0.5rem;">';
                h += 'Tiempo: ' + r.time.toFixed(3) + 'ms | ' + r.comparisons + ' comparaciones | ' + r.visited + ' nodos visitados';
                h += '</div>';
                if (r.points_found && r.points_found.length > 0) {
                    h += '<div style="overflow-x:auto;"><table class="table table-dash table-hover mb-0" style="font-size:.8rem;">';
                    h += '<thead><tr><th>Servidor</th><th>Distancia</th><th>CPU</th><th>Mem</th><th>HANA</th><th>DRT</th><th>WP</th><th>EL</th></tr></thead><tbody>';
                    for (var i = 0; i < Math.min(r.points_found.length, 100); i++) {
                        var p = r.points_found[i].point;
                        h += '<tr><td>' + p.server_name + '</td><td>' + r.points_found[i].distance.toFixed(1) + '</td>';
                        h += '<td>' + p.cpu_usage + '%</td><td>' + p.memory_usage + '%</td><td>' + p.hana_memory + '%</td>';
                        h += '<td>' + p.dialog_response_time + 'ms</td><td>' + p.work_processes + '</td><td>' + p.enqueue_locks + '</td></tr>';
                    }
                    h += '</tbody></table></div>';
                }
                if (el) el.innerHTML = h;
            } else {
                if (el) el.innerHTML = '<div class="alert alert-danger small">Error en la busqueda</div>';
            }
        })
        .catch(function () { if (el) el.innerHTML = '<div class="alert alert-danger small">Error de conexion</div>'; });
    });
})();
</script>
</body>
</html>
