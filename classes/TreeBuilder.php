<?php

require_once __DIR__ . '/KDTree.php';
require_once __DIR__ . '/Point.php';

/**
 * Constructor y administrador del KD-Tree.
 * Gestiona la creacion del arbol a partir de datos de la base de datos
 * y proporciona metodos para las operaciones del ciclo de vida del arbol.
 */
class TreeBuilder
{
    /** @var KDTree|null Instancia del arbol */
    private ?KDTree $tree;

    /** @var float Tiempo de la ultima construccion en ms */
    private float $lastBuildTime;

    /** @var int Cantidad de puntos en la ultima construccion */
    private int $lastBuildSize;

    /** @var array Metricas de rendimiento acumuladas */
    private array $metrics;

    /** @var int Dimensionalidad del arbol */
    private int $dimensions;

    /**
     * @param int $dimensions Dimensionalidad del espacio
     */
    public function __construct(int $dimensions = 6)
    {
        $this->tree = null;
        $this->lastBuildTime = 0;
        $this->lastBuildSize = 0;
        $this->metrics = [];
        $this->dimensions = $dimensions;
    }

    /**
     * Construye el arbol a partir de un array de puntos.
     *
     * @param array $pointsData Datos de puntos desde la base de datos
     * @return array Resultado de la construccion
     */
    public function buildFromData(array $pointsData): array
    {
        $points = [];
        foreach ($pointsData as $row) {
            $points[] = Point::fromDatabaseRow($row);
        }

        $this->tree = new KDTree($this->dimensions);
        $this->lastBuildTime = $this->tree->build($points);
        $this->lastBuildSize = count($points);

        $stats = $this->tree->getStatistics();
        $this->metrics = $stats;

        return [
            'success' => true,
            'size' => $this->lastBuildSize,
            'build_time' => $this->lastBuildTime,
            'statistics' => $stats,
            'tree' => $this->tree->toVisualizationArray(),
            'event_log' => $this->tree->getEventLog(),
        ];
    }

    /**
     * Busca el vecino mas cercano.
     *
     * @param array $targetData Coordenadas del punto objetivo
     * @return array
     */
    public function findNearestNeighbor(array $targetData): array
    {
        if ($this->tree === null) {
            return ['error' => 'El arbol no ha sido construido'];
        }

        $target = new Point([
            (float)$targetData['cpu_usage'],
            (float)$targetData['memory_usage'],
            (float)$targetData['hana_memory'],
            (float)$targetData['dialog_response_time'],
            (int)$targetData['work_processes'],
            (int)$targetData['enqueue_locks'],
        ]);

        $result = $this->tree->nearestNeighbor($target);

        return [
            'target' => $target->toArray(),
            'neighbor' => $result['point'] ? $result['point']->toArray() : null,
            'distance' => $result['distance'],
            'time' => $result['time'],
            'comparisons' => $result['comparisons'],
            'visited' => $result['visited'],
            'log' => $result['log'],
        ];
    }

    /**
     * Busqueda por rango.
     *
     * @param array $targetData Coordenadas del punto central
     * @param float $radius Radio de busqueda
     * @return array
     */
    public function searchRange(array $targetData, float $radius): array
    {
        if ($this->tree === null) {
            return ['error' => 'El arbol no ha sido construido'];
        }

        $target = new Point([
            (float)$targetData['cpu_usage'],
            (float)$targetData['memory_usage'],
            (float)$targetData['hana_memory'],
            (float)$targetData['dialog_response_time'],
            (int)$targetData['work_processes'],
            (int)$targetData['enqueue_locks'],
        ]);

        $result = $this->tree->rangeSearch($target, $radius);

        return [
            'target' => $target->toArray(),
            'points_found' => $result['points'],
            'total_found' => count($result['points']),
            'time' => $result['time'],
            'comparisons' => $result['comparisons'],
            'visited' => $result['visited'],
            'log' => $result['log'],
        ];
    }

    /**
     * Inserta un punto en el arbol.
     *
     * @param array $pointData Datos del punto
     * @return array
     */
    public function insertPoint(array $pointData): array
    {
        if ($this->tree === null) {
            return ['error' => 'El arbol no ha sido construido'];
        }

        $point = new Point(
            [
                (float)$pointData['cpu_usage'],
                (float)$pointData['memory_usage'],
                (float)$pointData['hana_memory'],
                (float)$pointData['dialog_response_time'],
                (int)$pointData['work_processes'],
                (int)$pointData['enqueue_locks'],
            ],
            (int)($pointData['id'] ?? 0),
            $pointData['server_name'] ?? ''
        );

        $time = $this->tree->insert($point);

        return [
            'success' => true,
            'insert_time' => $time,
            'tree_size' => $this->tree->getSize(),
            'statistics' => $this->tree->getStatistics(),
        ];
    }

    /**
     * Elimina un punto del arbol por ID.
     *
     * @param int $id
     * @return array
     */
    public function deletePoint(int $id): array
    {
        if ($this->tree === null) {
            return ['error' => 'El arbol no ha sido construido'];
        }

        $success = $this->tree->delete($id);

        return [
            'success' => $success,
            'tree_size' => $this->tree->getSize(),
            'statistics' => $this->tree->getStatistics(),
        ];
    }

    /**
     * Reconstruye el arbol para balancearlo.
     *
     * @return array
     */
    public function rebuild(): array
    {
        if ($this->tree === null) {
            return ['error' => 'El arbol no ha sido construido'];
        }

        $rebuildTime = $this->tree->rebalance();

        return [
            'success' => true,
            'rebuild_time' => $rebuildTime,
            'size' => $this->tree->getSize(),
            'statistics' => $this->tree->getStatistics(),
            'tree' => $this->tree->toVisualizationArray(),
            'event_log' => $this->tree->getEventLog(),
        ];
    }

    /**
     * Obtiene las estadisticas actuales del arbol.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        if ($this->tree === null) {
            return [
                'total_nodes' => 0,
                'height' => 0,
                'message' => 'El arbol no ha sido construido',
            ];
        }

        return $this->tree->getStatistics();
    }

    /**
     * Obtiene la estructura del arbol para visualizacion.
     *
     * @return array|null
     */
    public function getTreeVisualization(): ?array
    {
        if ($this->tree === null) {
            return null;
        }
        return $this->tree->toVisualizationArray();
    }

    /**
     * Verifica si el arbol existe y esta construido.
     *
     * @return bool
     */
    public function isTreeBuilt(): bool
    {
        return $this->tree !== null && $this->tree->getSize() > 0;
    }

    /**
     * Obtiene la instancia interna del KD-Tree.
     *
     * @return KDTree|null
     */
    public function getTree(): ?KDTree
    {
        return $this->tree;
    }
}
