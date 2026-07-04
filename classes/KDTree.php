<?php

require_once __DIR__ . '/KDNode.php';
require_once __DIR__ . '/Point.php';
require_once __DIR__ . '/Distance.php';

/**
 * Implementacion completa de un KD-Tree (K-Dimensional Tree).
 * Estructura de datos para particion del espacio multidimensional
 * que permite busquedas eficientes de vecinos cercanos y busquedas por rango.
 */
class KDTree
{
    /** @var KDNode|null Raiz del arbol */
    private ?KDNode $root;

    /** @var int Dimensionalidad de los puntos */
    private int $dimensions;

    /** @var int Numero total de nodos en el arbol */
    private int $size;

    /** @var int Contador de comparaciones realizadas durante busquedas */
    private int $comparisons;

    /** @var int Contador de nodos visitados durante busquedas */
    private int $visitedNodes;

    /** @var array Registro de pasos para animacion del algoritmo */
    private array $traversalLog;

    /** @var int Altura actual del arbol */
    private int $height;

    /** @var array Registro completo de eventos del arbol */
    private array $eventLog;

    /**
     * @param int $dimensions Numero de dimensiones del espacio
     */
    public function __construct(int $dimensions = 6)
    {
        $this->root = null;
        $this->dimensions = $dimensions;
        $this->size = 0;
        $this->comparisons = 0;
        $this->visitedNodes = 0;
        $this->traversalLog = [];
        $this->height = 0;
        $this->eventLog = [];
    }

    /**
     * Construye el arbol a partir de un conjunto de puntos.
     *
     * @param Point[] $points Array de puntos
     * @return float Tiempo de construccion en milisegundos
     */
    public function build(array $points): float
    {
        $start = microtime(true);
        $this->traversalLog = [];
        $this->eventLog = [];
        $this->size = count($points);

        $this->logEvent('BUILD', "Iniciando construccion del KD-Tree con {$this->size} puntos en {$this->dimensions} dimensiones");

        if ($this->size === 0) {
            $this->root = null;
            $this->height = 0;
            $this->logEvent('BUILD', 'No hay puntos para construir el arbol');
            return 0.0;
        }

        $this->root = $this->buildRecursive($points, 0);
        $this->updateHeight();

        $elapsed = (microtime(true) - $start) * 1000;
        $this->logEvent('BUILD', "Construccion completada: {$this->size} nodos, altura {$this->height}, tiempo {$elapsed}ms", [
            'nodes' => $this->size,
            'height' => $this->height,
            'time_ms' => $elapsed,
        ]);

        return $elapsed;
    }

    /**
     * Construye recursivamente el arbol ordenando puntos por la dimension de corte.
     *
     * @param Point[] $points Puntos a insertar en el subarbol
     * @param int $depth Profundidad actual
     * @return KDNode|null Nodo raiz del subarbol
     */
    private function buildRecursive(array $points, int $depth): ?KDNode
    {
        if (empty($points)) {
            return null;
        }

        $dim = $depth % $this->dimensions;
        $count = count($points);

        // Ordenar puntos por la dimension actual
        usort($points, function (Point $a, Point $b) use ($dim) {
            $va = $a->getCoordinate($dim);
            $vb = $b->getCoordinate($dim);
            return $va <=> $vb;
        });

        $medianIndex = intdiv($count, 2);
        $medianPoint = $points[$medianIndex];

        // Evitar duplicados en la mediana moviendo el indice si es necesario
        while ($medianIndex > 0
            && $points[$medianIndex - 1]->getCoordinate($dim) == $medianPoint->getCoordinate($dim)
        ) {
            $medianIndex--;
            $medianPoint = $points[$medianIndex];
        }

        $node = new KDNode($medianPoint, $dim, $depth);

        $leftPoints = array_slice($points, 0, $medianIndex);
        $rightPoints = array_slice($points, $medianIndex + 1);

        $this->logEvent('SPLIT', "Nivel {$depth}: dividiendo {$count} puntos por dimension d{$dim} (umbral={$node->splitValue}) -> izquierda: " . count($leftPoints) . ", derecha: " . count($rightPoints), [
            'level' => $depth,
            'dimension' => $dim,
            'split_value' => $node->splitValue,
            'node_id' => $node->point->getId(),
            'node_name' => $node->point->getServerName(),
            'left_count' => count($leftPoints),
            'right_count' => count($rightPoints),
        ]);

        $this->logEvent('CREATE_NODE', "Creando nodo '{$node->point->getServerName()}' (ID={$node->point->getId()}) en nivel {$depth}, corte por d{$dim}={$node->splitValue}");

        $node->left = $this->buildRecursive($leftPoints, $depth + 1);
        $node->right = $this->buildRecursive($rightPoints, $depth + 1);

        return $node;
    }

