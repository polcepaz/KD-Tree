<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — KD-Tree</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="fonts/fonts.css">
    <link rel="stylesheet" href="css/style.css?v=13">
    <script src="js/theme.js"></script>
    <script src="js/fontsize.js"></script>
</head>
<body>

<nav class="sidebar d-flex flex-column">
    <div class="sidebar-logo"><h1>KD-Tree</h1><small>Dashboard</small></div>
    <ul class="nav flex-column mb-auto">
        <li class="nav-item"><a class="nav-link" href="index.php"><span class="icon">⊞</span><span>Visualizador</span></a></li>
        <li class="nav-item"><a class="nav-link active" href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="nn.php"><span class="icon">🔍</span><span>Vecino Cercano</span></a></li>
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

<header class="navbar-theme">
    <div><span class="text-secondary small">📊 Dashboard de Rendimiento</span></div>
</header>

<main class="main-content p-3">
    <ul class="nav nav-tabs-dash nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-bench">Benchmark</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scal">Escalabilidad</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exp">Experimentos</button></li>
    </ul>

    <div class="tab-content">
        <!-- BENCHMARK -->
        <div class="tab-pane fade show active" id="tab-bench">
            <div class="card-dash mb-3">
                <div class="card-header">Benchmark Comparativo</div>
                <div class="p-2">
                    <label class="form-label small">Consultas</label>
                    <input type="number" class="form-control form-control-dash w-auto d-inline-block" id="benchQueries" value="50" style="width:80px;">
                    <small style="display:block;color:rgba(255,255,255,0.4);font-size:.7rem;margin-top:2px;">Numero de busquedas NN aleatorias para calcular el tiempo promedio. Min: 10, recomendado: 50+.</small>
                    <button class="btn btn-kyndryl btn-sm ms-2" id="btnBench">Ejecutar</button>
                </div>
                <div id="benchResult" class="p-2"></div>
            </div>
            <div class="card-dash"><div class="card-header">Grafico</div><div class="p-2"><canvas id="benchCanvas" style="width:100%;height:260px;"></canvas></div></div>
        </div>

        <!-- ESCALABILIDAD -->
        <div class="tab-pane fade" id="tab-scal">
            <div class="card-dash mb-3">
                <div class="card-header">Escalabilidad (100, 500, 1000, 3000, 5000)</div>
                <div class="p-2"><button class="btn btn-kyndryl btn-sm" id="btnScal">Ejecutar</button></div>
                <div id="scalResult" class="p-2"></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><div class="card-dash"><div class="card-header">Tiempo</div><div class="p-2"><canvas id="scalLineCanvas" style="width:100%;height:260px;"></canvas></div></div></div>
                <div class="col-md-6"><div class="card-dash"><div class="card-header">Speedup</div><div class="p-2"><canvas id="scalBarCanvas" style="width:100%;height:260px;"></canvas></div></div></div>
            </div>
        </div>

        <!-- EXPERIMENTOS -->
        <div class="tab-pane fade" id="tab-exp">
            <div class="card-dash mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span>Experimentos</span>
                    <span>
                        <button class="btn btn-outline-kyndryl btn-sm me-1" id="btnExpScal">Escalabilidad</button>
                        <button class="btn btn-outline-kyndryl btn-sm me-1" id="btnExpBench">Benchmark</button>
                        <button class="btn btn-outline-kyndryl btn-sm" id="btnExpDim">Variar Dimensiones</button>
                    </span>
                </div>
                <div id="expResult" class="p-2"><div class="text-secondary text-center py-4">Ejecuta un experimento</div></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><div class="card-dash"><div class="card-header">Grafico 1</div><div class="p-2"><canvas id="expCanvas1" style="width:100%;height:260px;"></canvas></div></div></div>
                <div class="col-md-6"><div class="card-dash"><div class="card-header">Grafico 2</div><div class="p-2"><canvas id="expCanvas2" style="width:100%;height:260px;"></canvas></div></div></div>
            </div>
        </div>
    </div>
