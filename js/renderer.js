/**
 * Renderizador del KD-Tree simplificado y robusto.
 */
const Renderer = {
    canvas: null, ctx: null, treeData: null,
    scale: 1, offsetX: 0, offsetY: 0,
    isDragging: false, dragStartX: 0, dragStartY: 0, dragOffsetX: 0, dragOffsetY: 0,
    nodes: [], edges: [], hoveredNode: null, highlightedNodeId: null,
    animHighlightAlpha: 0, animHighlightDir: 1,
    dimColors: ['#da291c', '#cfa144', '#8b4513', '#d4764e', '#5c3a29', '#a0522d'],

    // Estados para step-by-step
    steps: [], currentStepIndex: -1, playing: false, playTimer: null,
    playbackSpeed: 500, nodeStates: new Map(),
    selectedNode: null,
    selectedNodePos: null,
    infoAnimAlpha: 0,

    // Node dragging
    dragNode: null, dragNodeOffsetX: 0, dragNodeOffsetY: 0, wasNodeDrag: false,
    customPositions: {}, // { [nodeId]: { wx, wy } } para nodos movidos manualmente

    // Efectos visuales
    ripples: [],       // { x, y, radius, alpha, maxRadius }
    particles: [],     // { x, y, vx, vy, size, alpha }
    stars: [],         // { x, y, size, twinkle, phase }
    snowflakes: [],    // { x, y, size, speed, sway, swaySpeed, phase }
    edgeTime: 0,       // para animacion de flujo en aristas
    animFrame: null,   // requestAnimationFrame id
    zoomTarget: null,  // { x, y, scale } para zoom suave
    zoomAnimating: false,

    init: function (canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.resize();
        this.bindEvents();
        window.addEventListener('resize', () => this.resize());
        this.startEffects();
    },

    resize: function () {
        if (!this.canvas) return;
        const container = this.canvas.parentElement;
        if (!container) return;
        const rect = container.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;
        const dpr = window.devicePixelRatio || 1;
        this._dpr = dpr;
        this.width = rect.width;
        this.height = rect.height;
        this.canvas.width = rect.width * dpr;
        this.canvas.height = rect.height * dpr;
        this.stars = [];
        this.snowflakes = [];
        if (this.treeData) this.renderTree(this.treeData);
    },

    bindEvents: function () {
        const c = this.canvas;
        if (!c) return;
        c.addEventListener('mousedown', (e) => this.onMouseDown(e));
        window.addEventListener('mousemove', (e) => this.onMouseMove(e));
        window.addEventListener('mouseup', () => this.onMouseUp());
        c.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });
        c.addEventListener('click', (e) => this.onClick(e));
        c.addEventListener('mouseleave', () => { this.hoveredNode = null; this.renderTree(this.treeData); });
        c.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
        c.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
        c.addEventListener('touchend', () => this.onMouseUp());
    },

    getCanvasCoords: function (cx, cy) {
        const r = this.canvas.getBoundingClientRect();
        return { x: (cx - r.left), y: (cy - r.top) };
    },

    worldToScreen: function (wx, wy) {
        return {
            x: (wx + this.offsetX) * this.scale + this.width / 2,
            y: (wy + this.offsetY) * this.scale + 30,
        };
    },

    screenToWorld: function (sx, sy) {
        return {
            x: (sx - this.width / 2) / this.scale - this.offsetX,
            y: (sy - 30) / this.scale - this.offsetY,
        };
    },

    onMouseDown: function (e) {
        this.isDragging = false;
        this.dragNode = null;
        const p = this.getCanvasCoords(e.clientX, e.clientY);
        const w = this.screenToWorld(p.x, p.y);
        const n = this.findNodeAt(w.x, w.y);

        if (n) {
            // Click en nodo -> posible arrastre
            this.dragNode = n;
            this.dragNodeOffsetX = n.wx - w.x;
            this.dragNodeOffsetY = n.wy - w.y;
            this.wasNodeDrag = false;
            this.canvas.style.cursor = 'grabbing';
        } else {
            // Click en vacio -> panear canvas
            this.isDragging = true;
            this.dragStartX = e.clientX; this.dragStartY = e.clientY;
            this.dragOffsetX = this.offsetX; this.dragOffsetY = this.offsetY;
            this.canvas.style.cursor = 'grabbing';
        }
    },

    onMouseMove: function (e) {
        if (this.dragNode) {
            // Arrastrando nodo
            const p = this.getCanvasCoords(e.clientX, e.clientY);
            const w = this.screenToWorld(p.x, p.y);
            const nid = this.dragNode.point.id;
            this.customPositions[nid] = {
                wx: w.x + this.dragNodeOffsetX,
                wy: w.y + this.dragNodeOffsetY
            };
            this.wasNodeDrag = true;
            this.renderTree(this.treeData);
        } else if (this.isDragging) {
            // Arrastrando canvas (pan)
            const dx = (e.clientX - this.dragStartX) / this.scale;
            const dy = (e.clientY - this.dragStartY) / this.scale;
            this.offsetX = this.dragOffsetX + dx;
            this.offsetY = this.dragOffsetY + dy;
            this.renderTree(this.treeData);
            this.updatePanDisplay();
        } else {
            const p = this.getCanvasCoords(e.clientX, e.clientY);
            const w = this.screenToWorld(p.x, p.y);
            this.updateHover(w.x, w.y);
        }
    },
    onMouseUp: function () {
        this.dragNode = null;
        this.isDragging = false;
        this.canvas.style.cursor = 'grab';
    },
    onWheel: function (e) {
        e.preventDefault();
        const ns = Math.min(50, Math.max(0.1, this.scale * (e.deltaY > 0 ? 0.9 : 1.1)));
        const p = this.getCanvasCoords(e.clientX, e.clientY);
        const w = this.screenToWorld(p.x, p.y);
        this.scale = ns;
        this.offsetX = w.x - (p.x - this.width / 2) / this.scale;
        this.offsetY = w.y - (p.y - 30) / this.scale;
        this.renderTree(this.treeData);
        this.updateZoomDisplay();
        this.updatePanDisplay();
    },
    onClick: function (e) {
        if (!this.treeData) return;
        const p = this.getCanvasCoords(e.clientX, e.clientY);
        const w = this.screenToWorld(p.x, p.y);
        const n = this.findNodeAt(w.x, w.y);
        if (n) {
            const pos = this.worldToScreen(n.wx, n.wy);
            this.selectedNode = n;
            this.selectedNodePos = pos;
            this.infoAnimAlpha = 0;
            this.addRipple(pos.x, pos.y);
            this.renderTree(this.treeData);
            // Animacion fade-in del panel
            const animate = () => {
                if (this.infoAnimAlpha < 1) {
                    this.infoAnimAlpha = Math.min(1, this.infoAnimAlpha + 0.08);
                    if (this.selectedNode) this.renderTree(this.treeData);
                    requestAnimationFrame(animate);
                }
            };
            requestAnimationFrame(animate);
        } else {
            // Clic en vacio -> cerrar panel si estaba abierto
            if (this.selectedNode) {
                this.selectedNode = null;
                this.selectedNodePos = null;
                this.renderTree(this.treeData);
            }
        }
    },
    onTouchStart: function (e) {
        if (e.touches.length === 1) {
            const t = e.touches[0];
            this.isDragging = true;
            this.dragStartX = t.clientX; this.dragStartY = t.clientY;
            this.dragOffsetX = this.offsetX; this.dragOffsetY = this.offsetY;
        }
    },
    onTouchMove: function (e) {
        e.preventDefault();
        if (e.touches.length === 1 && this.isDragging) {
            const t = e.touches[0];
            const dx = (t.clientX - this.dragStartX) / this.scale;
            const dy = (t.clientY - this.dragStartY) / this.scale;
            this.offsetX = this.dragOffsetX + dx;
            this.offsetY = this.dragOffsetY + dy;
            this.renderTree(this.treeData);
            this.updatePanDisplay();
        }
    },

    updateHover: function (wx, wy) {
        const n = this.findNodeAt(wx, wy);
        if (n !== this.hoveredNode) {
            this.hoveredNode = n;
            this.canvas.style.cursor = n ? 'pointer' : 'grab';
            this.renderTree(this.treeData);
        }
    },
    findNodeAt: function (wx, wy) {
        const t = 26 / this.scale;
        for (const n of this.nodes) {
            const dx = n.wx - wx, dy = n.wy - wy;
            if (dx * dx + dy * dy < t * t) return n;
        }
        return null;
    },

    showNodeTooltip: function (node, cx, cy) {
        const tip = document.getElementById('nodeTooltip');
        if (!tip) return;
        const coords = node.point.coordinates || node.point;
        const server = node.point.name || node.point.server_name || `ID:${node.point.id}`;
        const dimColor = this.dimColors[node.dim % this.dimColors.length];

        // Usar position:fixed relativo a la ventana para evitar problemas de contenedor
        tip.style.cssText = `
            display:block;
            position:fixed;
            z-index:9999;
            background:#0d1117;
            border:1px solid ${dimColor};
            border-radius:8px;
            padding:10px 12px;
            min-width:220px;
            max-width:300px;
            box-shadow:0 8px 24px rgba(0,0,0,0.5);
            left:${Math.min(cx + 15, window.innerWidth - 240)}px;
            top:${Math.min(cy + 15, window.innerHeight - 220)}px;
            pointer-events:none;
            font-family:'Segoe UI',system-ui,sans-serif;
        `;
        tip.innerHTML = `
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${dimColor}"></span>
                <strong style="color:${dimColor};font-size:13px;font-weight:600;">${server}</strong>
            </div>
            <hr style="margin:5px 0;border:none;border-top:1px solid rgba(255,255,255,0.1);">
            <div style="font-size:12px;line-height:1.8;">
                <span style="background:rgba(255,255,255,0.1);padding:1px 7px;border-radius:4px;margin-right:4px;color:#e6edf3;">Nivel ${node.level}</span>
                <span style="background:${dimColor};padding:1px 7px;border-radius:4px;margin-right:4px;color:#fff;">d${node.dim}</span>
                <span style="background:rgba(255,255,255,0.1);padding:1px 7px;border-radius:4px;color:#e6edf3;">Split: ${node.split.toFixed(1)}</span>
            </div>
            <div style="font-size:11px;margin-top:6px;color:rgba(255,255,255,0.5);line-height:1.6;">
                ${coords.map((v,i) => `<span style="color:${this.dimColors[i % this.dimColors.length]}">d${i}</span>=${typeof v === 'number' ? v.toFixed(1) : v}`).join(' ')}
            </div>
        `;
        setTimeout(() => { tip.style.display = 'none'; }, 6000);
    },

    // ---- Step-by-step engine (same as before) ----
    setSteps: function (steps, autoPlay) {
        this.stop();
        this.steps = steps || [];
        this.currentStepIndex = -1;
        this.nodeStates = new Map();
        this.updateUI();
        if (steps.length > 0) {
            if (autoPlay) this.play();
            else this.goToStep(0);
        }
    },

    goToStep: function (index) {
        if (index < 0 || index >= this.steps.length) return;
        this.currentStepIndex = index;
        const step = this.steps[index];
        this.nodeStates = new Map();
        if (step.visitedIds) for (const id of step.visitedIds) { const s = this.nodeStates.get(id) || {}; s.visited = true; this.nodeStates.set(id, s); }
        if (step.prunedIds) for (const id of step.prunedIds) { const s = this.nodeStates.get(id) || {}; s.pruned = true; this.nodeStates.set(id, s); }
        if (step.foundIds) for (const id of step.foundIds) { const s = this.nodeStates.get(id) || {}; s.found = true; this.nodeStates.set(id, s); }
        if (step.nodeId !== null && step.nodeId !== undefined) { const s = this.nodeStates.get(step.nodeId) || {}; s.current = true; this.nodeStates.set(step.nodeId, s); }
        if (step.bestId !== null && step.bestId !== undefined) { const s = this.nodeStates.get(step.bestId) || {}; s.best = true; this.nodeStates.set(step.bestId, s); }
        this.renderTree(this.treeData);
        this.updateUI();
        // Sonido segun la fase del paso
        if (window.SoundFX) {
            if (step.phase === 'found' || step.phase === 'done' || step.phase === 'nn-result' || step.phase === 'range-result') {
                SoundFX.found();
            } else {
                SoundFX.tick();
            }
        }
    },

    play: function () {
        if (this.playing || this.steps.length === 0) return;
        if (this.currentStepIndex >= this.steps.length - 1) this.currentStepIndex = -1;
        this.playing = true;
        if (window.SoundFX) SoundFX.activate();
        this.updateUI();
        this.playTimer = setInterval(() => {
            if (this.currentStepIndex >= this.steps.length - 1) { this.stop(); return; }
            this.goToStep(this.currentStepIndex + 1);
        }, this.playbackSpeed);
    },
    pause: function () { this.playing = false; clearInterval(this.playTimer); this.playTimer = null; this.updateUI(); },
    stop: function () { this.playing = false; clearInterval(this.playTimer); this.playTimer = null; this.updateUI(); },
    reset: function () { this.stop(); this.currentStepIndex = -1; this.nodeStates = new Map(); if (this.steps.length > 0) this.goToStep(0); else this.renderTree(this.treeData); this.updateUI(); },

    updateUI: function () {
        const sd = (id, d) => { const el = document.getElementById(id); if (el) el.disabled = d; };
        const t = this.steps.length, i = this.currentStepIndex;
        sd('btnStepBack', t === 0 || i <= 0 || this.playing);
        sd('btnStepFwd', t === 0 || i >= t - 1 || this.playing);
        sd('btnPlay', t === 0 || i >= t - 1 || this.playing);
        sd('btnPause', !this.playing);
        sd('btnReset', t === 0);
        const ind = document.getElementById('stepIndicator');
        if (ind) ind.textContent = t > 0 ? `Paso ${i + 1} / ${t}` : 'Paso 0 / 0';
        const msg = document.getElementById('stepMessage');
        if (msg) { const s = this.steps[this.currentStepIndex]; msg.innerHTML = s ? s.message : 'Construya el arbol y realice busquedas para ver el algoritmo paso a paso.'; }
        const ss = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v ?? '—'; };
        if (this.steps[this.currentStepIndex]) {
            const s = this.steps[this.currentStepIndex];
            ss('stateCurrent', s.nodeId ? `${s.nodeId}` : '—');
            ss('stateBest', s.bestId ? `${s.bestId}` : '—');
            ss('stateVisited', s.visitedIds?.length ?? '—');
            ss('statePruned', s.prunedIds?.length ?? '—');
            ss('stateDim', s.dim !== null && s.dim !== undefined ? `d${s.dim}` : '—');
            ss('stateDist', s.bestDist !== null && s.bestDist !== undefined ? s.bestDist.toFixed(2) : '—');
        } else {
            ['stateCurrent','stateBest','stateVisited','statePruned','stateDim','stateDist'].forEach(id => ss(id, '—'));
        }
    },

    // ============================================================
    //  EFECTOS VISUALES
    // ============================================================

    /**
     * Genera estrellas para el fondo espacial.
     */
    generateStars: function (w, h) {
        if (this.stars.length > 0) return;
        for (var i = 0; i < 120; i++) {
            this.stars.push({
                x: Math.random() * w,
                y: Math.random() * h,
                size: 0.5 + Math.random() * 1.5,
                twinkle: 0.3 + Math.random() * 0.7,
                phase: Math.random() * Math.PI * 2,
            });
        }
    },

    /**
     * Genera copos de nieve cayendo (solo dark mode).
     */
    generateSnowflakes: function (w, h) {
        if (this.snowflakes.length > 0) return;
        var count = 20 + Math.floor(Math.random() * 10);
        for (var i = 0; i < count; i++) {
            this.snowflakes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                size: 1.5 + Math.random() * 3,
                speed: 0.2 + Math.random() * 0.5,
                sway: 0.3 + Math.random() * 0.6,
                swaySpeed: 0.003 + Math.random() * 0.008,
                phase: Math.random() * Math.PI * 2,
            });
        }
    },

    /**
     * Anade una onda expansiva en la posicion dada.
     */
    addRipple: function (x, y) {
        this.ripples.push({ x: x, y: y, radius: 5, alpha: 0.6, maxRadius: 60 });
    },

    /**
     * Inicia la animacion de efectos (ripples, particulas, aristas).
     */
    startEffects: function () {
        if (this.animFrame) return;
        var self = this;
        // Inicializar particulas
        this.particles = [];
        for (var i = 0; i < 30; i++) {
            this.particles.push({
                x: (Math.random() - 0.5) * 400,
                y: (Math.random() - 0.5) * 400,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3 - 0.1,
                size: 1 + Math.random() * 2,
                alpha: 0.2 + Math.random() * 0.3,
            });
        }
        function frame() {
            self.updateEffects();
            if (self.treeData) {
                self.renderTree(self.treeData);
            }
            self.animFrame = requestAnimationFrame(frame);
        }
        frame();
    },

    /**
     * Actualiza todos los efectos cada frame.
     */
    updateEffects: function () {
        var s = this._nodeScale || 1;

        // Ripples
        for (var i = this.ripples.length - 1; i >= 0; i--) {
            var r = this.ripples[i];
            r.radius += 2 * s;
            r.alpha *= 0.97;
            if (r.alpha < 0.01 || r.radius > r.maxRadius) {
                this.ripples.splice(i, 1);
            }
        }

        // Particulas
        for (var j = 0; j < this.particles.length; j++) {
            var p = this.particles[j];
            p.x += p.vx * s;
            p.y += p.vy * s;
            // Rebote suave en los bordes
            if (Math.abs(p.x) > 250) p.vx *= -1;
            if (Math.abs(p.y) > 250) p.vy *= -1;
            p.alpha += (Math.random() - 0.5) * 0.02;
            p.alpha = Math.max(0.1, Math.min(0.5, p.alpha));
        }

        // Tiempo de animacion de aristas
        this.edgeTime = (this.edgeTime || 0) + 0.02;

        // Copos de nieve
        var w = this.width, h = this.height;
        for (var si = 0; si < this.snowflakes.length; si++) {
            var sn = this.snowflakes[si];
            sn.y += sn.speed * s;
            sn.x += Math.sin(this.edgeTime * 2 + sn.phase) * sn.sway * 0.15;
            if (sn.y > h + 10) {
                sn.y = -10;
                sn.x = Math.random() * w;
            }
            if (sn.x < -10) sn.x = w + 10;
            if (sn.x > w + 10) sn.x = -10;
        }

        // Zoom suave
        if (this.zoomAnimating && this.zoomTarget) {
            var t = this.zoomTarget;
            this.scale += (t.scale - this.scale) * 0.1;
            this.offsetX += (t.x - this.offsetX) * 0.1;
            this.offsetY += (t.y - this.offsetY) * 0.1;
            if (Math.abs(this.scale - t.scale) < 0.01 &&
                Math.abs(this.offsetX - t.x) < 0.5 &&
                Math.abs(this.offsetY - t.y) < 0.5) {
                this.scale = t.scale;
                this.offsetX = t.x;
                this.offsetY = t.y;
                this.zoomAnimating = false;
                this.zoomTarget = null;
            }
        }
    },

    /**
     * Hace zoom suave para centrar un nodo.
     */
    smoothZoomTo: function (node) {
        if (!node || !this.canvas) return;
        var pos = this.worldToScreen(node.wx, node.wy);
        var cx = this.width / 2;
        var cy = 60;
        var targetScale = Math.min(3, Math.max(0.5, this.scale * 1.8));
        var targetOffsetX = (cx - node.wx * targetScale) / targetScale;
        var targetOffsetY = (cy - node.wy * targetScale) / targetScale;
        this.zoomTarget = { x: targetOffsetX, y: targetOffsetY, scale: targetScale };
        this.zoomAnimating = true;
    },

    /**
     * Dibuja los efectos sobre el canvas.
     */
    drawEffects: function (ctx) {
        var s = this._nodeScale || 1;

        // Particulas
        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        for (var i = 0; i < this.particles.length; i++) {
            var p = this.particles[i];
            ctx.globalAlpha = p.alpha;
            ctx.beginPath();
            ctx.arc(p.x + this.width / 2, p.y + 60, p.size, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;

        // Ripples
        for (var j = 0; j < this.ripples.length; j++) {
            var r = this.ripples[j];
            ctx.beginPath();
            ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(218,41,28,' + r.alpha + ')';
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }
    },

    /**
     * Detiene la animacion de efectos.
     */
    stopEffects: function () {
        if (this.animFrame) {
            cancelAnimationFrame(this.animFrame);
            this.animFrame = null;
        }
    },

    // ---- Render ----
    renderTree: function (treeData) {
        this.treeData = treeData;
        if (!this.canvas || !this.ctx) return;
        const ctx = this.ctx, w = this.width, h = this.height;
        const dpr = this._dpr || 1;
        // Factor de escala que ajusta nodos y texto al tamano del canvas
        this._nodeScale = Math.min(1.2, Math.max(0.45, h / 550));
        ctx.save();
        if (dpr > 1) ctx.scale(dpr, dpr);
        ctx.shadowBlur = 0; ctx.shadowColor = 'transparent';
        ctx.clearRect(0, 0, w, h);

        // Fondo espacial (solo en modo oscuro)
        var isLight = document.documentElement.getAttribute('data-theme') === 'light';
        if (!isLight) {
            const bg = ctx.createRadialGradient(w * 0.3, h * 0.4, 0, w * 0.3, h * 0.4, Math.max(w, h) * 0.8);
            bg.addColorStop(0, '#141b33'); bg.addColorStop(0.5, '#0d1528'); bg.addColorStop(1, '#070d1a');
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, w, h);

            // Nebulosa sutil
            const neb = ctx.createRadialGradient(w * 0.7, h * 0.3, 0, w * 0.7, h * 0.3, Math.max(w, h) * 0.5);
            neb.addColorStop(0, 'rgba(40,20,60,0.12)'); neb.addColorStop(0.5, 'rgba(20,10,40,0.06)'); neb.addColorStop(1, 'transparent');
            ctx.fillStyle = neb;
            ctx.fillRect(0, 0, w, h);

            // Estrellas
            this.generateStars(w, h);
            var et = this.edgeTime || 0;
            for (var si = 0; si < this.stars.length; si++) {
                var st = this.stars[si];
                var alpha = 0.3 + Math.sin(et * 2 + st.phase) * 0.3 * st.twinkle;
                ctx.fillStyle = 'rgba(255,255,255,' + (alpha * 0.7) + ')';
                ctx.beginPath();
                ctx.arc(st.x % w, st.y % h, st.size, 0, Math.PI * 2);
                ctx.fill();
            }

        }
        // Copos de nieve (ambos modos)
        this.generateSnowflakes(w, h);
        var snowColor = isLight ? 'rgba(180,210,240,0.55)' : 'rgba(255,255,255,0.5)';
        ctx.fillStyle = snowColor;
        for (var fi = 0; fi < this.snowflakes.length; fi++) {
            var sn = this.snowflakes[fi];
            ctx.beginPath();
            ctx.arc(sn.x, sn.y, sn.size, 0, Math.PI * 2);
            ctx.fill();
        }
        // Dibujar titulo del canvas
        this.drawTitle(ctx, w, h, isLight);

        if (!treeData) {
            ctx.fillStyle = 'rgba(255,255,255,0.15)'; ctx.font = '18px sans-serif';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText('Construya el KD-Tree para visualizar', w / 2, h / 2);
            return;
        }

        // Recolectar nodos por nivel y posicionarlos
        this.nodes = [];
        this.edges = [];
        this.layoutTree(treeData, w, h);

        this.pulseHighlight();
        this.drawEdges(ctx);
        this.drawNodes(ctx);
        this.drawEffects(ctx);
        this.drawLegend(ctx, w, h);
        // Info del nodo seleccionado
        if (this.selectedNode) {
            this.drawNodeInfo(ctx, this.selectedNode);
        }
        ctx.restore();
    },

    /**
     * Layout por niveles: cada nivel es una fila horizontal.
     */
    layoutTree: function (node, w, h) {
        // Recolectar todos los nodos por nivel
        const levels = [];
        const collect = (n, level) => {
            if (!levels[level]) levels[level] = [];
            levels[level].push(n);
            if (n.children) n.children.forEach(c => collect(c, level + 1));
        };
        collect(node, 0);

        const numLevels = levels.length;
        const vertSpacing = Math.min(70, (h - 60) / Math.max(1, numLevels));

        // Posicionar cada nivel
        const nodeMap = new Map(); // id -> nodeObj

        levels.forEach((levelNodes, level) => {
            const y = level * vertSpacing;
            const spacing = Math.min(60, (w - 40) / Math.max(1, levelNodes.length));

            levelNodes.forEach((n, i) => {
                const x = -((levelNodes.length - 1) * spacing) / 2 + i * spacing;
                const cust = this.customPositions[n.id];
                const nodeObj = {
                    wx: cust ? cust.wx : x,
                    wy: cust ? cust.wy : y,
                    level, dim: n.dim || 0, split: n.split || 0, point: n, children: []
                };
                this.nodes.push(nodeObj);
                nodeMap.set(n.id, nodeObj);
            });
        });

        // Crear aristas
        const link = (n) => {
            const parent = nodeMap.get(n.id);
            if (!parent || !n.children) return;
            n.children.forEach(c => {
                const child = nodeMap.get(c.id);
                if (child) {
                    this.edges.push({ from: parent, to: child });
                    link(c);
                }
            });
        };
        link(node);
    },

    drawEdges: function (ctx) {
        var et = this.edgeTime || 0;
        for (var i = 0; i < this.edges.length; i++) {
            var edge = this.edges[i];
            if (!edge.to) continue;
            var p1 = this.worldToScreen(edge.from.wx, edge.from.wy);
            var p2 = this.worldToScreen(edge.to.wx, edge.to.wy);
            var state = this.nodeStates.get(edge.to.point.id);
            var isPruned = state?.pruned;
            var level = edge.from.level;
            var isDark = document.documentElement.getAttribute('data-theme') !== 'light';

            var thickness = Math.max(0.5, 4 - level * 0.35);
            var r = this.getNodeRadius();
            var shrink = r * 0.45;

            ctx.beginPath(); ctx.moveTo(p1.x, p1.y + shrink); ctx.lineTo(p2.x, p2.y - shrink);
            if (isPruned) {
                ctx.strokeStyle = 'rgba(239,68,68,0.3)'; ctx.lineWidth = 1.5;
                ctx.setLineDash([4, 4]); ctx.stroke(); ctx.setLineDash([]);
            } else {
                ctx.strokeStyle = isDark
                    ? 'rgba(148,163,184,' + Math.max(0.1, 0.35 - level * 0.03) + ')'
                    : 'rgba(100,116,139,' + Math.max(0.15, 0.4 - level * 0.03) + ')';
                ctx.lineWidth = thickness;
                ctx.stroke();

                // Flujo animado en aristas (puntos moviendose)
                if (level < 5 && thickness > 1) {
                    var dashLen = 4;
                    var gapLen = 10 + level * 3;
                    var totalLen = dashLen + gapLen;
                    var offset = (et * 40 + i * 7) % totalLen;
                    ctx.beginPath();
                    ctx.moveTo(p1.x, p1.y + shrink);
                    ctx.lineTo(p2.x, p2.y - shrink);
                    ctx.strokeStyle = isDark
                        ? 'rgba(218,41,28,' + Math.max(0.1, 0.3 - level * 0.03) + ')'
                        : 'rgba(218,41,28,' + Math.max(0.15, 0.4 - level * 0.03) + ')';
                    ctx.lineWidth = Math.max(0.5, thickness * 0.4);
                    ctx.setLineDash([dashLen, gapLen]);
                    ctx.lineDashOffset = -offset;
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.lineDashOffset = 0;
                }
            }
        }
    },

    drawNodeInfo: function (ctx, node) {
        if (!node || !node.point || !this.selectedNodePos) return;
        const s = Math.min(1, (this._nodeScale || 1) * 0.75);
        const alpha = this.infoAnimAlpha || 1;
        const coords = node.point.coordinates || node.point;
        const dimColor = this.dimColors[node.dim % this.dimColors.length];

        const npos = this.selectedNodePos;
        const pad = Math.round(10 * s);
        const titleSize = Math.round(17 * s);
        const infoSize = Math.round(15 * s);
        const lineH = Math.round(24 * s);
        const labelW = Math.round(75 * s);
        const valueW = Math.round(55 * s);
        const colW = labelW + valueW;
        const w = Math.round(270 * s);
        const h = Math.round(130 * s);

        let px = npos.x + 12;
        let py = npos.y - h / 2;
        if (px + w > this.width) px = npos.x - w - 12;
        if (py < 4) py = 4;
        if (py + h > this.height - 4) py = this.height - h - 4;

        ctx.shadowColor = 'rgba(0,0,0,0.5)'; ctx.shadowBlur = 12;
        ctx.fillStyle = `rgba(13,17,23,${0.93 * alpha})`;
        ctx.beginPath(); ctx.roundRect(px, py, w, h, 7); ctx.fill();
        ctx.shadowBlur = 0;

        ctx.strokeStyle = dimColor; ctx.lineWidth = 1;
        ctx.shadowColor = dimColor; ctx.shadowBlur = 4;
        ctx.beginPath(); ctx.roundRect(px, py, w, h, 7); ctx.stroke();
        ctx.shadowBlur = 0;

        ctx.fillStyle = dimColor;
        ctx.font = `bold ${titleSize}px Open Sans, sans-serif`;
        ctx.textAlign = 'left'; ctx.textBaseline = 'top';
        ctx.globalAlpha = alpha;
        ctx.fillText(`ID: ${node.point.id}`, px + pad, py + pad);

        ctx.fillStyle = `rgba(255,255,255,${0.4 * alpha})`;
        ctx.font = `${infoSize}px Open Sans, sans-serif`;
        ctx.textAlign = 'right';
        ctx.fillText(`d${node.dim}=${node.split.toFixed(1)}`, px + w - pad, py + pad + 1);

        ctx.fillStyle = `rgba(255,255,255,${0.06 * alpha})`;
        ctx.fillRect(px + pad, py + pad + Math.round(20 * s), w - pad * 2, 1);

        for (let i = 0; i < Math.min(6, coords.length); i++) {
            const v = typeof coords[i] === 'number' ? coords[i] : parseFloat(coords[i]);
            const dc = this.dimColors[i % this.dimColors.length];
            const row = Math.floor(i / 2);
            const col = i % 2;
            const xOff = px + pad + col * (colW + Math.round(6 * s));
            const yOff = py + pad + Math.round(25 * s) + row * lineH;

            ctx.beginPath(); ctx.arc(xOff + 4, yOff + 5, 3, 0, Math.PI * 2);
            ctx.fillStyle = dc; ctx.globalAlpha = alpha; ctx.fill();

            ctx.fillStyle = dc;
            ctx.font = `${infoSize}px Open Sans, sans-serif`;
            ctx.textAlign = 'left';
            ctx.fillText(['CPU','Mem','HANA','DRT','WP','EL'][i], xOff + 9, yOff + 1);

            const valStr = isNaN(v) ? '—' : v.toFixed(1);
            const unit = i < 3 ? '%' : i === 3 ? 'ms' : '';
            ctx.fillStyle = `rgba(255,255,255,${0.7 * alpha})`;
            ctx.textAlign = 'right';
            ctx.fillText(`${valStr}${unit}`, xOff + colW - 1, yOff + 1);
        }

        ctx.globalAlpha = 1;
    },

    drawNodes: function (ctx) {
        const r = this.getNodeRadius();
        for (const node of this.nodes) {
            const pos = this.worldToScreen(node.wx, node.wy);
            const isHover = this.hoveredNode === node;
            const isHL = node.point.id === this.highlightedNodeId;
            const isSel = this.selectedNode && node.point.id === this.selectedNode.point.id;
            const state = this.nodeStates.get(node.point.id);
            const isCurrent = state?.current || isHL;
            const isBest = state?.best;
            const isVisited = state?.visited;
            const isPruned = state?.pruned;
            const isFound = state?.found;

            const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
            const dimColor = this.dimColors[node.dim % this.dimColors.length];

            // Color por dimension de corte: mas intenso cerca de raiz, mas claro en hojas
            const dimIntensity = Math.max(0.45, 1 - node.level * 0.05);
            let color = dimColor;
            let borderColor = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.12)';
            let borderW = 1;
            let glow = false;

            if (isSel) { color = '#ff5722'; borderColor = '#ff5722'; borderW = 3; glow = true; }
            else if (isCurrent) { color = '#ff5722'; borderColor = '#ff5722'; borderW = 2.5; glow = true; }
            else if (isBest) { color = '#ffc107'; borderColor = '#ffc107'; borderW = 2.5; glow = true; }
            else if (isFound) { color = '#4caf50'; borderColor = '#4caf50'; borderW = 2; }
            else if (isPruned) { color = '#546e7a'; borderColor = 'rgba(84,110,122,0.3)'; borderW = 1; }
            else if (isVisited) { color = '#42a5f5'; borderColor = '#42a5f5'; borderW = 2; }
            else if (isHover) { color = '#ffc107'; borderColor = '#ffc107'; borderW = 2; glow = true; }
            else if (node.level === 0) { color = '#fbbf24'; borderColor = '#fbbf24'; borderW = 2.5; }
            else {
                // Color base de la dimension con intensidad por nivel
                color = this.lerpColor(dimColor, isDark ? '#0f1724' : '#eef2f7', 1 - dimIntensity);
            }

            if (glow) {
                ctx.beginPath(); ctx.arc(pos.x, pos.y, r + 4, 0, Math.PI * 2);
                ctx.fillStyle = color + '33'; ctx.fill();
            }
            if (isSel) {
                const pulseR = r + 6 + Math.sin(Date.now() / 200) * 3;
                ctx.beginPath(); ctx.arc(pos.x, pos.y, pulseR, 0, Math.PI * 2);
                ctx.strokeStyle = `rgba(218,41,28,${0.3 + Math.sin(Date.now() / 200) * 0.15})`;
                ctx.lineWidth = 1.5; ctx.setLineDash([4, 6]); ctx.stroke(); ctx.setLineDash([]);
            }

            ctx.beginPath(); ctx.arc(pos.x, pos.y, r, 0, Math.PI * 2);
            ctx.fillStyle = color; ctx.fill();
            ctx.strokeStyle = borderColor; ctx.lineWidth = borderW; ctx.stroke();

            // Etiqueta ID
            const s = this._nodeScale || 1;
            ctx.fillStyle = '#fff';
            ctx.font = `600 ${Math.round(13 * s)}px Open Sans, sans-serif`;
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(`${node.point.id}`, pos.x, pos.y);

            if (isHover) {
                ctx.fillStyle = 'rgba(255,255,255,0.4)';
                ctx.font = `${Math.round(13 * s)}px Open Sans, sans-serif`;
                ctx.textAlign = 'center'; ctx.textBaseline = 'top';
                const name = node.point.name || node.point.server_name || '';
                ctx.fillText(name.length > 14 ? name.substring(0, 13) + '..' : name, pos.x, pos.y + r + 4);
            }
        }
    },

    drawTitle: function (ctx, w, h, isLight) {
        const s = this._nodeScale || 1;
        const fsTitle = Math.round(Math.max(12, 14 * s));
        const fsInfo = Math.round(Math.max(9, 11 * s));
        const y = 10;
        ctx.save();
        ctx.shadowBlur = 0; ctx.shadowColor = 'transparent';
        ctx.font = `bold ${fsTitle}px Open Sans, sans-serif`;
        ctx.textAlign = 'left'; ctx.textBaseline = 'top';
        ctx.fillStyle = isLight ? '#dc3545' : '#ff6b6b';
        ctx.fillText('Visualizacion del KD-Tree', 12, y);
        ctx.font = `${fsInfo}px Open Sans, sans-serif`;
        ctx.fillStyle = isLight ? '#6c757d' : '#adb5bd';
        ctx.fillText('Rueda zoom · Arrastrar · Click info', 12, y + fsTitle + 4);
        ctx.restore();
    },

    drawLegend: function (ctx, w, h) {
        const s = this._nodeScale || 1;
        const items = ['Raiz', 'Nodo', 'Visitado', 'Actual', 'Mejor', 'Podado'];
        const cols = ['#fbbf24', '#3b82f6', '#42a5f5', '#ff5722', '#ffc107', '#94a3b8'];
        const circleR = Math.round(Math.max(5, 6 * s));
        const spacing = Math.round(Math.max(18, 23 * s));
        const fontSize = Math.round(Math.max(12, 14 * s));
        const pad = Math.round(12 * s);
        // Medir el ancho maximo de los textos para calcular boxW
        ctx.font = `bold ${fontSize}px Open Sans, sans-serif`;
        let maxTextW = 0;
        for (const item of items) {
            const tw = ctx.measureText(item).width;
            if (tw > maxTextW) maxTextW = tw;
        }
        const boxW = Math.ceil(maxTextW + circleR * 2 + pad * 2 + Math.round(8 * s));
        const boxH = items.length * spacing + Math.round(10 * s);
        const px = w - boxW, py = 10;
        const textX = px + boxW - pad - circleR - Math.round(4 * s);
        const circleX = px + boxW - pad - Math.round(2 * s);
        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
        ctx.save();
        ctx.shadowBlur = 0; ctx.shadowColor = 'transparent';
        if (isDark) {
            ctx.fillStyle = 'rgba(13,17,23,0.88)';
            ctx.beginPath(); ctx.roundRect(px, py, boxW, boxH, 6); ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,0.08)';
            ctx.lineWidth = 1; ctx.beginPath(); ctx.roundRect(px, py, boxW, boxH, 6); ctx.stroke();
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'right';
            for (let i = 0; i < items.length; i++) {
                const y = py + Math.round(7 * s) + i * spacing;
                ctx.fillStyle = '#ffffff';
                ctx.font = `bold ${fontSize}px Open Sans, sans-serif`;
                ctx.fillText(items[i], textX, y);
                ctx.beginPath(); ctx.arc(circleX, y, circleR, 0, Math.PI * 2);
                ctx.fillStyle = cols[i]; ctx.fill();
            }
        } else {
            ctx.fillStyle = 'rgba(255,255,255,0.92)';
            ctx.beginPath(); ctx.roundRect(px, py, boxW, boxH, 6); ctx.fill();
            ctx.strokeStyle = 'rgba(0,0,0,0.12)';
            ctx.lineWidth = 1; ctx.beginPath(); ctx.roundRect(px, py, boxW, boxH, 6); ctx.stroke();
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'right';
            for (let i = 0; i < items.length; i++) {
                const y = py + Math.round(7 * s) + i * spacing;
                ctx.fillStyle = '#1a1a2e';
                ctx.font = `bold ${fontSize}px Open Sans, sans-serif`;
                ctx.fillText(items[i], textX, y);
                ctx.beginPath(); ctx.arc(circleX, y, circleR, 0, Math.PI * 2);
                ctx.fillStyle = cols[i]; ctx.fill();
            }
        }
        ctx.restore();
    },

    lerpColor: function (hex1, hex2, t) {
        const c1 = this.hexToRgb(hex1), c2 = this.hexToRgb(hex2);
        const r = Math.round(c1.r + (c2.r - c1.r) * t);
        const g = Math.round(c1.g + (c2.g - c1.g) * t);
        const b = Math.round(c1.b + (c2.b - c1.b) * t);
        return `rgb(${r},${g},${b})`;
    },
    hexToRgb: function (hex) {
        const n = parseInt(hex.replace('#', ''), 16);
        return { r: (n >> 16) & 0xFF, g: (n >> 8) & 0xFF, b: n & 0xFF };
    },
    getNodeRadius: function () { return Math.round(27 * (this._nodeScale || 1)); },
    highlightNode: function (id) { this.highlightedNodeId = id; this.animHighlightAlpha = 0; this.animHighlightDir = 1; this.renderTree(this.treeData); },
    clearHighlight: function () { this.highlightedNodeId = null; this.renderTree(this.treeData); },
    pulseHighlight: function () { if (this.highlightedNodeId !== null) { this.animHighlightAlpha += 0.05 * this.animHighlightDir; if (this.animHighlightAlpha > 1) this.animHighlightDir = -1; if (this.animHighlightAlpha < 0) this.animHighlightDir = 1; } },

    clearCustomPositions: function () { this.customPositions = {}; },

    clear: function () {
        this.stopEffects();
        this.treeData = null; this.nodes = []; this.edges = [];
        this.highlightedNodeId = null; this.hoveredNode = null;
        this.customPositions = {};
        this.scale = 1; this.offsetX = 0; this.offsetY = 0;
        this.steps = []; this.currentStepIndex = -1; this.stop(); this.nodeStates = new Map();
        if (this.ctx && this.canvas) this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.updateZoomDisplay(); this.updatePanDisplay(); this.updateUI();
    },
    updateZoomDisplay: function () { const el = document.getElementById('zoomLevelDisplay'); if (el) el.textContent = `Zoom: ${Math.round(this.scale * 100)}%`; },
    updatePanDisplay: function () { const el = document.getElementById('panOffsetDisplay'); if (el) el.textContent = `Pan: (${this.offsetX.toFixed(1)}, ${this.offsetY.toFixed(1)})`; },
};

