# KD-Tree — Índice Espacial para Búsqueda Multidimensional en Infraestructura SAP

**Proyecto de Investigación Doctoral**  
Estructuras de Datos y Algoritmos Avanzados

---

## Resumen

Implementación completa desde cero de un **KD-Tree (K-Dimensional Tree)** aplicado al análisis multidimensional de métricas de infraestructura SAP. La aplicación permite construir un índice espacial sobre 6 dimensiones (CPU, Memoria, HANA, DRT, WP, EL) y realizar búsquedas eficientes mediante **Nearest Neighbor (NN)** y **Range Search**, con visualización interactiva, simulación paso a paso, y experimentos comparativos.

---

## Páginas del Sistema

| Página | Descripción |
|--------|-------------|
| `index.php` | Visualizador principal: canvas interactivo, estadísticas, registros SAP, simulación paso a paso, búsquedas NN y rango |
| `dashboard.php` | Experimentos: benchmark, variación de dimensiones, escalabilidad con gráficos nativos |
| `nn.php` | Búsqueda de vecino más cercano con formulario dedicado |
| `rango.php` | Búsqueda por rango con tabla de resultados paginada |
| `ayuda.php` | Documentación completa y guía de uso |

---

## Arquitectura

```
Usuario → HTML5 → CSS3 → JavaScript ES6 → Fetch API → PHP 8 → MySQL 8
                                                  ↓
                                     Implementación KD-Tree (desde cero)
```

| Componente | Tecnología |
|---|---|
| **Frontend** | HTML5, CSS3, JavaScript ES6, Bootstrap 5.3.8 local, Canvas API |
| **Backend** | PHP 8, POO, SOLID |
| **Base de Datos** | MySQL 8 (5000 registros) |
| **Comunicación** | Fetch API + JSON + AJAX |
| **Visualización** | Canvas (árbol interactivo con zoom/pan, HiDPI/Retina) |
| **Servidor** | XAMPP (Apache + MySQL + PHP) |

---

## Estructura del Proyecto

```
web9/
├── index.php                 # Visualizador principal
├── dashboard.php             # Dashboard de experimentos
├── nn.php                    # Búsqueda vecino cercano
├── rango.php                 # Búsqueda por rango
├── ayuda.php                 # Documentación y ayuda
├── css/style.css             # Estilos completos (oscuro/claro, responsive)
├── fonts/                    # Fuentes locales (Open Sans)
├── js/
│   ├── app.js                # Lógica principal (CRUD, búsquedas, sidebar, sync stats)
│   ├── renderer.js           # Renderizador del árbol (Canvas) + estrellas + copos de nieve
│   ├── animation.js          # Log de eventos
│   ├── kdEngine.js           # Motor de pasos didáctico
│   ├── soundfx.js            # Sonidos procedurales (Web Audio API)
│   ├── theme.js              # Tema oscuro/claro con localStorage
│   ├── fontsize.js           # Control de tamaño de fuente
│   └── charts.js             # Gráficos Chart.js (alternativo)
├── classes/
│   ├── KDTree.php            # Implementación completa del KD-Tree
│   ├── KDNode.php            # Nodo del árbol
│   ├── Point.php             # Punto multidimensional
│   ├── Distance.php          # Métricas de distancia
│   ├── TreeBuilder.php       # Constructor del árbol
│   └── Metrics.php           # Recolección de métricas
├── php/
│   ├── buildTree.php         # Endpoint: construir árbol
│   ├── nearestNeighbor.php   # Endpoint: búsqueda NN
│   ├── rangeSearch.php       # Endpoint: búsqueda por rango
│   ├── insertPoint.php       # Endpoint: insertar registro
│   ├── deletePoint.php       # Endpoint: eliminar registro
│   ├── statistics.php        # Endpoint: estadísticas + rebuild
│   ├── experiments.php       # Endpoint: experimentos (escalabilidad, benchmark, dimensiones)
│   └── getRecords.php        # Endpoint: obtener registros paginados
├── database/
│   ├── Database.php          # Capa de acceso PDO (Singleton)
│   └── ServerMetrics.php     # Modelo de datos
├── sql/
│   ├── schema.sql            # Esquema de BD
│   └── seed.php              # Generador de datos semilla
└── bootstrap-5.3.8-dist/     # Bootstrap 5.3.8 local
```

---

## Instalación

### Requisitos
- XAMPP (PHP 8+, MySQL 8+, Apache)
- Navegador moderno

