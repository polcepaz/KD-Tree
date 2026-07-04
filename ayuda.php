<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda — KD-Tree</title>
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css?v=20">
    <script src="js/theme.js"></script>
    <script src="js/fontsize.js"></script>
</head>
<body>

<nav class="sidebar d-flex flex-column">
    <div class="sidebar-logo"><h1>KD-Tree</h1><small>Ayuda</small></div>
    <ul class="nav flex-column mb-auto">
        <li class="nav-item"><span class="sidebar-heading">Navegacion</span></li>
        <li class="nav-item"><a class="nav-link" href="index.php"><span class="icon">⊞</span><span>Visualizador</span></a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="nn.php"><span class="icon">🔍</span><span>Vecino Cercano</span></a></li>
        <li class="nav-item"><a class="nav-link" href="rango.php"><span class="icon">🎯</span><span>Busqueda por Rango</span></a></li>
        <li class="nav-item"><a class="nav-link active" href="ayuda.php"><span class="icon">❓</span><span>Ayuda</span></a></li>
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
        <span class="badge-kyndryl">Ayuda y Documentacion</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="small text-secondary">Guia de uso del monitor KD-Tree SAP</span>
    </div>
</header>

<main class="main-content p-3">

    <!-- Que es un KD-Tree -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">🧠</span>
            <span>Que es un KD-Tree?</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <p>Un <strong class="text-danger">KD-Tree</strong> (k-dimensional tree) es una estructura de datos de tipo
            arbol binario de busqueda que organiza puntos en un espacio de <strong>k dimensiones</strong>.
            Fue propuesto por <em>Jon Louis Bentley</em> en 1975.</p>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);height:100%;">
                        <h6 class="text-danger mb-2">Construccion</h6>
                        <ul class="small mb-0" style="padding-left:1.2rem;line-height:2;">
                            <li>Selecciona la mediana del conjunto segun la dimension de corte</li>
                            <li>Divide el espacio en dos sub-espacios</li>
                            <li>Alterna la dimension de corte en cada nivel</li>
                            <li>Complejidad: <strong>O(n log n)</strong></li>
                            <li>Espacial: <strong>O(n)</strong></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);height:100%;">
                        <h6 class="mb-2" style="color:var(--kyndryl-red);">Busqueda</h6>
                        <ul class="small mb-0" style="padding-left:1.2rem;line-height:2;">
                            <li><strong>Vecino mas cercano</strong>: poda de ramas lejanas</li>
                            <li><strong>Busqueda por rango</strong>: recorre solo nodos dentro del rango</li>
                            <li>Complejidad promedio: <strong>O(log n)</strong></li>
                            <li>Peor caso: <strong>O(n)</strong></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-3 p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);">
                <h6 class="mb-2" style="color:var(--kyndryl-red);">Dimensiones del Monitor SAP</h6>
                <p class="small mb-0">El sistema monitoriza servidores SAP en 6 dimensiones:</p>
                <div class="row g-2 mt-2 text-center small">
                    <div class="col-4 col-md-2"><div class="p-2 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);"><strong>CPU</strong><br><span style="opacity:.6;">% uso</span></div></div>
                    <div class="col-4 col-md-2"><div class="p-2 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);"><strong>Memoria</strong><br><span style="opacity:.6;">% uso</span></div></div>
                    <div class="col-4 col-md-2"><div class="p-2 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);"><strong>HANA</strong><br><span style="opacity:.6;">% memoria</span></div></div>
                    <div class="col-4 col-md-2"><div class="p-2 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);"><strong>DRT</strong><br><span style="opacity:.6;">ms respuesta</span></div></div>
                    <div class="col-4 col-md-2"><div class="p-2 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);"><strong>WP</strong><br><span style="opacity:.6;">Work Process</span></div></div>
                    <div class="col-4 col-md-2"><div class="p-2 rounded" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);"><strong>EL</strong><br><span style="opacity:.6;">Enqueue Locks</span></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interfaz -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">🖥</span>
            <span>Interfaz del Visualizador</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-danger mb-2">Distribucion</h6>
                    <ul class="small" style="padding-left:1.2rem;line-height:2.2;">
                        <li><strong>Columna izquierda</strong> (73%): Canvas del arbol + simulacion paso a paso</li>
                        <li><strong>Columna derecha</strong> (27%): Estadisticas del arbol + Registros SAP</li>
                        <li>En mobile (&lt;992px) las columnas se apilan verticalmente</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger mb-2">Tema Oscuro / Claro</h6>
                    <ul class="small" style="padding-left:1.2rem;line-height:2.2;">
                        <li>Boton <strong>☀ / ☾</strong> en el sidebar para alternar</li>
                        <li>Modo oscuro: fondo espacial con estrellas titilantes</li>
                        <li>Copos de nieve cayendo en ambos modos</li>
                        <li>Soporte <strong>HiDPI/Retina</strong> para nitidez en pantallas de alta densidad</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Guia del Visualizador -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">⊞</span>
            <span>Visualizador — Como Usarlo</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-danger rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;">1</span>
                        <div><strong>Construir el arbol</strong><br>
                        <span class="small text-secondary">Haz clic en <span class="text-danger">"Construir"</span> en el menu lateral.
                        Selecciona la cantidad de nodos (registros SAP) y presiona "Reconstruir".
                        El arbol se dibujara en el canvas central.</span></div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-danger rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;">2</span>
                        <div><strong>Navegar el arbol</strong><br>
                        <span class="small text-secondary">Usa la <span class="text-danger">rueda del mouse</span> para hacer zoom.
                        <span class="text-danger">Arrastra</span> con el mouse para moverte por el arbol.
                        Haz <span class="text-danger">clic en un nodo</span> para ver sus metricas.</span></div>
                    </div>
                    <div class="d-flex gap-3">
                        <span class="badge bg-danger rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;">3</span>
                        <div><strong>Simulacion paso a paso</strong><br>
                        <span class="small text-secondary">Botones <span class="text-danger">⏮ ⏭ ▶ ⏸ ↺</span> debajo del canvas.
                        Recorre la construccion nodo por nodo. El contador muestra exactamente
                        <strong>N pasos / N nodos</strong>. Ajusta la velocidad con el slider.</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-danger rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;">4</span>
                        <div><strong>Menu responsive</strong><br>
                        <span class="small text-secondary">En mobile, el sidebar se oculta automaticamente.
                        Usa el <span class="text-danger">boton hamburguesa ☰</span> en el header para abrirlo.
                        Se cierra al hacer clic en un enlace o al redimensionar a desktop.</span></div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-danger rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;">5</span>
                        <div><strong>CRUD de registros</strong><br>
                        <span class="small text-secondary">Usa <span class="text-danger">"Nuevo"</span> para agregar un servidor SAP.
                        Selecciona un registro en la tabla y usa <span class="text-danger">"Eliminar"</span> para removerlo.
                        La tabla tiene scroll horizontal y altura reducida.</span></div>
                    </div>
                    <div class="d-flex gap-3">
                        <span class="badge bg-danger rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;">6</span>
                        <div><strong>Estadisticas sincronizadas</strong><br>
                        <span class="small text-secondary">La tarjeta de estadisticas hereda automaticamente la
                        altura del canvas. Los 8 indicadores se estiran para llenar el espacio disponible.
                        Si el contenido excede, aparece scroll interno.</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Busquedas -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">🔍</span>
            <span>Busquedas — Vecino Cercano y Rango</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-success rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;background:#2e7d32;">NN</span>
                        <div><strong>Vecino mas cercano</strong><br>
                        <span class="small text-secondary">Ingresa valores para las 6 dimensiones (o selecciona un servidor de la tabla)
                        y presiona <span class="text-danger">"Buscar Vecino Cercano"</span>.
                        El algoritmo recorre el arbol podando ramas lejanas y encuentra el punto con
                        menor distancia euclidiana.</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-success rounded-circle" style="width:28px;height:28px;line-height:28px;text-align:center;flex-shrink:0;background:#2e7d32;">RG</span>
                        <div><strong>Busqueda por rango</strong><br>
                        <span class="small text-secondary">Define los valores <span class="text-danger">minimos y maximos</span>
                        para cada dimension y encuentra todos los servidores dentro de ese rango.
                        El resultado se muestra en una tabla con paginacion.</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">📊</span>
            <span>Dashboard — Experimentos</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded text-center" style="background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2);height:100%;">
                        <div style="font-size:2rem;margin-bottom:.5rem;">⚡</div>
                        <h6 class="text-danger">Benchmark</h6>
                        <p class="small mb-0 text-secondary">Ejecuta multiples consultas de vecino cercano y mide el rendimiento
                        promedio del arbol. Compara contra busqueda secuencial.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded text-center" style="background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);height:100%;">
                        <div style="font-size:2rem;margin-bottom:.5rem;">📈</div>
                        <h6 class="text-danger">Variar Dimensiones</h6>
                        <p class="small mb-0 text-secondary">Construye arboles con 1D a 6D y compara tiempos.
                        Los graficos muestran como la dimensionalidad afecta el rendimiento.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded text-center" style="background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);height:100%;">
                        <div style="font-size:2rem;margin-bottom:.5rem;">📊</div>
                        <h6 class="text-danger">Escalabilidad</h6>
                        <p class="small mb-0 text-secondary">Prueba el rendimiento con diferentes tamanos de dataset
                        (100 a 5000 nodos). Grafica tiempo vs cantidad de datos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Atajos -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">⌨</span>
            <span>Atajos y Tips</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3">
                <div class="col-md-6">
                    <table class="table table-sm table-dash mb-0" style="font-size:.85rem;">
                        <thead><tr><th>Accion</th><th>Como hacerlo</th></tr></thead>
                        <tbody>
                            <tr><td>Zoom</td><td>Rueda del mouse</td></tr>
                            <tr><td>Pan (moverse)</td><td>Arrastrar con el mouse</td></tr>
                            <tr><td>Ver info del nodo</td><td>Clic en el nodo</td></tr>
                            <tr><td>Alternar tema</td><td>Boton ☀ / ☾ en el sidebar</td></tr>
                            <tr><td>Cambiar tamano texto</td><td>Botones A- / A+ en el sidebar</td></tr>
                            <tr><td>Menu responsive</td><td>Boton ☰ en el header</td></tr>
                            <tr><td>Seleccionar servidor</td><td>Clic en fila de la tabla</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);height:100%;">
                        <h6 class="mb-2" style="color:var(--kyndryl-red);">Recomendaciones</h6>
                        <ul class="small mb-0" style="padding-left:1.2rem;line-height:2.2;">
                            <li>Construye el arbol con <strong>1000-2000 nodos</strong> para mejor visualizacion</li>
                            <li>Usa el <strong>modo oscuro</strong> para ver las estrellas y copos de nieve</li>
                            <li>Activa la <strong>simulacion paso a paso</strong> para entender el algoritmo</li>
                            <li>El dashboard permite comparar rendimiento entre configuraciones</li>
                            <li>Las estadisticas se sincronizan automaticamente con el canvas</li>
                            <li>Los valores de los stats boxes usan toda la altura disponible</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estructura del proyecto -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">📁</span>
            <span>Estructura del Proyecto</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3 small">
                <div class="col-md-6">
                    <h6 class="text-danger mb-2">Frontend (JavaScript)</h6>
                    <table class="table table-sm table-dash mb-3" style="font-size:.8rem;">
                        <thead><tr><th style="width:120px;">Archivo</th><th>Responsabilidad</th></tr></thead>
                        <tbody>
                            <tr><td><code>app.js</code></td><td>Logica principal: CRUD, busquedas NN/rango, sidebar toggle, sincronizacion de alturas</td></tr>
                            <tr><td><code>renderer.js</code></td><td>Renderizador del arbol en Canvas: nodos, aristas, zoom/pan, efectos (estrellas, copos de nieve, ondas), titulo, leyenda, tooltip, info panel, HiDPI/Retina</td></tr>
                            <tr><td><code>kdEngine.js</code></td><td>Motor de pasos didactico: genera pasos de busqueda NN y rango a partir del event_log</td></tr>
                            <tr><td><code>animation.js</code></td><td>Log de eventos en pantalla con animacion de entrada</td></tr>
                            <tr><td><code>soundfx.js</code></td><td>Sonidos procedurales via Web Audio API para la simulacion paso a paso</td></tr>
                            <tr><td><code>theme.js</code></td><td>Alternancia oscuro/claro con atributo <code>data-theme</code> y persistencia en localStorage</td></tr>
                            <tr><td><code>fontsize.js</code></td><td>Control de tamano de fuente (A- / A+) con localStorage</td></tr>
                            <tr><td><code>charts.js</code></td><td>Graficos Chart.js usados en dashboard.php como alternativa</td></tr>
                        </tbody>
                    </table>
                    <h6 class="text-danger mb-2">Estilos</h6>
                    <table class="table table-sm table-dash mb-3" style="font-size:.8rem;">
                        <thead><tr><th style="width:120px;">Archivo</th><th>Contenido</th></tr></thead>
                        <tbody>
                            <tr><td><code>style.css</code></td><td>Paleta Bootstrap 5.3, modo oscuro/claro, sidebar, cards, stats, canvas, tablas, botones, scrollbar, responsive (tablet/mobile), animaciones</td></tr>
                        </tbody>
                    </table>
                    <h6 class="text-danger mb-2">Paginas HTML</h6>
                    <table class="table table-sm table-dash" style="font-size:.8rem;">
                        <thead><tr><th style="width:120px;">Pagina</th><th>Proposito</th></tr></thead>
                        <tbody>
                            <tr><td><code>index.php</code></td><td>Visualizador principal con canvas, simulacion, busquedas y registros</td></tr>
                            <tr><td><code>dashboard.php</code></td><td>Experimentos: benchmark, variar dimensiones, escalabilidad</td></tr>
                            <tr><td><code>nn.php</code></td><td>Busqueda de vecino mas cercano con formulario dedicado</td></tr>
                            <tr><td><code>rango.php</code></td><td>Busqueda por rango con tabla de resultados paginada</td></tr>
                            <tr><td><code>ayuda.php</code></td><td>Documentacion y guia de uso del sistema</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger mb-2">Backend (PHP Clases)</h6>
                    <table class="table table-sm table-dash mb-3" style="font-size:.8rem;">
                        <thead><tr><th style="width:120px;">Archivo</th><th>Responsabilidad</th></tr></thead>
                        <tbody>
                            <tr><td><code>KDTree.php</code></td><td>Implementacion completa del arbol: construir, insertar, eliminar, buscar NN, buscar por rango, serializar a JSON</td></tr>
                            <tr><td><code>KDNode.php</code></td><td>Nodo del arbol: punto k-dimensional, dimension de corte, hijos izquierdo/derecho</td></tr>
                            <tr><td><code>Point.php</code></td><td>Punto multidimensional con 6 dimensiones (CPU, Mem, HANA, DRT, WP, EL)</td></tr>
                            <tr><td><code>TreeBuilder.php</code></td><td>Constructor del arbol: ordena puntos por dimension, encuentra mediana, construye recursivamente</td></tr>
                            <tr><td><code>Distance.php</code></td><td>Metricas de distancia: Euclidiana y Manhattan con ponderacion por dimension</td></tr>
                            <tr><td><code>Metrics.php</code></td><td>Recolector de metricas: altura, profundidad, nodos hoja/internos, complejidad</td></tr>
                            <tr><td><code>Database.php</code></td><td>Capa de acceso PDO (Singleton) para MySQL 8</td></tr>
                            <tr><td><code>ServerMetrics.php</code></td><td>Modelo de datos para servidores SAP</td></tr>
                        </tbody>
                    </table>
                    <h6 class="text-danger mb-2">Endpoints PHP (API)</h6>
                    <table class="table table-sm table-dash" style="font-size:.8rem;">
                        <thead><tr><th style="width:120px;">Archivo</th><th>Metodo</th><th>Descripcion</th></tr></thead>
                        <tbody>
                            <tr><td><code>buildTree.php</code></td><td><code>POST</code></td><td>Construir/reconstruir arbol desde BD</td></tr>
                            <tr><td><code>nearestNeighbor.php</code></td><td><code>POST</code></td><td>Buscar vecino mas cercano</td></tr>
                            <tr><td><code>rangeSearch.php</code></td><td><code>POST</code></td><td>Busqueda por rango</td></tr>
                            <tr><td><code>insertPoint.php</code></td><td><code>POST</code></td><td>Insertar nuevo registro SAP</td></tr>
                            <tr><td><code>deletePoint.php</code></td><td><code>POST</code></td><td>Eliminar registro</td></tr>
                            <tr><td><code>statistics.php</code></td><td><code>GET</code></td><td>Estado del arbol, estadisticas, rebuild</td></tr>
                            <tr><td><code>experiments.php</code></td><td><code>GET</code></td><td>Benchmark, escalabilidad, variar dimensiones</td></tr>
                            <tr><td><code>getRecords.php</code></td><td><code>GET</code></td><td>Registros paginados desde BD</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Codigo fuente del KD-Tree -->
    <div class="card-dash mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">📄</span>
            <span>Codigo Fuente del Algoritmo KD-Tree (PHP)</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div style="max-height:600px;overflow-y:auto;background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);border-radius:8px;font-size:.75rem;line-height:1.6;">
