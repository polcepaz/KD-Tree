<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KD-Tree — Dashboard</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="fonts/fonts.css">
    <link rel="stylesheet" href="css/style.css?v=19">
    <script src="js/theme.js"></script>
    <script src="js/fontsize.js"></script>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar d-flex flex-column">
    <div class="sidebar-logo">
        <h1>KD-Tree</h1>
        <small>Indice Espacial</small>
    </div>
    <ul class="nav flex-column mb-2">
        <li class="nav-item">
            <span class="sidebar-heading">Acciones</span>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" id="btnBuildTree">
                <span class="icon">▶</span>
                <span>Construir</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" id="btnRebuildTree" disabled data-bs-toggle="modal" data-bs-target="#modalRebuild">
                <span class="icon">↻</span>
                <span>Reconstruir</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" id="btnInsert">
                <span class="icon">+</span>
                <span>Nuevo</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" id="btnDelete" disabled>
                <span class="icon">−</span>
                <span>Eliminar</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="ayuda.php">
                <span class="icon">❓</span>
                <span>Ayuda</span>
            </a>
        </li>
    </ul>
    <hr style="border-color:rgba(255,255,255,.08);margin:.5rem 1rem;">
    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <span class="sidebar-heading">Navegacion</span>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="index.php">
                <span class="icon">⊞</span>
                <span>Visualizador</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="nn.php">
                <span class="icon">🔍</span>
                <span>Vecino Cercano</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="rango.php">
                <span class="icon">🎯</span>
                <span>Busqueda por Rango</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="ayuda.php">
                <span class="icon">❓</span>
                <span>Ayuda</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="small" style="opacity:.6;">Tam</span>
            <button class="fontsize-down">A-</button>
            <span id="fontsizeValue" class="small">100%</span>
            <button class="fontsize-up">A+</button>
        </div>
        <button class="theme-toggle sidebar-toggle w-100">☀</button>
    </div>
</nav>

<!-- HEADER -->
<header class="navbar-theme">
    <div class="d-flex align-items-center gap-2">
        <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Menu" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        <span class="badge-kyndryl" id="treeBadge">No construido</span>
        <span class="text-secondary small d-none d-md-inline">⊞ KD-Tree</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="small text-secondary d-none d-sm-inline" id="statInfo">Nodos: <strong class="text-danger" id="statTotalNodes">0</strong></span>
    </div>
</header>