    /**
     * Inserta un nuevo punto en el arbol.
     *
     * @param Point $point Punto a insertar
     * @return float Tiempo de insercion en milisegundos
     */
    public function insert(Point $point): float
    {
        $start = microtime(true);
        $this->traversalLog = [];

        $this->logEvent('INSERT', "Insertando punto '{$point->getServerName()}' (ID={$point->getId()}) en el arbol");

        if ($this->root === null) {
            $this->root = new KDNode($point, 0, 0);
            $this->size = 1;
            $this->height = 1;
            $elapsed = (microtime(true) - $start) * 1000;
            $this->logEvent('INSERT', "Nodo insertado como raiz, tiempo {$elapsed}ms");
            return $elapsed;
        }

        $this->root = $this->insertRecursive($this->root, $point, 0);
        $this->size++;
        $this->updateHeight();

        $this->logEvent('INSERT', "Insercion completada. Tamano actual: {$this->size}, altura: {$this->height}");

        // Rebalancear si el arbol esta desbalanceado
        if ($this->needsRebalancing()) {
            $this->logEvent('REBALANCE', "Arbol desbalanceado (altura={$this->height}), iniciando rebalanceo");
            $this->rebalance();
        }

        return (microtime(true) - $start) * 1000;
    }

    /**
     * Inserta recursivamente un punto en el subarbol.
     *
     * @param KDNode $node Nodo actual
     * @param Point $point Punto a insertar
     * @param int $depth Profundidad actual
     * @return KDNode
     */
    private function insertRecursive(KDNode $node, Point $point, int $depth): KDNode
    {
        $dim = $depth % $this->dimensions;
        $coord = $point->getCoordinate($dim);
        $nodeCoord = $node->point->getCoordinate($dim);

        $this->logTraversal('compare', "Comparando dimension {$dim}: {$coord} vs {$nodeCoord}");
        $this->logEvent('COMPARE', "Insertando '{$point->getServerName()}': d{$dim}={$coord} vs nodo '{$node->point->getServerName()}' d{$dim}={$nodeCoord}");

        if ($coord < $nodeCoord) {
            if ($node->left === null) {
                $node->left = new KDNode($point, $dim, $depth + 1);
                $this->logTraversal('insert_left', "Insertando en subarbol izquierdo");
                $this->logEvent('INSERT_LEFT', "Nodo '{$point->getServerName()}' insertado como hijo izquierdo de '{$node->point->getServerName()}' (nivel " . ($depth + 1) . ")");
            } else {
                $this->logEvent('TRAVERSE', "Yendo a subarbol izquierdo de '{$node->point->getServerName()}' (d{$dim}: {$coord} < {$nodeCoord})");
                $node->left = $this->insertRecursive($node->left, $point, $depth + 1);
            }
        } else {
            if ($node->right === null) {
                $node->right = new KDNode($point, $dim, $depth + 1);
                $this->logTraversal('insert_right', "Insertando en subarbol derecho");
                $this->logEvent('INSERT_RIGHT', "Nodo '{$point->getServerName()}' insertado como hijo derecho de '{$node->point->getServerName()}' (nivel " . ($depth + 1) . ")");
            } else {
                $this->logEvent('TRAVERSE', "Yendo a subarbol derecho de '{$node->point->getServerName()}' (d{$dim}: {$coord} >= {$nodeCoord})");
                $node->right = $this->insertRecursive($node->right, $point, $depth + 1);
            }
        }

        return $node;
    }