<pre style="padding:1rem;margin:0;white-space:pre-wrap;word-break:break-word;"><code style="color:var(--bs-body-color);">&lt;?php

/**
 * Nodo del KD-Tree.
 * Contiene un punto k-dimensional y referencias a subarboles.
 */
class KDNode
{
    public Point $point;        // Punto almacenado
    public ?KDNode $left;       // Subarbol izquierdo
    public ?KDNode $right;      // Subarbol derecho
    public int $dimension;      // Dimension de corte en este nivel
    public int $level;          // Profundidad del nodo
    public float $splitValue;   // Valor de corte

    public function __construct(Point $point, int $dimension = 0, int $level = 0)
    {
        $this-&gt;point = $point;
        $this-&gt;left = null;
        $this-&gt;right = null;
        $this-&gt;dimension = $dimension;
        $this-&gt;level = $level;
        $this-&gt;splitValue = $point-&gt;getCoordinate($dimension);
    }

    public function isLeaf(): bool
    {
        return $this-&gt;left === null &amp;&amp; $this-&gt;right === null;
    }
}

/**
 * Punto en el espacio k-dimensional (6 dimensiones SAP).
 */
class Point
{
    private array $coordinates;  // [cpu, mem, hana, drt, wp, el]
    private int $id;             // ID del servidor
    private string $serverName;  // Nombre del servidor SAP

