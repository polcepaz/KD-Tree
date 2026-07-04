/**
 * Graficos estadisticos con Chart.js
 */
const Charts = {
    instances: {},

    _can: function (id) {
        const c = document.getElementById(id);
        if (!c) return null;
        if (this.instances[id]) {
            try { this.instances[id].destroy(); } catch (e) {}
            this.instances[id] = null;
        }
        return c;
    },

    _chart: function (canvas, config) {
        try {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js no disponible');
                return null;
            }
            this.instances[canvas.id] = new Chart(canvas, config);
        } catch (e) {
            console.warn('Error creando grafico:', e);
        }
    },

    createBenchmarkChart: function (data, canvasId) {
        const canvas = this._can(canvasId);
        if (!canvas) return;

        this._chart(canvas, {
            type: 'bar',
            data: {
                labels: ['Secuencial', 'KD-Tree'],
                datasets: [
                    {
                        label: 'Tiempo (ms)',
                        data: [data.sequential.avg_time, data.kdtree.avg_time],
                        backgroundColor: ['rgba(239,83,80,0.7)', 'rgba(102,187,106,0.7)'],
                        borderColor: ['rgba(239,83,80,1)', 'rgba(102,187,106,1)'],
                        borderWidth: 1,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Comparaciones',
                        data: [data.sequential.avg_comparisons, data.kdtree.avg_comparisons],
                        type: 'line',
                        backgroundColor: ['rgba(239,83,80,0.2)', 'rgba(102,187,106,0.2)'],
                        borderColor: ['rgba(239,83,80,0.8)', 'rgba(102,187,106,0.8)'],
                        borderWidth: 1,
                        yAxisID: 'y1',
                        pointStyle: 'circle',
                        pointRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'KD-Tree vs Busqueda Secuencial', color: '#ccc' },
                    legend: { labels: { color: '#ccc', boxWidth: 12 } },
                },
                scales: {
                    x: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                    y: { position: 'left', title: { display: true, text: 'ms', color: '#ccc' }, ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                    y1: { position: 'right', title: { display: true, text: 'Comp.', color: '#ccc' }, ticks: { color: '#ccc' }, grid: { drawOnChartArea: false } },
                },
            },
        });
    },

    createScalabilityChart: function (data) {
        const canvas = this._can('chartScalability');
        if (!canvas) return;

        const labels = data.map(function (r) { return r.size; });
        const kd = data.map(function (r) { return r.kdtree.avg_search_time; });
        const seq = data.map(function (r) { return r.sequential.avg_search_time; });

        this._chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'KD-Tree (ms)',
                        data: kd,
                        borderColor: '#66bb6a',
                        backgroundColor: 'rgba(102,187,106,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointBackgroundColor: '#66bb6a',
                    },
                    {
                        label: 'Secuencial (ms)',
                        data: seq,
                        borderColor: '#ef5350',
                        backgroundColor: 'rgba(239,83,80,0.1)',
                        fill: true,
                        tension: 0.3,
                        borderDash: [5, 5],
                        pointRadius: 5,
                        pointBackgroundColor: '#ef5350',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Escalabilidad: Tiempo vs Tamano', color: '#ccc' },
                    legend: { labels: { color: '#ccc', boxWidth: 12 } },
                },
                scales: {
                    x: { title: { display: true, text: 'Puntos', color: '#ccc' }, ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                    y: { type: 'logarithmic', title: { display: true, text: 'ms', color: '#ccc' }, ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                },
            },
        });

        this.createSpeedupChart(labels, data.map(function (r) { return r.speedup; }));
    },

    createSpeedupChart: function (labels, speedups) {
        const canvas = this._can('chartSpeedup');
        if (!canvas) return;

        this._chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Speedup (x)',
                    data: speedups,
                    backgroundColor: speedups.map(function (s) { return s > 1 ? 'rgba(102,187,106,0.7)' : 'rgba(239,83,80,0.7)'; }),
                    borderColor: speedups.map(function (s) { return s > 1 ? 'rgba(102,187,106,1)' : 'rgba(239,83,80,1)'; }),
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Speedup del KD-Tree', color: '#ccc' },
                    legend: { labels: { color: '#ccc', boxWidth: 12 } },
                },
                scales: {
                    x: { title: { display: true, text: 'Puntos', color: '#ccc' }, ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                    y: { title: { display: true, text: 'x veces', color: '#ccc' }, ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                },
            },
        });
    },
};