if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, r) {
        if (r > w/2) r = w/2; if (r > h/2) r = h/2;
        this.moveTo(x+r, y); this.arcTo(x+w, y, x+w, y+h, r); this.arcTo(x+w, y+h, x, y+h, r); this.arcTo(x, y+h, x, y, r); this.arcTo(x, y, x+w, y, r);
        return this;
    };
}

// ============================================================
//  NATIVE CANVAS CHARTS (sin dependencia de Chart.js)
// ============================================================

const NativeCharts = {
    /**
     * Dibuja un grafico de barras simple.
     * @param {string} canvasId - ID del canvas
     * @param {Array} labels - Etiquetas del eje X
     * @param {Array} datasets - [{ label, data, color, type }]
     * @param {object} opts - { title, xLabel, yLabel, height }
     */
    drawBarChart: function (canvasId, labels, datasets, opts) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var w = canvas.width = canvas.offsetWidth;
        var h = canvas.height = (opts?.height || 280);

        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = 'rgba(10,18,30,0.5)';
        ctx.fillRect(0, 0, w, h);

        var pad = { top: 40, right: 30, bottom: 40, left: 55 };
        var pw = w - pad.left - pad.right;
        var ph = h - pad.top - pad.bottom;

        // Title
        if (opts?.title) {
            ctx.fillStyle = '#ccc';
            ctx.font = '12px Open Sans, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(opts.title, w / 2, 18);
        }

        // Y axis labels
        ctx.fillStyle = '#888'; ctx.font = '9px Open Sans, sans-serif';
        var maxVal = 0;
        for (var i = 0; i < datasets.length; i++) {
            for (var j = 0; j < datasets[i].data.length; j++) {
                maxVal = Math.max(maxVal, datasets[i].data[j]);
            }
        }
        maxVal = maxVal * 1.15 || 1;
        var ySteps = 4;
        for (var y = 0; y <= ySteps; y++) {
            var val = (maxVal * y / ySteps).toFixed(2);
            var yPos = pad.top + ph - (ph * y / ySteps);
            ctx.fillStyle = '#666'; ctx.textAlign = 'right';
            ctx.fillText(val, pad.left - 5, yPos + 3);
            ctx.strokeStyle = 'rgba(255,255,255,0.04)';
            ctx.beginPath(); ctx.moveTo(pad.left, yPos); ctx.lineTo(w - pad.right, yPos); ctx.stroke();
        }

        // Bars
        var totalBars = 0;
        for (var i = 0; i < datasets.length; i++) totalBars += datasets[i].data.length;
        var barW = Math.max(15, (pw / totalBars) * 0.7);
        var gap = (pw / totalBars) * 0.3;

        var barIdx = 0;
        for (var d = 0; d < datasets.length; d++) {
            var ds = datasets[d];
            for (var j = 0; j < ds.data.length; j++) {
                var val = ds.data[j];
                var bh = (val / maxVal) * ph;
                var bx = pad.left + barIdx * (barW + gap) + gap / 2;
                var by = pad.top + ph - bh;

                ctx.fillStyle = ds.color || '#3b82f6';
                ctx.fillRect(bx, by, barW, bh);

                ctx.fillStyle = 'rgba(255,255,255,0.15)';
                ctx.fillRect(bx, by, barW, 1);

                barIdx++;
            }
        }

        // X axis labels
        ctx.fillStyle = '#888'; ctx.font = '9px Open Sans, sans-serif';
        ctx.textAlign = 'center';
        for (var k = 0; k < labels.length; k++) {
            var lx = pad.left + (pw / labels.length) * (k + 0.5);
            ctx.fillText(labels[k], lx, h - pad.bottom + 15);
        }

        // Legend
        var lx = pad.left;
        for (var m = 0; m < datasets.length; m++) {
            ctx.fillStyle = datasets[m].color || '#3b82f6';
            ctx.fillRect(lx, h - pad.bottom + 25, 10, 10);
            ctx.fillStyle = '#ccc';
            ctx.font = '10px Open Sans, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(datasets[m].label || '', lx + 14, h - pad.bottom + 32);
            lx += ctx.measureText(datasets[m].label || '').width + 30;
        }
    },

    /**
     * Dibuja un grafico de lineas.
     */
    drawLineChart: function (canvasId, labels, datasets, opts) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var w = canvas.width = canvas.offsetWidth;
        var h = canvas.height = (opts?.height || 280);

        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle = 'rgba(10,18,30,0.5)';
        ctx.fillRect(0, 0, w, h);

        var pad = { top: 40, right: 30, bottom: 40, left: 55 };
        var pw = w - pad.left - pad.right;
        var ph = h - pad.top - pad.bottom;

        if (opts?.title) {
            ctx.fillStyle = '#ccc'; ctx.font = '12px Open Sans, sans-serif';
            ctx.textAlign = 'center'; ctx.fillText(opts.title, w / 2, 18);
        }

        // Find max
        var maxVal = 0;
        for (var i = 0; i < datasets.length; i++) {
            for (var j = 0; j < datasets[i].data.length; j++) {
                maxVal = Math.max(maxVal, datasets[i].data[j]);
            }
        }
        maxVal = maxVal * 1.15 || 1;

        // Y grid
        var ySteps = 4;
        for (var y = 0; y <= ySteps; y++) {
            var val = (maxVal * y / ySteps).toFixed(2);
            var yPos = pad.top + ph - (ph * y / ySteps);
            ctx.fillStyle = '#666'; ctx.textAlign = 'right';
            ctx.fillText(val, pad.left - 5, yPos + 3);
            ctx.strokeStyle = 'rgba(255,255,255,0.04)';
            ctx.beginPath(); ctx.moveTo(pad.left, yPos); ctx.lineTo(w - pad.right, yPos); ctx.stroke();
        }

        // Lines
        for (var d = 0; d < datasets.length; d++) {
            var ds = datasets[d];
            var pts = [];
            for (var j = 0; j < ds.data.length; j++) {
                var x = pad.left + (pw / (ds.data.length - 1)) * j;
                var y = pad.top + ph - (ds.data[j] / maxVal) * ph;
                pts.push({ x: x, y: y });
            }

            ctx.beginPath();
            ctx.moveTo(pts[0].x, pts[0].y);
            for (var k = 1; k < pts.length; k++) {
                var cx1 = pts[k - 1].x + (pts[k].x - pts[k - 1].x) * 0.5;
                var cx2 = pts[k].x - (pts[k].x - pts[k - 1].x) * 0.5;
                ctx.bezierCurveTo(cx1, pts[k - 1].y, cx2, pts[k].y, pts[k].x, pts[k].y);
            }
            ctx.strokeStyle = ds.color || '#3b82f6';
            ctx.lineWidth = 2;
            ctx.setLineDash(ds.dashed || []);
            ctx.stroke();
            ctx.setLineDash([]);

            // Dots
            for (var m = 0; m < pts.length; m++) {
                ctx.beginPath();
                ctx.arc(pts[m].x, pts[m].y, 4, 0, Math.PI * 2);
                ctx.fillStyle = ds.color || '#3b82f6';
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 0.5;
                ctx.stroke();
            }
        }

        // X labels
        ctx.fillStyle = '#888'; ctx.font = '9px Open Sans, sans-serif';
        ctx.textAlign = 'center';
        for (var l = 0; l < labels.length; l++) {
            var lx = pad.left + (pw / (labels.length - 1)) * l;
            ctx.fillText(labels[l], lx, h - pad.bottom + 15);
        }

        // Legend
        var lx = pad.left;
        for (var n = 0; n < datasets.length; n++) {
            ctx.strokeStyle = datasets[n].color || '#3b82f6';
            ctx.lineWidth = 2;
            ctx.beginPath(); ctx.moveTo(lx, h - pad.bottom + 30); ctx.lineTo(lx + 20, h - pad.bottom + 30); ctx.stroke();
            ctx.fillStyle = '#ccc'; ctx.font = '10px Open Sans, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(datasets[n].label || '', lx + 24, h - pad.bottom + 32);
            lx += ctx.measureText(datasets[n].label || '').width + 40;
        }
    },
};

document.addEventListener('DOMContentLoaded', () => {
    Renderer.init('treeCanvas');
    window.addEventListener('themechange', () => {
        if (Renderer.treeData) Renderer.renderTree(Renderer.treeData);
    });
});