    public function __construct(array $coords, int $id = 0, string $name = '')
    {
        $this-&gt;coordinates = $coords;
        $this-&gt;id = $id;
        $this-&gt;serverName = $name;
    }

    public function getCoordinate(int $dim): float { return (float)($this-&gt;coordinates[$dim] ?? 0); }
    public function getCoordinates(): array       { return $this-&gt;coordinates; }
    public function getDimensions(): int           { return count($this-&gt;coordinates); }
    public function getId(): int                   { return $this-&gt;id; }
    public function getServerName(): string        { return $this-&gt;serverName; }
}

/**
 * Distancia euclidiana entre puntos multidimensionales.
 */
class Distance
{
    public static function euclidean(Point $a, Point $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i &lt; min($a-&gt;getDimensions(), $b-&gt;getDimensions()); $i++) {
            $diff = $a-&gt;getCoordinate($i) - $b-&gt;getCoordinate($i);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    public static function squaredEuclidean(Point $a, Point $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i &lt; min($a-&gt;getDimensions(), $b-&gt;getDimensions()); $i++) {
            $diff = $a-&gt;getCoordinate($i) - $b-&gt;getCoordinate($i);
            $sum += $diff * $diff;
        }
        return $sum;
    }
}

/**
 * Implementacion del KD-Tree con construccion, insercion,
 * busqueda NN, busqueda por rango y eliminacion.
 */