</main>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script>
if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, r) {
        if (r > w / 2) r = w / 2; if (r > h / 2) r = h / 2;
        this.moveTo(x + r, y);
        this.arcTo(x + w, y, x + w, y + h, r);
        this.arcTo(x + w, y + h, x, y + h, r);
        this.arcTo(x, y + h, x, y, r);
        this.arcTo(x, y, x + w, y, r);
        return this;
    };
}
(function () {
    var byId = function (id) { return document.getElementById(id); };

    var animFrame = null;

    // Debug: verify DOM loaded
    var debugBtn = byId('btnBench');
    if (debugBtn) {
        // Button exists
    }

    function drawBar(canvasId, labels, values, title) {
        var c = byId(canvasId); if (!c) return;
        c.width = c.offsetWidth || 400; c.height = 260;
        var ctx = c.getContext('2d'), w = c.width, h = c.height;
        if (animFrame) cancelAnimationFrame(animFrame);

        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        var maxV = 0; for (var i = 0; i < values.length; i++) maxV = Math.max(maxV, values[i]); maxV = maxV * 1.2;
        var top = 40, bottom = 40, left = 55, right = 30;
        var pw = w - left - right, ph = h - top - bottom;
        var bw = pw / values.length * 0.55, gap = pw / values.length * 0.45;
        var colors = ['#dc3545', '#cfa144', '#28a745', '#0d6efd', '#ab47bc'];
        var textColor = isDark ? '#ddd' : '#444';
        var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.08)';
        var axisColor = isDark ? '#aaa' : '#666';
        var bgColor = isDark ? 'rgba(10,18,30,0.5)' : '#f8f9fa';

        function render(progress) {
            ctx.clearRect(0, 0, w, h);
            ctx.fillStyle = bgColor; ctx.fillRect(0, 0, w, h);

            var steps = 4;
            ctx.strokeStyle = gridColor; ctx.lineWidth = 1; ctx.setLineDash([3, 6]);
            for (var s = 0; s <= steps; s++) {
                var gy = top + ph - (ph * s / steps);
                ctx.beginPath(); ctx.moveTo(left, gy); ctx.lineTo(w - right, gy); ctx.stroke();
                ctx.fillStyle = axisColor; ctx.font = '10px Open Sans, sans-serif'; ctx.textAlign = 'right';
                ctx.fillText((maxV * s / steps / 1.2).toFixed(2), left - 5, gy + 3);
            }
            ctx.setLineDash([]);
            ctx.save(); ctx.translate(12, top + ph / 2); ctx.rotate(-Math.PI / 2);
            ctx.fillStyle = axisColor; ctx.font = '11px Open Sans, sans-serif'; ctx.textAlign = 'center';
            ctx.fillText('ms', 0, 0); ctx.restore();

            var easing = 1 - Math.pow(1 - progress, 1.5);
            var animateH = Math.min(1, easing);
            for (var i = 0; i < values.length; i++) {
                var fullH = (values[i] / maxV) * ph * animateH;
                var bx = left + i * (bw + gap) + gap / 2, by = top + ph - fullH;
                var grad = ctx.createLinearGradient(bx, by, bx, top + ph);
                var col = colors[i % colors.length];
                grad.addColorStop(0, col); grad.addColorStop(1, col + '66');
                ctx.fillStyle = grad;
                ctx.beginPath(); ctx.roundRect(bx, by, bw, Math.max(1, fullH), 4); ctx.fill();
                ctx.fillStyle = col; ctx.fillRect(bx, by, bw, 2);

                if (animateH > 0.9) {
                    ctx.fillStyle = textColor; ctx.font = 'bold 10px Open Sans, sans-serif'; ctx.textAlign = 'center';
                    ctx.fillText(values[i].toFixed(3), bx + bw / 2, by - 6);
                }
                ctx.fillStyle = axisColor; ctx.font = '10px Open Sans, sans-serif'; ctx.textAlign = 'center';
                ctx.fillText(labels[i], bx + bw / 2, h - bottom + 18);
            }
        }

        var start = performance.now(), dur = 600;
        function step(ts) { render(Math.min(1, (ts - start) / dur)); if (ts - start < dur) animFrame = requestAnimationFrame(step); }
        animFrame = requestAnimationFrame(step);
    }

    function drawLine(canvasId, labels, lines, title) {
        var c = byId(canvasId); if (!c) return;
        c.width = c.offsetWidth || 400; c.height = 260;
        var ctx = c.getContext('2d'), w = c.width, h = c.height;
        if (animFrame) cancelAnimationFrame(animFrame);

        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        var top = 40, bottom = 40, left = 55, right = 30;
        var pw = w - left - right, ph = h - top - bottom;
        var maxV = 0;
        for (var i = 0; i < lines.length; i++)
            for (var j = 0; j < lines[i].data.length; j++)
                maxV = Math.max(maxV, lines[i].data[j]);
        maxV = maxV * 1.2;
        var textColor = isDark ? '#ddd' : '#444';
        var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.08)';
        var axisColor = isDark ? '#aaa' : '#666';
        var bgColor = isDark ? 'rgba(10,18,30,0.5)' : '#f8f9fa';

        function render(progress) {
            ctx.clearRect(0, 0, w, h);
            ctx.fillStyle = bgColor; ctx.fillRect(0, 0, w, h);

            var steps = 4;
            ctx.strokeStyle = gridColor; ctx.lineWidth = 1; ctx.setLineDash([3, 6]);
            for (var s = 0; s <= steps; s++) {
                var gy = top + ph - (ph * s / steps);
                ctx.beginPath(); ctx.moveTo(left, gy); ctx.lineTo(w - right, gy); ctx.stroke();
                ctx.fillStyle = axisColor; ctx.font = '10px Open Sans, sans-serif'; ctx.textAlign = 'right';
                ctx.fillText((maxV * s / steps / 1.2).toFixed(2), left - 5, gy + 3);
            }
            ctx.setLineDash([]);
            ctx.save(); ctx.translate(12, top + ph / 2); ctx.rotate(-Math.PI / 2);
            ctx.fillStyle = axisColor; ctx.font = '11px Open Sans, sans-serif'; ctx.textAlign = 'center'; ctx.fillText('ms', 0, 0); ctx.restore();
            ctx.fillStyle = axisColor; ctx.font = '11px Open Sans, sans-serif'; ctx.textAlign = 'center';
            ctx.fillText('Puntos', w / 2, h - bottom + 22);

            var easing = 1 - Math.pow(1 - progress, 1.5);
            var pts = Math.min(1, easing);
            for (var i = 0; i < lines.length; i++) {
                var ln = lines[i], col = ln.color || '#0d6efd';
                var visiblePts = Math.floor(ln.data.length * pts);
                var ptsX = [], ptsY = [];

                for (var j = 0; j < visiblePts; j++) {
                    var x = left + (pw / (Math.max(1, ln.data.length - 1))) * j;
                    var y = top + ph - (ln.data[j] / maxV) * ph * pts;
                    ptsX.push(x); ptsY.push(y);
                }

                if (visiblePts > 1) {
                    ctx.beginPath();
                    ctx.moveTo(ptsX[0], top + ph);
                    ctx.lineTo(ptsX[0], ptsY[0]);
                    for (var j = 1; j < visiblePts; j++) ctx.lineTo(ptsX[j], ptsY[j]);
                    ctx.lineTo(ptsX[visiblePts - 1], top + ph);
                    ctx.fillStyle = col + '18'; ctx.fill();
                }

                ctx.strokeStyle = col; ctx.lineWidth = 2.5;
                if (ln.dash) { ctx.setLineDash(ln.dash); ctx.lineWidth = 1.5; }
                ctx.beginPath();
                for (var j = 0; j < visiblePts; j++) {
                    j === 0 ? ctx.moveTo(ptsX[j], ptsY[j]) : ctx.lineTo(ptsX[j], ptsY[j]);
                }
                ctx.stroke(); ctx.setLineDash([]);

                if (pts > 0.7) {
                    for (var j = 0; j < visiblePts; j++) {
                        ctx.beginPath(); ctx.arc(ptsX[j], ptsY[j], 4, 0, Math.PI * 2);
                        ctx.fillStyle = col; ctx.fill();
                        ctx.strokeStyle = isDark ? '#1a1d21' : '#fff'; ctx.lineWidth = 1.5; ctx.stroke();
                        ctx.fillStyle = textColor; ctx.font = '9px Open Sans, sans-serif'; ctx.textAlign = 'center';
                        ctx.fillText(ln.data[j].toFixed(4), ptsX[j], ptsY[j] - 10);
                    }
                }
            }

            for (var i = 0; i < labels.length; i++) {
                ctx.fillStyle = axisColor; ctx.font = '10px Open Sans, sans-serif'; ctx.textAlign = 'center';
                ctx.fillText(labels[i], left + (pw / (Math.max(1, labels.length - 1))) * i, h - bottom + 18);
            }

            ctx.fillStyle = textColor; ctx.font = 'bold 13px Open Sans, sans-serif'; ctx.textAlign = 'center';
            if (title) ctx.fillText(title, w / 2, 16);
        }

        var start = performance.now(), dur = 600;
        function step(ts) { render(Math.min(1, (ts - start) / dur)); if (ts - start < dur) animFrame = requestAnimationFrame(step); }
        animFrame = requestAnimationFrame(step);
    }

    // BENCHMARK
    byId('btnBench')?.addEventListener('click', function () {
        var el = byId('benchResult');
        if (el) el.innerHTML = 'Ejecutando...';
        var q = (byId('benchQueries')?.value) || 50;
        fetch('php/experiments.php?action=benchmark&queries=' + q + '&_t=' + Date.now())
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success && d.benchmark) {
                    var b = d.benchmark;
                    var h = '';
                    h += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-top:0.5rem;">';
                    if (b.session_tree) {
                        h += '<div style="grid-column:1/-1;margin-bottom:-0.3rem;"><small style="color:#4fc3f7;">✓ ' + (b.session_info || '') + '</small></div>';
                    } else {
                        h += '<div style="grid-column:1/-1;margin-bottom:-0.3rem;"><small style="color:#ff9800;">⚠ Arbol creado localmente</small></div>';
                    }
                    h += '<div style="padding:0.6rem;border-radius:6px;text-align:center;background:rgba(198,40,40,0.12);border:1px solid rgba(198,40,40,0.25);"><small style="color:var(--bs-body-color);opacity:.6;">Secuencial</small><br><strong style="font-size:1.3rem;color:#ef5350;">' + (typeof b.sequential.avg_time === 'number' ? b.sequential.avg_time.toFixed(4) : '—') + 'ms</strong><br><small style="color:var(--bs-secondary-color);">' + (typeof b.sequential.avg_time === 'number' ? (b.sequential.avg_time / 1000).toFixed(6) + 's | ' + (b.sequential.avg_time / 60000).toFixed(8) + 'm' : '—') + ' | ' + (typeof b.sequential.avg_comparisons === 'number' ? b.sequential.avg_comparisons.toFixed(1) : '—') + ' comp</small></div>';
                    h += '<div style="padding:0.6rem;border-radius:6px;text-align:center;background:rgba(46,125,50,0.12);border:1px solid rgba(46,125,50,0.25);"><small style="color:var(--bs-body-color);opacity:.6;">KD-Tree</small><br><strong style="font-size:1.3rem;color:#66bb6a;">' + (typeof b.kdtree.avg_time === 'number' ? b.kdtree.avg_time.toFixed(4) : '—') + 'ms</strong><br><small style="color:var(--bs-secondary-color);">' + (typeof b.kdtree.avg_time === 'number' ? (b.kdtree.avg_time / 1000).toFixed(6) + 's | ' + (b.kdtree.avg_time / 60000).toFixed(8) + 'm' : '—') + ' | ' + (typeof b.kdtree.avg_comparisons === 'number' ? b.kdtree.avg_comparisons.toFixed(1) : '—') + ' comp | ' + (typeof b.kdtree.throughput_qps === 'number' ? b.kdtree.throughput_qps + ' q/s' : '—') + '</small></div>';
                    h += '</div>';
                    var kb2 = (typeof b.memory_kb === 'number') ? b.memory_kb : 0;
                    var mb2 = kb2 / 1024;
                    var memStr2 = kb2 > 0 ? (kb2 >= 1024 ? (mb2.toFixed(2) + ' MB') : kb2.toFixed(1) + ' KB') : '—';
                    var pctRelativo = (typeof b.kdtree.avg_time === 'number' && typeof b.sequential.avg_time === 'number' && b.sequential.avg_time > 0) ? (b.kdtree.avg_time / b.sequential.avg_time * 100).toFixed(2) : '—';
                    var pctMejora = (typeof b.kdtree.avg_time === 'number' && typeof b.sequential.avg_time === 'number' && b.sequential.avg_time > 0) ? ((1 - b.kdtree.avg_time / b.sequential.avg_time) * 100).toFixed(2) : '—';
                    var tiempoTotal = (typeof b.kdtree.build_time === 'number' ? b.kdtree.build_time : 0) + (typeof b.kdtree.total_time === 'number' ? b.kdtree.total_time : 0);
                    var pctBuild = tiempoTotal > 0 ? ((typeof b.kdtree.build_time === 'number' ? b.kdtree.build_time : 0) / tiempoTotal * 100).toFixed(1) : '—';
                    var pctSearch = tiempoTotal > 0 ? ((typeof b.kdtree.total_time === 'number' ? b.kdtree.total_time : 0) / tiempoTotal * 100).toFixed(1) : '—';
                    h += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-top:0.5rem;">';
                    h += '<div style="padding:0.5rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);"><small style="color:var(--bs-secondary-color);">Precision</small><br><strong style="font-size:1.1rem;color:' + (b.precision_pct === 100 ? '#66bb6a' : '#ffc107') + ';">' + (b.precision_pct || '—') + '%</strong><br><small style="color:var(--bs-secondary-color);">' + (b.precision_hits || '—') + '/' + b.num_queries + ' aciertos</small></div>';
                    h += '<div style="padding:0.5rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);"><small style="color:var(--bs-secondary-color);">Tiempo Relativo</small><br><strong style="font-size:1.1rem;color:#ffc107;">' + pctRelativo + '%</strong><br><small style="color:var(--bs-secondary-color);">KD es el ' + pctRelativo + '% de Sec</small></div>';
                    h += '<div style="padding:0.5rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);"><small style="color:var(--bs-secondary-color);">Mejora</small><br><strong style="font-size:1.1rem;color:#66bb6a;">' + pctMejora + '%</strong><br><small style="color:var(--bs-secondary-color);">mas rapido que Sec</small></div>';
                    h += '</div>';
                    h += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-top:0.5rem;">';
                    h += '<div style="padding:0.5rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);"><small style="color:var(--bs-secondary-color);">Memoria KD-Tree</small><br><strong style="font-size:1.1rem;color:var(--bs-body-color);">' + memStr2 + '</strong></div>';
                    h += '<div style="padding:0.5rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);"><small style="color:var(--bs-secondary-color);">Distribucion Tiempo</small><br><strong style="font-size:1rem;color:var(--bs-body-color);">Build ' + pctBuild + '%</strong><br><small style="color:var(--bs-secondary-color);">Search ' + pctSearch + '%</small></div>';
                    h += '<div style="padding:0.5rem;border-radius:6px;text-align:center;background:var(--bs-tertiary-bg);"><small style="color:var(--bs-secondary-color);">Throughput</small><br><strong style="font-size:1.1rem;color:#66bb6a;">' + (b.kdtree.throughput_qps || '—') + ' q/s</strong><br><small style="color:var(--bs-secondary-color);">KD-Tree</small></div>';
                    h += '</div>';

                    // Tabla comparativa clara
                    h += '<div style="margin-top:0.8rem;background:var(--bs-tertiary-bg);border-radius:8px;padding:0.6rem;">';
                    h += '<strong style="color:var(--bs-body-color);font-size:0.8rem;">Resumen Comparativo</strong>';
                    h += '<table style="width:100%;margin-top:0.3rem;border-collapse:collapse;font-size:0.78rem;">';
                    h += '<thead><tr style="color:var(--bs-secondary-color);border-bottom:1px solid var(--bs-border-color);"><th style="padding:0.3rem;text-align:left;">Métrica</th><th style="text-align:center;color:#ef5350;">Secuencial</th><th style="text-align:center;color:#66bb6a;">KD-Tree</th><th style="text-align:center;color:var(--bs-secondary-color);">Resultado</th></tr></thead>';
                    h += '<tbody>';
                    h += '<tr><td style="padding:0.2rem 0.3rem;color:var(--bs-body-color);">Tiempo</td><td style="text-align:center;color:#ef5350;">' + (typeof b.sequential.avg_time === 'number' ? b.sequential.avg_time.toFixed(4) + 'ms' : '—') + '</td><td style="text-align:center;color:#66bb6a;">' + (typeof b.kdtree.avg_time === 'number' ? b.kdtree.avg_time.toFixed(4) + 'ms' : '—') + '</td><td style="text-align:center;"><strong style="color:#66bb6a;">KD-Tree ' + b.speedup + 'x más rápido</strong></td></tr>';
                    h += '<tr style="background:rgba(128,128,128,.04);"><td style="padding:0.2rem 0.3rem;color:var(--bs-body-color);">Comparaciones</td><td style="text-align:center;color:#ef5350;">' + (typeof b.sequential.avg_comparisons === 'number' ? b.sequential.avg_comparisons.toFixed(1) : '—') + ' /consulta</td><td style="text-align:center;color:#66bb6a;">' + (typeof b.kdtree.avg_comparisons === 'number' ? b.kdtree.avg_comparisons.toFixed(1) : '—') + ' /consulta</td><td style="text-align:center;color:var(--bs-secondary-color);">KD revisa solo ' + pctRelativo + '% de los puntos</td></tr>';
                    h += '<tr><td style="padding:0.2rem 0.3rem;color:var(--bs-body-color);">Throughput</td><td style="text-align:center;color:#ef5350;">' + (typeof b.sequential.avg_time === 'number' && b.sequential.avg_time > 0 ? (1000 / b.sequential.avg_time).toFixed(0) + ' q/s' : '—') + '</td><td style="text-align:center;color:#66bb6a;">' + (b.kdtree.throughput_qps || '—') + ' q/s</td><td style="text-align:center;color:var(--bs-secondary-color);">—</td></tr>';
                    h += '<tr style="background:rgba(128,128,128,.04);"><td style="padding:0.2rem 0.3rem;color:var(--bs-body-color);">Precisión</td><td style="text-align:center;color:#ef5350;">100% (referencia)</td><td style="text-align:center;color:#66bb6a;">' + (b.precision_pct || '—') + '%</td><td style="text-align:center;color:var(--bs-secondary-color);">' + (b.precision_pct === 100 ? 'Exacto ✓' : (b.precision_hits || '—') + '/' + b.num_queries) + '</td></tr>';
                    h += '</tbody></table></div>';
                    if (el) el.innerHTML = h;
                    drawBar('benchCanvas', ['Secuencial', 'KD-Tree'], [b.sequential.avg_time, b.kdtree.avg_time], 'Benchmark (' + b.num_points + ' pts, ' + b.num_queries + ' queries)');
                } else {
                    if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error del servidor: ' + JSON.stringify(d) + '</div>';
                }
            })
            .catch(function (err) {
                if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error de red: ' + err.message + '</div>';
            });
    });

    // ESCALABILIDAD
    byId('btnScal')?.addEventListener('click', function () {
        var el = byId('scalResult');
        if (el) el.innerHTML = 'Ejecutando...';
        fetch('php/experiments.php?action=scalability')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success && d.scalability) {
                    var a = d.scalability;
                    var h = '<table style="width:100%;border-collapse:collapse;font-size:0.75rem;">';
                    h += '<thead><tr style="border-bottom:2px solid rgba(218,41,28,0.3);"><th style="padding:0.25rem;">Tam</th><th>Build</th><th>KD</th><th>Seq</th><th>Speedup</th><th>Precision</th><th>Throughput</th><th>Prof.Busq</th><th>Memoria</th></tr></thead><tbody>';
                    for (var i = 0; i < a.length; i++) {
                        var kb = a[i].memory_kb || 0;
                        var mb = kb / 1024;
                        var kbn = a[i].memory_kb_per_node || 0;
                        var memStr = kb >= 1024 ? (mb.toFixed(2) + ' MB') : (kb === 0 ? '—' : kb.toFixed(1) + ' KB');
                        var nodeStr = kbn > 0 ? kbn.toFixed(1) + ' KB/n' : '';
                        var kdQps = a[i].kdtree.throughput_qps || '—';
                        var seqQps = a[i].sequential.throughput_qps || '—';
                        var depth = a[i].kdtree.avg_search_depth || '—';
                        h += '<tr>' +
                            '<td><strong>' + a[i].size + '</strong></td>' +
                            '<td>' + a[i].build_time_ms.toFixed(1) + 'ms</td>' +
                            '<td style="color:#66bb6a;">' + a[i].kdtree.avg_time_ms.toFixed(4) + 'ms<br><small style="opacity:0.4;">' + (a[i].kdtree.avg_time_ms / 1000).toFixed(6) + 's | ' + (a[i].kdtree.avg_time_ms / 60000).toFixed(8) + 'm</small></td>' +
                            '<td style="color:#ef5350;">' + a[i].sequential.avg_time_ms.toFixed(4) + 'ms<br><small style="opacity:0.4;">' + (a[i].sequential.avg_time_ms / 1000).toFixed(6) + 's | ' + (a[i].sequential.avg_time_ms / 60000).toFixed(8) + 'm</small></td>' +
                            '<td><strong>' + a[i].speedup + 'x</strong></td>' +
                            '<td style="color:' + (a[i].precision_pct === 100 ? '#66bb6a' : '#ffc107') + ';">' + (a[i].precision_pct || '—') + '%</td>' +
                            '<td><span title="KD: ' + kdQps + ' qps | Seq: ' + seqQps + ' qps" style="border-bottom:1px dotted rgba(255,255,255,0.2);">' + kdQps + '/s</span></td>' +
                            '<td>' + depth + '</td>' +
                            '<td>' + memStr + (nodeStr ? ' <small style="opacity:0.4;">' + nodeStr + '</small>' : '') + '</td>' +
                            '</tr>';
                    }
                    h += '</tbody></table>';
                    if (el) el.innerHTML = h;
                    drawLine('scalLineCanvas', a.map(function (r) { return r.size; }), [
                        { label: 'KD-Tree', data: a.map(function (r) { return r.kdtree.avg_time_ms; }), color: '#66bb6a' },
                        { label: 'Secuencial', data: a.map(function (r) { return r.sequential.avg_time_ms; }), color: '#ef5350', dash: [5, 5] }
                    ], 'Escalabilidad: Tiempo vs Tamano');
                    drawBar('scalBarCanvas', a.map(function (r) { return r.size; }), a.map(function (r) { return r.speedup; }), 'Speedup del KD-Tree');
                } else {
                    if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error: ' + JSON.stringify(d) + '</div>';
                }
            }).catch(function (err) {
                if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error de red: ' + err.message + '</div>';
            });
    });

    // EXPERIMENTOS
    byId('btnExpScal')?.addEventListener('click', function () { byId('btnScal')?.click(); });
    byId('btnExpBench')?.addEventListener('click', function () { byId('btnBench')?.click(); });
    byId('btnExpDim')?.addEventListener('click', function () {
        var el = byId('expResult');
        if (el) el.innerHTML = 'Ejecutando...';
        fetch('php/experiments.php?action=dimensions')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success && d.dimensions) {
                    var a = d.dimensions;
                    var h = '<h6 class="text-info mb-2">Variacion de Dimensiones (1000 pts)</h6>';
                    h += '<table style="width:100%;border-collapse:collapse;font-size:0.8rem;"><thead><tr><th>Dim</th><th>Build</th><th>Search</th><th>Comps</th><th>Altura</th></tr></thead><tbody>';
                    for (var i = 0; i < a.length; i++) h += '<tr><td><strong>' + a[i].dimensions + 'D</strong></td><td>' + a[i].build_time_ms.toFixed(1) + 'ms</td><td>' + a[i].avg_search_ms.toFixed(3) + '</td><td>' + a[i].avg_comparisons + '</td><td>' + a[i].height + '</td></tr>';
                    h += '</tbody></table>';
                    if (el) el.innerHTML = h;
                    drawBar('expCanvas1', a.map(function (r) { return r.dimensions + 'D'; }), a.map(function (r) { return r.build_time_ms; }), 'Tiempo de Construccion');
                    drawLine('expCanvas2', a.map(function (r) { return r.dimensions + 'D'; }), [{ label: 'Busqueda (ms)', data: a.map(function (r) { return r.avg_search_ms; }), color: '#66bb6a' }], 'Rendimiento vs Dimensiones');
                }
            });
    });
})();
</script>
</body>
</html>
