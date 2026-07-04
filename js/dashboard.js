/**
 * Dashboard — Benchmark, Escalabilidad, Experimentos (simplificado)
 */
var dash = {};

(function () {
    function byId(id) { return document.getElementById(id); }

    function loadStatus() {
        fetch('php/statistics.php?action=status')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var s = byId('dashTotalRecords'); if (s) s.textContent = data.total_records || '0';
                s = byId('dashTreeStatus'); if (s) { s.textContent = data.tree_built ? 'Si' : 'No'; s.style.color = data.tree_built ? '#66bb6a' : '#ef5350'; }
                if (data.statistics) {
                    var set = function (id, v) { var e = byId(id); if (e) e.textContent = v; };
                    set('dashTotalNodes', data.statistics.total_nodes);
                    set('dashHeight', data.statistics.height);
                    set('dashMaxDepth', data.statistics.max_depth);
                    set('dashMinDepth', data.statistics.min_depth);
                    set('dashAvgDepth', data.statistics.avg_depth?.toFixed ? data.statistics.avg_depth.toFixed(2) : '0');
                    set('dashDimensions', data.statistics.dimensions);
                }
            }).catch(function () {});
    }

    function runBenchmark() {
        var el = byId('benchmarkResult');
        if (el) el.innerHTML = '<div class="text-secondary py-2 small">Ejecutando...</div>';
        var q = (byId('benchmarkQueries')?.value) || 50;
        fetch('php/experiments.php?action=benchmark&queries=' + q)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.benchmark) {
                    var d = data.benchmark;
                    var h = '';
                    h += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-top:0.5rem;">';
                    h += '<div style="padding:0.6rem;border-radius:6px;text-align:center;background:rgba(198,40,40,0.15);border:1px solid rgba(198,40,40,0.3);"><small style="text-transform:uppercase;opacity:0.6;">Secuencial</small><br><strong style="font-size:1.3rem;color:#ef5350;">' + d.sequential.avg_time.toFixed(4) + 'ms</strong><br><small style="opacity:0.5;">' + d.sequential.avg_comparisons.toFixed(1) + ' comp/prom</small></div>';
                    h += '<div style="padding:0.6rem;border-radius:6px;text-align:center;background:rgba(46,125,50,0.15);border:1px solid rgba(46,125,50,0.3);"><small style="text-transform:uppercase;opacity:0.6;">KD-Tree</small><br><strong style="font-size:1.3rem;color:#66bb6a;">' + d.kdtree.avg_time.toFixed(4) + 'ms</strong><br><small style="opacity:0.5;">' + d.kdtree.avg_comparisons.toFixed(1) + ' comp/prom · bld:' + d.kdtree.build_time.toFixed(1) + 'ms</small></div>';
                    h += '</div>';
                    h += '<div style="text-align:center;margin-top:0.5rem;"><span style="display:inline-block;background:#2e7d32;color:#fff;padding:0.25rem 0.6rem;border-radius:20px;font-weight:700;font-size:0.8rem;">KD-Tree ' + d.speedup + 'x mas rapido (' + d.improvement_percent + '% mejora)</span></div>';
                    if (el) el.innerHTML = h;

                    try {
                        var c = byId('chartBenchmark');
                        if (c) {
                            c.width = c.offsetWidth || 400;
                            c.height = 260;
                            var ctx = c.getContext('2d');
                            ctx.clearRect(0, 0, c.width, c.height);
                            ctx.fillStyle = 'rgba(10,18,30,0.5)';
                            ctx.fillRect(0, 0, c.width, c.height);
                            ctx.fillStyle = '#ef5350';
                            var bw = c.width / 5;
                            ctx.fillRect(c.width * 0.2, c.height - 20, bw, -(d.sequential.avg_time / Math.max(d.sequential.avg_time, d.kdtree.avg_time) * (c.height - 80)));
                            ctx.fillStyle = '#66bb6a';
                            ctx.fillRect(c.width * 0.55, c.height - 20, bw, -(d.kdtree.avg_time / Math.max(d.sequential.avg_time, d.kdtree.avg_time) * (c.height - 80)));
                            ctx.fillStyle = '#ccc'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
                            ctx.fillText('Secuencial ' + d.sequential.avg_time.toFixed(4) + 'ms', c.width * 0.2 + bw / 2, c.height - 3);
                            ctx.fillText('KD-Tree ' + d.kdtree.avg_time.toFixed(4) + 'ms', c.width * 0.55 + bw / 2, c.height - 3);
                            ctx.fillStyle = '#ccc'; ctx.font = '11px sans-serif';
                            ctx.fillText('Benchmark (' + d.num_points + ' pts, ' + d.num_queries + ' queries)', c.width / 2, 15);
                        }
                    } catch (e) {}
                } else {
                    if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error: ' + (data.error || '') + '</div>';
                }
            }).catch(function () {
                if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error de conexion</div>';
            });
    }

    function runScalability() {
        var el = byId('scalabilityResult');
        if (el) el.innerHTML = '<div class="text-secondary py-2 small">Ejecutando...</div>';
        fetch('php/experiments.php?action=scalability')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.scalability) {
                    var arr = data.scalability;
                    var h = '<div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:0.8rem;">';
                    h += '<thead><tr style="border-bottom:2px solid rgba(218,41,28,0.3);"><th style="padding:0.4rem;text-align:left;">Tam</th><th style="padding:0.4rem;text-align:left;">Build (ms)</th><th style="padding:0.4rem;text-align:left;">KD (ms)</th><th style="padding:0.4rem;text-align:left;">Seq (ms)</th><th style="padding:0.4rem;text-align:left;">Speedup</th><th style="padding:0.4rem;text-align:left;">Altura</th></tr></thead><tbody>';
                    for (var i = 0; i < arr.length; i++) {
                        var r = arr[i];
                        h += '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">';
                        h += '<td style="padding:0.3rem 0.4rem;"><strong>' + r.size + '</strong></td>';
                        h += '<td>' + r.build_time_ms.toFixed(2) + '</td>';
                        h += '<td style="color:#66bb6a;">' + r.kdtree.avg_time_ms.toFixed(4) + '</td>';
                        h += '<td style="color:#ef5350;">' + r.sequential.avg_time_ms.toFixed(4) + '</td>';
                        h += '<td><strong>' + r.speedup + 'x</strong></td>';
                        h += '<td>' + r.kdtree.height + '</td></tr>';
                    }
                    h += '</tbody></table></div>';
                    if (el) el.innerHTML = h;

                    // Simple line chart
                    try {
                        var c = byId('chartScalability');
                        var speedC = byId('chartSpeedup');
                        if (c) {
                            c.width = c.offsetWidth || 400; c.height = 260;
                            var ctx = c.getContext('2d'), pad = { l: 50, r: 20, t: 30, b: 30 };
                            ctx.clearRect(0, 0, c.width, c.height);
                            ctx.fillStyle = 'rgba(10,18,30,0.5)';
                            ctx.fillRect(0, 0, c.width, c.height);
                            var pw = c.width - pad.l - pad.r, ph = c.height - pad.t - pad.b;
                            var maxV = 0;
                            for (var i = 0; i < arr.length; i++) {
                                maxV = Math.max(maxV, arr[i].sequential.avg_time_ms, arr[i].kdtree.avg_time_ms);
                            }
                            maxV = maxV * 1.2;
                            function xPos(i) { return pad.l + (pw / (arr.length - 1)) * i; }
                            function yPos(v) { return pad.t + ph - (v / maxV) * ph; }
                            ctx.strokeStyle = '#ef5350'; ctx.lineWidth = 1.5; ctx.setLineDash([5, 5]); ctx.beginPath();
                            for (var i = 0; i < arr.length; i++) {
                                var xx = xPos(i), yy = yPos(arr[i].sequential.avg_time_ms);
                                if (i === 0) ctx.moveTo(xx, yy); else ctx.lineTo(xx, yy);
                            }
                            ctx.stroke(); ctx.setLineDash([]);
                            ctx.strokeStyle = '#66bb6a'; ctx.lineWidth = 2; ctx.beginPath();
                            for (var i = 0; i < arr.length; i++) {
                                var xx = xPos(i), yy = yPos(arr[i].kdtree.avg_time_ms);
                                if (i === 0) ctx.moveTo(xx, yy); else ctx.lineTo(xx, yy);
                            }
                            ctx.stroke();
                            ctx.fillStyle = '#ccc'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
                            ctx.fillText('Escalabilidad: Tiempo vs Tamano', c.width / 2, 14);
                            for (var i = 0; i < arr.length; i++) {
                                ctx.fillStyle = '#888'; ctx.fillText(arr[i].size, xPos(i), c.height - pad.b + 15);
                            }
                        }
                        if (speedC) {
                            speedC.width = speedC.offsetWidth || 400; speedC.height = 260;
                            var ctx2 = speedC.getContext('2d');
                            ctx2.clearRect(0, 0, speedC.width, speedC.height);
                            ctx2.fillStyle = 'rgba(10,18,30,0.5)';
                            ctx2.fillRect(0, 0, speedC.width, speedC.height);
                            var bw2 = (speedC.width - 60 - 20) / arr.length * 0.7;
                            var gap2 = (speedC.width - 60 - 20) / arr.length * 0.3;
                            var maxS = 0;
                            for (var i = 0; i < arr.length; i++) maxS = Math.max(maxS, arr[i].speedup);
                            maxS = maxS * 1.2;
                            for (var i = 0; i < arr.length; i++) {
                                var bh2 = (arr[i].speedup / maxS) * (speedC.height - 60);
                                ctx2.fillStyle = '#66bb6a';
                                ctx2.fillRect(50 + i * (bw2 + gap2), speedC.height - 30 - bh2, bw2, bh2);
                                ctx2.fillStyle = '#888'; ctx2.font = '9px sans-serif'; ctx2.textAlign = 'center';
                                ctx2.fillText(arr[i].size, 50 + i * (bw2 + gap2) + bw2 / 2, speedC.height - 10);
                            }
                            ctx2.fillStyle = '#ccc'; ctx2.font = '9px sans-serif'; ctx2.textAlign = 'center';
                            ctx2.fillText('Speedup del KD-Tree', speedC.width / 2, 14);
                        }
                    } catch (e) {}
                } else {
                    if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error</div>';
                }
            }).catch(function () {
                if (el) el.innerHTML = '<div class="alert alert-danger py-1 small">Error de conexion</div>';
            });
    }

    function runExperimentsTab() {
        var el = byId('experimentsResult');
        if (el) el.innerHTML = '<div class="text-secondary py-2 small">Ejecutando...</div>';
        fetch('php/experiments.php?action=scalability')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.scalability) {
                    var a = data.scalability;
                    var h = '<h6 class="text-info mb-2">Escalabilidad (5 tamaños)</h6>';
                    h += '<table style="width:100%;border-collapse:collapse;font-size:0.8rem;">';
                    h += '<thead><tr style="border-bottom:2px solid rgba(218,41,28,0.3);"><th style="padding:0.3rem;">Tam</th><th>Build</th><th>KD</th><th>Seq</th><th>Speedup</th><th>H</th></tr></thead><tbody>';
                    for (var i = 0; i < a.length; i++) {
                        h += '<tr><td style="padding:0.2rem;"><strong>' + a[i].size + '</strong></td><td>' + a[i].build_time_ms.toFixed(1) + '</td><td style="color:#66bb6a;">' + a[i].kdtree.avg_time_ms.toFixed(3) + '</td><td style="color:#ef5350;">' + a[i].sequential.avg_time_ms.toFixed(3) + '</td><td><strong>' + a[i].speedup + 'x</strong></td><td>' + a[i].kdtree.height + '</td></tr>';
                    }
                    h += '</tbody></table>';
                    if (el) el.innerHTML = h;
                }
            });
    }

    function runDimensions() {
        var el = byId('experimentsResult');
        if (el) el.innerHTML = '<div class="text-secondary py-2 small">Ejecutando...</div>';
        fetch('php/experiments.php?action=dimensions')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.dimensions) {
                    var a = data.dimensions;
                    var h = '<h6 class="text-info mb-2">Variacion de Dimensiones (1000 pts)</h6>';
                    h += '<table style="width:100%;border-collapse:collapse;font-size:0.8rem;">';
                    h += '<thead><tr><th style="padding:0.3rem;">Dim</th><th>Build</th><th>Search</th><th>Comps</th><th>Altura</th></tr></thead><tbody>';
                    for (var i = 0; i < a.length; i++) {
                        h += '<tr><td style="padding:0.2rem;"><strong>' + a[i].dimensions + 'D</strong></td><td>' + a[i].build_time_ms.toFixed(1) + 'ms</td><td>' + a[i].avg_search_ms.toFixed(3) + '</td><td>' + a[i].avg_comparisons + '</td><td>' + a[i].height + '</td></tr>';
                    }
                    h += '</tbody></table>';
                    if (el) el.innerHTML = h;
                }
            });
    }

    // Init
    document.addEventListener('DOMContentLoaded', function () {
        loadStatus();
        byId('btnRunBenchmark')?.addEventListener('click', runBenchmark);
        byId('btnRunScalability')?.addEventListener('click', runScalability);
        byId('btnRefreshStatus')?.addEventListener('click', loadStatus);
        byId('btnRunScalability2')?.addEventListener('click', runExperimentsTab);
        byId('btnRunBenchmark2')?.addEventListener('click', runBenchmark);
        byId('btnRunDimensions')?.addEventListener('click', runDimensions);
    });
})();