class KDTree
{
    private ?KDNode $root;      // Raiz del arbol
    private int $dimensions;    // Dimensionalidad (6)
    private int $size;          // Cantidad de nodos
    private int $comparisons;   // Contador de comparaciones
    private int $visitedNodes;  // Contador de nodos visitados
    private array $eventLog;    // Registro de eventos

    /**
     * Construye el arbol desde un conjunto de puntos.
     * Tiempo: O(n log n)
     */
    public function build(array $points): float
    {
        $start = microtime(true);
        $this-&gt;size = count($points);
        $this-&gt;root = $this-&gt;buildRecursive($points, 0);
        $this-&gt;updateHeight();
        return (microtime(true) - $start) * 1000;
    }

    /**
     * Construccion recursiva: ordena puntos por la dimension de corte,
     * selecciona la mediana como raiz, y divide en subarboles.
     */
    private function buildRecursive(array $points, int $depth): ?KDNode
    {
        if (empty($points)) return null;

        $dim = $depth % $this-&gt;dimensions;  // Alternar dimension

        // Ordenar por la dimension actual
        usort($points, function (Point $a, Point $b) use ($dim) {
            return $a-&gt;getCoordinate($dim) &lt;=&gt; $b-&gt;getCoordinate($dim);
        });

        $medianIndex = intdiv(count($points), 2);
        $node = new KDNode($points[$medianIndex], $dim, $depth);

        // Construir subarboles recursivamente
        $node-&gt;left = $this-&gt;buildRecursive(
            array_slice($points, 0, $medianIndex), $depth + 1
        );
        $node-&gt;right = $this-&gt;buildRecursive(
            array_slice($points, $medianIndex + 1), $depth + 1
        );

        return $node;
    }