<!-- MAIN -->
<main class="main-content p-3">
    <div id="alerts"></div>

    <div class="row g-3">
        <!-- Columna izquierda: Canvas + Simulación + Busquedas -->
        <div class="col-left col-12">
            <!-- Canvas -->
            <div class="card-dash mb-3">
                <div class="card-header d-none">
                    <span class="fw-bold">Visualizacion del KD-Tree</span>
                    <span class="small ms-2 text-secondary">🖱 Rueda zoom · ✋ Arrastrar · 👆 Click info</span>
                </div>
                <div class="canvas-container" id="canvasContainer">
                    <canvas id="treeCanvas"></canvas>
                    <div class="canvas-tooltip" id="nodeTooltip"></div>
                </div>
                <div class="px-2 py-1 d-flex gap-3 small text-secondary">
                    <span id="zoomLevelDisplay">Zoom: 100%</span>
                    <span id="panOffsetDisplay">Pan: (0, 0)</span>
                </div>
            </div>

            <!-- Simulación paso a paso -->
            <div class="card-dash mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Simulación paso a paso</span>
                    <span class="badge bg-dark" id="stepIndicator">0 / 0</span>
                </div>
                <div class="p-2">
                    <div class="d-flex flex-wrap align-items-center gap-1 sim-controls">
                        <button class="btn btn-sm btn-outline-secondary" id="btnStepBack" disabled>⏮</button>
                        <button class="btn btn-sm btn-outline-success" id="btnPlay" disabled>▶</button>
                        <button class="btn btn-sm btn-outline-warning" id="btnPause" disabled>⏸</button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnStepFwd" disabled>⏭</button>
                        <button class="btn btn-sm btn-outline-danger" id="btnReset" disabled>↺</button>
                        <span class="ms-2 small text-secondary">Vel:</span>
                        <input type="range" id="speedRange" min="1" max="10" value="5" style="width:60px;height:4px;">
                        <span class="small text-secondary" id="speedLabel">5</span>
                        <span class="ms-2 small text-secondary d-none d-md-inline" id="stateInfo">
                            Actual: <strong class="text-danger" id="stateCurrent">—</strong>
                            Mejor: <strong class="text-warning" id="stateBest">—</strong>
                            Visit: <strong id="stateVisited">—</strong>
                            Podados: <strong id="statePruned">—</strong>
                        </span>
                    </div>
                </div>
                <div class="step-message-box p-2 small" id="stepMessage">
                    Construya el arbol y realice busquedas para ver el algoritmo.
                </div>
            </div>
        </div>

        <!-- Columna derecha: Stats + Registros -->
        <div class="col-right d-flex flex-column col-12">
            <!-- Stats -->
            <div class="card-dash mb-3 flex-shrink-0" id="statsCard">
                <div class="card-header">Estadisticas del Arbol</div>
                <div class="stats-scroll">
                <div class="p-2">
                    <div class="row g-1">
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statTotalNodes2">0</span><span class="label">Nodos</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statHeight2">0</span><span class="label">Altura</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statMaxDepth">0</span><span class="label">Prof. Max</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statMinDepth">0</span><span class="label">Prof. Min</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statAvgDepth">0</span><span class="label">Prof. Prom</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statLeafNodes">0</span><span class="label">Hojas</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statInternalNodes">0</span><span class="label">Internos</span></div></div>
                        <div class="col-6"><div class="stat-dash"><span class="value" id="statDimensions2">6</span><span class="label">Dimensiones</span></div></div>
                    </div>
                </div>
                </div><!-- .stats-scroll -->
                <div class="p-2 small text-secondary d-flex flex-wrap gap-2">
                    <span>Complejidad: <strong id="statComplexityTime">O(log n)</strong></span>
                    <span>Espacial: <strong id="statComplexitySpace">O(n)</strong></span>
                </div>
            </div>

            <!-- Registros SAP -->
            <div class="card-dash d-flex flex-column">
                <div class="card-header d-flex justify-content-between align-items-center flex-shrink-0">
                    <span>Registros SAP</span>
                    <input type="text" class="form-control form-control-dash" id="searchRecordInput" placeholder="Buscar ID..." style="width:140px;" autofocus>
                </div>
                <div id="recordsLoading" class="text-center py-3 small text-secondary" style="display:none;">Cargando...</div>
                <div class="table-responsive flex-fill" style="overflow-y:auto;height:10.67vh;">
                    <table class="table table-dash table-hover mb-0 w-100">
                        <thead class="sticky-top table-header-dash">
                            <tr><th>ID</th><th>Servidor</th><th>CPU</th><th>Mem</th></tr>
                        </thead>
                        <tbody id="recordsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- ^^^ right column -->
    </div>
    <!-- ^^^ row -->
</main>

<!-- MODALS -->
<div class="modal fade" id="modalRebuild" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-danger">Reconstruir</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label small">Numero de nodos</label>
                <input type="number" class="form-control form-control-dash" id="rebuildNodeCount" min="1" value="1000">
                <small class="text-secondary" id="rebuildInfo">Selecciona N registros aleatorios.</small>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-kyndryl btn-sm" id="btnConfirmRebuild" data-bs-dismiss="modal">Reconstruir</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInsert" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-danger">Nuevo Registro SAP</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="formInsert">
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small">Servidor</label><input type="text" class="form-control form-control-dash" id="insertServerName" required></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">CPU (%)</label><input type="number" class="form-control form-control-dash" id="insertCpu" step="0.1" required></div>
                        <div class="col-6"><label class="form-label small">Memoria (%)</label><input type="number" class="form-control form-control-dash" id="insertMem" step="0.1" required></div>
                        <div class="col-6"><label class="form-label small">HANA (%)</label><input type="number" class="form-control form-control-dash" id="insertHana" step="0.1" required></div>
                        <div class="col-6"><label class="form-label small">DRT (ms)</label><input type="number" class="form-control form-control-dash" id="insertDrt" step="1" required></div>
                        <div class="col-6"><label class="form-label small">Work Processes</label><input type="number" class="form-control form-control-dash" id="insertWp" required></div>
                        <div class="col-6"><label class="form-label small">Enqueue Locks</label><input type="number" class="form-control form-control-dash" id="insertEl" required></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-kyndryl btn-sm">Insertar</button></div>
            </form>
        </div>
    </div>
</div>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="js/soundfx.js"></script>
<script src="js/renderer.js?v=22"></script>
<script src="js/animation.js?v=5"></script>
<script src="js/kdEngine.js?v=5"></script>
<script src="js/app.js?v=8"></script>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
</body>
</html>