    /**
     * Busca el vecino mas cercano a un punto dado.
     *
     * @param Point $target Punto objetivo
     * @return array{punto: ?Point, distancia: float, tiempo: float, comparaciones: int, visitados: int, log: array}
     */
    public function nearestNeighbor(Point $target): array
    {
        $start = microtime(true);
        $this->comparisons = 0;
        $this->visitedNodes = 0;
        $this->traversalLog = [];

        $coordsStr = implode(', ', array_map(fn($v) => round($v, 1), $target->getCoordinates()));
        $this->logEvent('NN_SEARCH', "Iniciando busqueda del vecino mas cercano para punto [{$coordsStr}]");

        if ($this->root === null) {
            $this->logEvent('NN_SEARCH', 'Arbol vacio, no hay resultados');
            return [
                'point' => null,
                'distance' => -1,
                'time' => 0,
                'comparisons' => 0,
                'visited' => 0,
                'log' => [],
                'event_log' => $this->eventLog,
            ];
        }

        $best = null;
        $bestDist = INF;
        $bestDepth = 0;

        $this->nearestNeighborRecursive($this->root, $target, 0, $best, $bestDist, $bestDepth);

        $elapsed = (microtime(true) - $start) * 1000;

        $bestName = $best ? $best->getServerName() : 'N/A';
        $bestDistVal = $bestDist !== INF ? sqrt($bestDist) : -1;
        $this->logEvent('NN_RESULT', "Vecino mas cercano: '{$bestName}' a distancia {$bestDistVal} (tiempo: {$elapsed}ms, comparaciones: {$this->comparisons}, nodos visitados: {$this->visitedNodes})");

        return [
            'point' => $best,
            'distance' => $bestDistVal,
            'time' => $elapsed,
            'comparisons' => $this->comparisons,
            'visited' => $this->visitedNodes,
            'depth' => $bestDepth,
            'log' => $this->traversalLog,
            'event_log' => $this->eventLog,
        ];
    }

    /**
     * Busqueda recursiva del vecino mas cercano.
     *
     * @param KDNode $node Nodo actual
     * @param Point $target Punto objetivo
     * @param int $depth Profundidad actual
     * @param Point|null &$best Mejor punto encontrado
     * @param float &$bestDist Mejor distancia encontrada
     */
    private function nearestNeighborRecursive(
        ?KDNode $node,
        Point $target,
        int $depth,
        ?Point &$best,
        float &$bestDist,
        int &$bestDepth = 0
    ): void {
        if ($node === null) {
            return;
        }

        $this->visitedNodes++;
        $dim = $depth % $this->dimensions;
        $coord = $target->getCoordinate($dim);
        $nodeCoord = $node->point->getCoordinate($dim);

        $this->logTraversal('visit', "Visitando nodo '{$node->point->getServerName()}' en nivel {$depth}");
        $this->logEvent('VISIT', "Visitando nodo '{$node->point->getServerName()}' (ID={$node->point->getId()}), nivel {$depth}, corte d{$dim}={$nodeCoord}");

        // Calcular distancia al punto actual
        $dist = Distance::squaredEuclidean($target, $node->point);
        $this->comparisons++;
        $actualDist = sqrt($dist);
        $this->logEvent('COMPARE', "Calculando distancia euclidiana al nodo '{$node->point->getServerName()}': {$actualDist}");

        if ($dist < $bestDist) {
            $bestDist = $dist;
            $best = $node->point;
            $bestDepth = $depth;
            $this->logTraversal('update_best', "Nuevo mejor: '{$node->point->getServerName()}' con distancia " . sqrt($dist));
            $this->logEvent('UPDATE_BEST', "Nuevo mejor candidato: '{$node->point->getServerName()}' a distancia {$actualDist} (nivel {$depth})");
        }

        // Determinar que subarbol explorar primero
        $diff = $coord - $nodeCoord;
        $first = $diff < 0 ? $node->left : $node->right;
        $second = $diff < 0 ? $node->right : $node->left;

        $side = $diff < 0 ? 'izquierdo' : 'derecho';
        $this->logEvent('TRAVERSE', "Explorando subarbol {$side} de '{$node->point->getServerName()}' (d{$dim}: {$coord} vs {$nodeCoord})");

        // Explorar el subarbol mas prometedor primero
        $this->nearestNeighborRecursive($first, $target, $depth + 1, $best, $bestDist, $bestDepth);

        // Verificar si es necesario explorar el otro subarbol
        if ($second !== null) {
            $planeDist = $diff * $diff;
            $this->comparisons++;

            if ($planeDist < $bestDist) {
                $this->logTraversal('explore_other', "Explorando subarbol alternativo (distancia al plano: " . sqrt($planeDist) . ")");
                $this->logEvent('EXPLORE_OTHER', "Distancia al plano de corte ({$node->point->getServerName()}) = " . sqrt($planeDist) . " < mejor distancia " . sqrt($bestDist) . " -> explorando otro subarbol");
                $this->nearestNeighborRecursive($second, $target, $depth + 1, $best, $bestDist, $bestDepth);
            } else {
                $this->logTraversal('prune', "Descartando subarbol (distancia al plano: " . sqrt($planeDist) . " > mejor: " . sqrt($bestDist) . ")");
                $this->logEvent('PRUNE', "Poda: distancia al plano de corte " . sqrt($planeDist) . " >= mejor distancia " . sqrt($bestDist) . " -> subarbol descartado");
            }
        }
    }