    /**
     * Busca el vecino mas cercano a un punto objetivo.
     * Tiempo promedio: O(log n), peor caso: O(n)
     */
    public function nearestNeighbor(Point $target): array
    {
        $start = microtime(true);
        $this-&gt;comparisons = 0;
        $this-&gt;visitedNodes = 0;

        $best = null;
        $bestDist = INF;

        $this-&gt;nnRecursive($this-&gt;root, $target, 0, $best, $bestDist);

        return [
            'point' =&gt; $best,
            'distance' =&gt; $bestDist !== INF ? sqrt($bestDist) : -1,
            'time' =&gt; (microtime(true) - $start) * 1000,
            'comparisons' =&gt; $this-&gt;comparisons,
            'visited' =&gt; $this-&gt;visitedNodes,
        ];
    }

    /**
     * Busqueda recursiva con poda de ramas.
     * Explora primero el subarbol del mismo lado del plano de corte.
     * Solo explora el otro subarbol si la distancia al plano es menor
     * que la mejor distancia encontrada.
     */
    private function nnRecursive(
        ?KDNode $node, Point $target, int $depth,
        ?Point &amp;$best, float &amp;$bestDist
    ): void {
        if ($node === null) return;

        $this-&gt;visitedNodes++;
        $dim = $depth % $this-&gt;dimensions;

        // Calcular distancia al punto actual
        $dist = Distance::squaredEuclidean($target, $node-&gt;point);
        $this-&gt;comparisons++;

        if ($dist &lt; $bestDist) {
            $bestDist = $dist;
            $best = $node-&gt;point;
        }

        // Decidir que subarbol explorar primero
        $diff = $target-&gt;getCoordinate($dim) - $node-&gt;point-&gt;getCoordinate($dim);
        $first = $diff &lt; 0 ? $node-&gt;left : $node-&gt;right;
        $second = $diff &lt; 0 ? $node-&gt;right : $node-&gt;left;

        // Explorar el subarbol mas prometedor
        $this-&gt;nnRecursive($first, $target, $depth + 1, $best, $bestDist);

        // PODA: solo explorar el otro subarbol si la distancia al plano
        // de corte es menor que la mejor distancia actual
        if ($second !== null) {
            $planeDist = $diff * $diff;
            $this-&gt;comparisons++;
            if ($planeDist &lt; $bestDist) {
                $this-&gt;nnRecursive($second, $target, $depth + 1, $best, $bestDist);
            }
        }
    }