### Pasos
```bash
# 1. Clonar o copiar en htdocs
cd C:/xampp/htdocs

# 2. Crear base de datos
"C:/xampp/mysql/bin/mysql.exe" -u root < web9/sql/schema.sql

# 3. Generar datos semilla
"C:/xampp/php/php.exe" web9/sql/seed.php

# 4. Abrir en navegador
http://localhost/web9/index.php
```

---

## Algoritmos Implementados

| Algoritmo | Complejidad | Descripción |
|---|---|---|
| **Construcción** | O(n log n) | Mediana recursiva alternando dimensiones |
| **Inserción** | O(log n) | Descenso recursivo por dimensión de corte |
| **Eliminación** | O(n) | Reconstrucción del árbol sin el nodo |
| **Nearest Neighbor** | O(log n) avg | Recorrido con poda de ramas imposibles |
| **Range Search** | O(log n + k) | Búsqueda con poda de subárboles fuera del radio |
| **Balanceo** | O(n log n) | Reconstrucción desde recorrido inorder |

---

## Funcionalidades

### Visualizador
- Árbol interactivo con zoom (rueda) y panorámica (arrastre)
- Clic en nodos para ver métricas detalladas
- Simulación paso a paso con controles de reproducción (⏮ ⏭ ▶ ⏸ ↺)
- Contador exacto: N pasos = N nodos
- Soporte HiDPI/Retina para nitidez en pantallas de alta densidad
- Copos de nieve cayendo (modo oscuro y claro)
- Fondo espacial con estrellas titilantes y nebulosa (dark mode)

### Interfaz
- Sidebar responsive con menú hamburguesa (☰) en mobile
- Columnas: izquierda 73% (canvas) + derecha 27% (stats + registros)
- Tarjeta de estadísticas sincronizada automáticamente con la altura del canvas
- 8 indicadores en grid que se estiran para llenar el espacio disponible
- Tabla de registros con altura optimizada y scroll
- Tema oscuro/claro con persistencia en localStorage
- Control de tamaño de fuente (A- / A+)

### Búsquedas
- Vecino más cercano (NN) con poda de ramas
- Búsqueda por rango con tabla paginada
- Simulación visual del recorrido del algoritmo
- Selección de servidor desde la tabla de registros

### Dashboard
- **Benchmark**: compara KD-Tree vs búsqueda secuencial
- **Variar Dimensiones**: gráficos de rendimiento 1D a 6D
- **Escalabilidad**: prueba con 100, 500, 1000, 2000, 5000 nodos
- Gráficos de barras y líneas nativos (Canvas API)

---

## Resultados Experimentales

### Escalabilidad (5000 registros, 6 dimensiones)

| Tamaño | Build (ms) | KD-Tree (ms) | Secuencial (ms) | Speedup | Altura |
|---|---|---|---|---|---|
| 100 | 1.27 | 0.094 | 0.17 | 1.84x | 7 |
| 500 | 7.93 | 0.075 | 0.80 | 10.61x | 10 |
| 1000 | 16.09 | 0.104 | 1.30 | 12.47x | 11 |
| 3000 | 58.50 | 0.122 | 4.06 | 33.26x | 12 |
| 5000 | 114.55 | 0.265 | 7.20 | 27.18x | 14 |

**Conclusión**: El KD-Tree escala logarítmicamente (O(log n)) mientras la búsqueda secuencial escala linealmente (O(n)). La ventaja crece con el tamaño del dataset.

---

## API Endpoints

| Endpoint | Método | Descripción |
|---|---|---|
| `php/buildTree.php` | POST | Construir KD-Tree desde BD |
| `php/buildTree.php?nodes=N` | POST | Construir con N nodos aleatorios |
| `php/nearestNeighbor.php` | POST | Buscar vecino más cercano |
| `php/rangeSearch.php` | POST | Buscar puntos por rango |
| `php/insertPoint.php` | POST | Insertar nuevo registro |
| `php/deletePoint.php` | POST | Eliminar registro |
| `php/statistics.php?action=status` | GET | Estado del árbol |
| `php/statistics.php?action=rebuild&nodes=N` | GET | Reconstruir con N nodos |
| `php/getRecords.php?page=N&per_page=M` | GET | Registros paginados |
| `php/experiments.php?action=scalability` | GET | Prueba de escalabilidad |
| `php/experiments.php?action=benchmark&queries=N` | GET | Benchmark completo |
| `php/experiments.php?action=dimensions` | GET | Variar dimensiones |

---

## Autor

Proyecto de Investigación Doctoral  
**Estructuras de Datos y Algoritmos Avanzados**