    /**
     * Busca todos los puntos dentro de un radio de distancia de un punto objetivo.
     *
     * @param Point $target Punto central
     * @param float $radius Radio de busqueda
     * @return array{puntos: array, tiempo: float, comparaciones: int, visitados: int, log: array}
     */
    public function rangeSearch(Point $target, float $radius): array
    {
        $start = microtime(true);
        $this->comparisons = 0;
        $this->visitedNodes = 0;
        $this->traversalLog = [];

        $coordsStr = implode(', ', array_map(fn($v) => round($v, 1), $target->getCoordinates()));
        $this->logEvent('RANGE_SEARCH', "Iniciando busqueda por rango: centro [{$coordsStr}], radio {$radius}");

        $result = [];

        if ($this->root !== null) {
            $this->rangeSearchRecursive($this->root, $target, $radius, 0, $result);
        }

        $elapsed = (microtime(true) - $start) * 1000;

        // Ordenar por distancia
        usort($result, function (array $a, array $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $this->logEvent('RANGE_RESULT', "Busqueda por rango completada: " . count($result) . " puntos encontrados, tiempo {$elapsed}ms, comparaciones: {$this->comparisons}, nodos visitados: {$this->visitedNodes}");

        return [
            'points' => $result,
            'time' => $elapsed,
            'comparisons' => $this->comparisons,
            'visited' => $this->visitedNodes,
            'log' => $this->traversalLog,
            'event_log' => $this->eventLog,
        ];
    }

    /**
     * Busqueda recursiva por rango.
     *
     * @param KDNode $node Nodo actual
     * @param Point $target Punto central
     * @param float $radius Radio de busqueda
     * @param int $depth Profundidad actual
     * @param array &$result Array de resultados
     */
    private function rangeSearchRecursive(
        ?KDNode $node,
        Point $target,
        float $radius,
        int $depth,
        array &$result
    ): void {
        if ($node === null) {
            return;
        }

        $this->visitedNodes++;
        $dim = $depth % $this->dimensions;
        $radiusSquared = $radius * $radius;

        $this->logTraversal('visit', "Visitando nodo '{$node->point->getServerName()}'");
        $this->logEvent('VISIT', "Range: visitando nodo '{$node->point->getServerName()}', nivel {$depth}");

        $dist = Distance::squaredEuclidean($target, $node->point);
        $this->comparisons++;
        $actualDist = sqrt($dist);

        if ($dist <= $radiusSquared) {
            $result[] = [
                'point' => $node->point->toArray(),
                'distance' => $actualDist,
            ];
            $this->logTraversal('found', "Punto '{$node->point->getServerName()}' dentro del rango (distancia: " . $actualDist . ")");
            $this->logEvent('FOUND', "Punto '{$node->point->getServerName()}' DENTRO del rango (distancia={$actualDist} <= radio={$radius})");
        } else {
            $this->logEvent('COMPARE', "Punto '{$node->point->getServerName()}' FUERA del rango (distancia={$actualDist} > radio={$radius})");
        }

        $coord = $target->getCoordinate($dim);
        $nodeCoord = $node->point->getCoordinate($dim);
        $diff = $coord - $nodeCoord;
        $planeDist = $diff * $diff;

        $first = $diff < 0 ? $node->left : $node->right;
        $second = $diff < 0 ? $node->right : $node->left;

        $this->rangeSearchRecursive($first, $target, $radius, $depth + 1, $result);

        if ($second !== null && $planeDist <= $radiusSquared) {
            $this->logEvent('EXPLORE_OTHER', "Range: explorando subarbol alternativo (distancia al plano=" . sqrt($planeDist) . " <= radio={$radius})");
            $this->rangeSearchRecursive($second, $target, $radius, $depth + 1, $result);
        } elseif ($second !== null) {
            $this->logEvent('PRUNE', "Range: podando subarbol alternativo (distancia al plano=" . sqrt($planeDist) . " > radio={$radius})");
        }
    }

    /**
     * Elimina un punto del arbol por su ID.
     *
     * @param int $id Identificador del punto
     * @return bool True si se elimino correctamente
     */
    public function delete(int $id): bool
    {
        $this->traversalLog = [];

        $this->logEvent('DELETE', "Iniciando eliminacion del nodo ID={$id}");

        if ($this->root === null) {
            $this->logEvent('DELETE', "Arbol vacio, no se puede eliminar");
            return false;
        }

        $points = $this->inOrderTraversal();
        $filtered = [];

        $found = false;
        foreach ($points as $p) {
            if ($p->getId() === $id) {
                $found = true;
                $this->logEvent('DELETE', "Nodo encontrado: '{$p->getServerName()}' (ID={$id}), sera eliminado");
                continue;
            }
            $filtered[] = $p;
        }

        if (!$found) {
            $this->logEvent('DELETE', "Nodo ID={$id} no encontrado en el arbol");
            return false;
        }

        $this->clear();
        $this->logEvent('DELETE', "Reconstruyendo arbol sin el nodo eliminado (" . count($filtered) . " nodos restantes)");
        $this->build($filtered);

        $this->logEvent('DELETE', "Eliminacion completada. Nuevo tamano: {$this->size}");

        return true;
    }

    /**
     * Realiza un recorrido inorder del arbol.
     *
     * @return Point[]
     */
    public function inOrderTraversal(): array
    {
        $result = [];
        $this->inOrderRecursive($this->root, $result);
        return $result;
    }

    /**
     * Recorrido inorder recursivo.
     *
     * @param KDNode|null $node
     * @param array &$result
     */
    private function inOrderRecursive(?KDNode $node, array &$result): void
    {
        if ($node === null) {
            return;
        }

        $this->inOrderRecursive($node->left, $result);
        $result[] = $node->point;
        $this->inOrderRecursive($node->right, $result);
    }

    /**
     * Obtiene todas las estadisticas del arbol.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $totalNodes = $this->size;
        $leafCount = $this->countLeaves($this->root);
        $internalNodes = $totalNodes - $leafCount;

        $depthStats = $this->calculateDepthStats($this->root, 0);

        return [
            'total_nodes' => $totalNodes,
            'leaf_nodes' => $leafCount,
            'internal_nodes' => $internalNodes,
            'height' => $this->height,
            'max_depth' => $depthStats['max'],
            'min_depth' => $depthStats['min'],
            'avg_depth' => $totalNodes > 0 ? $depthStats['sum'] / $totalNodes : 0,
            'dimensions' => $this->dimensions,
            'complexity_time' => 'O(log n) en promedio, O(n) en peor caso',
            'complexity_space' => 'O(n)',
            'memory_estimate' => $totalNodes * (56 + $this->dimensions * 8) . ' bytes aprox.',
        ];
    }

    /**
     * Verifica si el arbol necesita rebalanceo.
     *
     * @return bool
     */
    public function needsRebalancing(): bool
    {
        $maxDepth = $this->calculateDepthStats($this->root, 0)['max'];
        $optimalDepth = (int)ceil(log($this->size + 1, 2));
        return $maxDepth > $optimalDepth * 1.5 && $this->size > 10;
    }

    /**
     * Reconstruye el arbol de forma balanceada.
     *
     * @return float Tiempo de reconstruccion en milisegundos
     */
    public function rebalance(): float
    {
        $this->logEvent('REBALANCE', "Iniciando rebalanceo del arbol. Tamano actual: {$this->size}, altura: {$this->height}");
        $points = $this->inOrderTraversal();
        $this->clear();
        $time = $this->build($points);
        $this->logEvent('REBALANCE', "Rebalanceo completado en {$time}ms. Nueva altura: {$this->height}");
        return $time;
    }

    /**
     * Limpia el arbol completamente.
     */
    public function clear(): void
    {
        $this->root = null;
        $this->size = 0;
        $this->height = 0;
    }

    /**
     * Convierte el arbol a un array para serializacion.
     *
     * @return array|null
     */
    public function toArray(): ?array
    {
        return $this->root ? $this->root->toArray() : null;
    }

    // ========== Metodos auxiliares ==========

    /**
     * Actualiza la altura del arbol.
     */
    private function updateHeight(): void
    {
        $this->height = $this->calculateHeight($this->root);
    }

    /**
     * Calcula la altura de un subarbol.
     *
     * @param KDNode|null $node
     * @return int
     */
    private function calculateHeight(?KDNode $node): int
    {
        if ($node === null) {
            return 0;
        }
        return 1 + max(
            $this->calculateHeight($node->left),
            $this->calculateHeight($node->right)
        );
    }

    /**
     * Cuenta las hojas del arbol.
     *
     * @param KDNode|null $node
     * @return int
     */
    private function countLeaves(?KDNode $node): int
    {
        if ($node === null) {
            return 0;
        }
        if ($node->isLeaf()) {
            return 1;
        }
        return $this->countLeaves($node->left) + $this->countLeaves($node->right);
    }

    /**
     * Calcula estadisticas de profundidad.
     *
     * @param KDNode|null $node
     * @param int $depth
     * @return array{min: int, max: int, sum: int}
     */
    private function calculateDepthStats(?KDNode $node, int $depth): array
    {
        if ($node === null) {
            return ['min' => PHP_INT_MAX, 'max' => 0, 'sum' => 0];
        }

        $left = $this->calculateDepthStats($node->left, $depth + 1);
        $right = $this->calculateDepthStats($node->right, $depth + 1);

        $isLeaf = $node->isLeaf();

        return [
            'min' => $isLeaf ? min($depth, $left['min'], $right['min']) : min($left['min'], $right['min']),
            'max' => max($depth, $left['max'], $right['max']),
            'sum' => ($isLeaf ? $depth : 0) + $left['sum'] + $right['sum'],
        ];
    }

    /**
     * Registra un paso en el log de recorrido para animacion.
     *
     * @param string $action Tipo de accion
     * @param string $description Descripcion del paso
     */
    private function logTraversal(string $action, string $description): void
    {
        $this->traversalLog[] = [
            'action' => $action,
            'description' => $description,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Obtiene el log de recorrido.
     *
     * @return array
     */
    public function getTraversalLog(): array
    {
        return $this->traversalLog;
    }

    /**
     * Obtiene la raiz del arbol.
     *
     * @return KDNode|null
     */
    public function getRoot(): ?KDNode
    {
        return $this->root;
    }

    /**
     * Obtiene el tamano del arbol.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Registra un evento en el log de la estructura KD-Tree.
     *
     * @param string $type Categoria del evento (BUILD, SPLIT, INSERT, COMPARE, PRUNE, FOUND, REBALANCE, DELETE, VISIT, SEARCH)
     * @param string $description Descripcion detallada
     * @param array $metadata Datos adicionales opcionales
     */
    private function logEvent(string $type, string $description, array $metadata = []): void
    {
        $this->eventLog[] = [
            'type' => $type,
            'description' => $description,
            'timestamp' => microtime(true),
            'metadata' => $metadata,
        ];
    }

    /**
     * Obtiene el log completo de eventos.
     *
     * @return array
     */
    public function getEventLog(): array
    {
        return $this->eventLog;
    }

    /**
     * Limpia el log de eventos.
     */
    public function clearEventLog(): void
    {
        $this->eventLog = [];
    }

    /**
     * Convierte el arbol a un formato ligero para visualizacion en canvas.
     *
     * @return array|null
     */
    public function toVisualizationArray(): ?array
    {
        if ($this->root === null) {
            return null;
        }
        return $this->nodeToVisArray($this->root);
    }

    /**
     * Convierte un nodo a formato de visualizacion.
     *
     * @param KDNode $node
     * @return array
     */
    private function nodeToVisArray(KDNode $node): array
    {
        $arr = [
            'name' => $node->point->getServerName(),
            'id' => $node->point->getId(),
            'dim' => $node->dimension,
            'level' => $node->level,
            'split' => $node->splitValue,
            'coords' => $node->point->getCoordinates(),
            'children' => [],
        ];

        if ($node->left !== null) {
            $arr['children'][] = $this->nodeToVisArray($node->left);
        }
        if ($node->right !== null) {
            $arr['children'][] = $this->nodeToVisArray($node->right);
        }

        return $arr;
    }
}