    /**
     * Busca todos los puntos dentro de un radio del objetivo.
     * Tiempo: O(log n + k) donde k = puntos encontrados
     */
    public function rangeSearch(Point $target, float $radius): array
    {
        $start = microtime(true);
        $this-&gt;comparisons = 0;
        $this-&gt;visitedNodes = 0;

        $result = [];
        $this-&gt;rangeRecursive($this-&gt;root, $target, $radius, 0, $result);

        usort($result, fn($a, $b) =&gt; $a['distance'] &lt;=&gt; $b['distance']);

        return [
            'points' =&gt; $result,
            'time' =&gt; (microtime(true) - $start) * 1000,
            'comparisons' =&gt; $this-&gt;comparisons,
            'visited' =&gt; $this-&gt;visitedNodes,
        ];
    }

    private function rangeRecursive(
        ?KDNode $node, Point $target, float $radius,
        int $depth, array &amp;$result
    ): void {
        if ($node === null) return;

        $this-&gt;visitedNodes++;
        $dim = $depth % $this-&gt;dimensions;
        $radiusSq = $radius * $radius;

        $dist = Distance::squaredEuclidean($target, $node-&gt;point);
        $this-&gt;comparisons++;

        if ($dist &lt;= $radiusSq) {
            $result[] = [
                'point' =&gt; $node-&gt;point-&gt;toArray(),
                'distance' =&gt; sqrt($dist),
            ];
        }

        $diff = $target-&gt;getCoordinate($dim) - $node-&gt;point-&gt;getCoordinate($dim);
        $first = $diff &lt; 0 ? $node-&gt;left : $node-&gt;right;
        $second = $diff &lt; 0 ? $node-&gt;right : $node-&gt;left;

        $this-&gt;rangeRecursive($first, $target, $radius, $depth + 1, $result);

        // Podar si la distancia al plano excede el radio
        if ($second !== null &amp;&amp; ($diff * $diff) &lt;= $radiusSq) {
            $this-&gt;rangeRecursive($second, $target, $radius, $depth + 1, $result);
        }
    }

    // --- Metodos auxiliares ---

    private function updateHeight(): void {
        $this-&gt;height = $this-&gt;calcHeight($this-&gt;root);
    }

    private function calcHeight(?KDNode $node): int {
        return $node === null ? 0
            : 1 + max($this-&gt;calcHeight($node-&gt;left), $this-&gt;calcHeight($node-&gt;right));
    }

    public function getSize(): int      { return $this-&gt;size; }
    public function getRoot(): ?KDNode  { return $this-&gt;root; }
}
</code></pre>
            </div>
            <p class="small text-secondary mt-2 mb-0">
                <strong>Leyenda:</strong> El algoritmo construye el arbol ordenando puntos por la dimension de corte
                (alternando en cada nivel), selecciona la mediana como raiz, y divide recursivamente.
                La busqueda NN poda ramas cuya distancia al plano de corte excede la mejor distancia actual,
                logrando complejidad O(log n) en promedio.
            </p>
        </div>
    </div>

    <!-- Creditos tecnicos -->
    <div class="card-dash">
        <div class="card-header d-flex align-items-center gap-2">
            <span style="font-size:1.2rem;">⚙</span>
            <span>Detalles Tecnicos</span>
        </div>
        <div class="p-3" style="line-height:1.7;">
            <div class="row g-3 small">
                <div class="col-md-6">
                    <h6 class="text-danger mb-2">Stack tecnologico</h6>
                    <ul style="padding-left:1.2rem;line-height:2.2;">
                        <li>PHP 8+ con sesiones y JSON API</li>
                        <li>MySQL 8+ con 5000 registros SAP</li>
                        <li>Bootstrap 5.3.8 local (sin CDN)</li>
                        <li>Canvas API con soporte Retina/HiDPI</li>
                        <li>Open Sans tipografia local</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger mb-2">Rendimiento</h6>
                    <ul style="padding-left:1.2rem;line-height:2.2;">
                        <li>Construccion del arbol: <strong>O(n log n)</strong></li>
                        <li>Busqueda NN promedio: <strong>O(log n)</strong></li>
                        <li>5000 puntos en 6 dimensiones</li>
                        <li>Animaciones via requestAnimationFrame</li>
                        <li>Polyfill roundRect para compatibilidad</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
