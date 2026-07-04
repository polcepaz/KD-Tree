<?php

require_once __DIR__ . '/KDTree.php';
require_once __DIR__ . '/Point.php';

/**
 * Recolecta y analiza metricas de rendimiento del KD-Tree.
 * Permite comparar el rendimiento del arbol contra busqueda secuencial.
 */
class Metrics
{
    /** @var array Resultados de las pruebas */
    private array $results;

    /**
     * Inicializa el recolector de metricas.
     */
    public function __construct()
    {
        $this->results = [];
    }

    /**
     * Ejecuta pruebas comparativas entre KD-Tree y busqueda secuencial.
     *
     * @param array $pointsData Datos de puntos
     * @param int $numQueries Numero de consultas a realizar
     * @return array Resultados comparativos
     */
    public function runBenchmark(array $pointsData, int $numQueries = 50): array
    {
        $points = [];
        foreach ($pointsData as $row) {
            $points[] = Point::fromDatabaseRow($row);
        }

        if (empty($points)) {
            return ['error' => 'No hay datos para realizar las pruebas'];
        }

        $numPoints = count($points);
        $dimensions = 6;

        // Generar consultas aleatorias
        $queries = [];
        for ($i = 0; $i < $numQueries; $i++) {
            $queries[] = $points[array_rand($points)];
        }

        // ---- Prueba 1: Busqueda secuencial ----
        $seqStart = microtime(true);
        $seqComparisons = 0;
        $seqResults = [];

        foreach ($queries as $query) {
            $bestDist = INF;
            $bestPoint = null;
            $localComps = 0;

            foreach ($points as $p) {
                $dist = Distance::squaredEuclidean($query, $p);
                $localComps++;
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $bestPoint = $p;
                }
            }

            $seqComparisons += $localComps;
            $seqResults[] = [
                'query' => $query->getId(),
                'neighbor' => $bestPoint ? $bestPoint->getId() : null,
                'distance' => sqrt($bestDist),
            ];
        }

        $seqTime = (microtime(true) - $seqStart) * 1000;

        // ---- Prueba 2: KD-Tree ----
        $tree = new KDTree($dimensions);
        $buildStart = microtime(true);
        $tree->build($points);
        $buildTime = (microtime(true) - $buildStart) * 1000;

        $kdStart = microtime(true);
        $kdComparisons = 0;
        $kdResults = [];

        foreach ($queries as $query) {
            $result = $tree->nearestNeighbor($query);
            $kdComparisons += $result['comparisons'];
            $kdResults[] = [
                'query' => $query->getId(),
                'neighbor' => $result['point'] ? $result['point']->getId() : null,
                'distance' => $result['distance'],
            ];
        }

        $kdTime = (microtime(true) - $kdStart) * 1000;

        // ---- Resultados ----
        $speedup = $kdTime > 0 ? $seqTime / $kdTime : 0;

        $benchmark = [
            'num_points' => $numPoints,
            'num_queries' => $numQueries,
            'sequential' => [
                'total_time' => round($seqTime, 4),
                'avg_time' => $numQueries > 0 ? round($seqTime / $numQueries, 4) : 0,
                'total_comparisons' => $seqComparisons,
                'avg_comparisons' => $numQueries > 0 ? round($seqComparisons / $numQueries, 2) : 0,
            ],
            'kdtree' => [
                'build_time' => round($buildTime, 4),
                'total_time' => round($kdTime, 4),
                'avg_time' => $numQueries > 0 ? round($kdTime / $numQueries, 4) : 0,
                'total_comparisons' => $kdComparisons,
                'avg_comparisons' => $numQueries > 0 ? round($kdComparisons / $numQueries, 2) : 0,
            ],
            'speedup' => round($speedup, 2),
            'improvement_percent' => $speedup > 0 ? round(($speedup - 1) * 100, 2) : 0,
        ];

        $this->results[] = $benchmark;
        return $benchmark;
    }

    /**
     * Ejecuta pruebas de escalabilidad con diferentes tamanos de datos.
     *
     * @param array $pointsData Datos completos
     * @return array Resultados de escalabilidad
     */
    public function runScalabilityTest(array $pointsData): array
    {
        $sizes = [100, 500, 1000, 3000, 5000];
        $results = [];

        $allPoints = [];
        foreach ($pointsData as $row) {
            $allPoints[] = Point::fromDatabaseRow($row);
        }

        shuffle($allPoints);

        foreach ($sizes as $size) {
            if ($size > count($allPoints)) {
                continue;
            }

            $subset = array_slice($allPoints, 0, $size);

            // Construccion
            $tree = new KDTree(6);
            $buildStart = microtime(true);
            $tree->build($subset);
            $buildTime = (microtime(true) - $buildStart) * 1000;

            // Busqueda (10 consultas)
            $numQueries = min(10, $size);
            $queryPoints = array_rand($subset, $numQueries);
            if (!is_array($queryPoints)) {
                $queryPoints = [$queryPoints];
            }

            $searchStart = microtime(true);
            $searchComps = 0;
            foreach ($queryPoints as $idx) {
                $result = $tree->nearestNeighbor($subset[$idx]);
                $searchComps += $result['comparisons'];
            }
            $searchTime = (microtime(true) - $searchStart) * 1000;

            // Secuencial
            $seqStart = microtime(true);
            $seqComps = 0;
            foreach ($queryPoints as $idx) {
                $target = $subset[$idx];
                $bestDist = INF;
                foreach ($subset as $p) {
                    $d = Distance::squaredEuclidean($target, $p);
                    $seqComps++;
                    if ($d < $bestDist && $p->getId() !== $target->getId()) {
                        $bestDist = $d;
                    }
                }
            }
            $seqTime = (microtime(true) - $seqStart) * 1000;

            $stats = $tree->getStatistics();

            $results[] = [
                'size' => $size,
                'kdtree' => [
                    'build_time' => round($buildTime, 4),
                    'search_time' => round($searchTime, 4),
                    'avg_search_time' => round($searchTime / $numQueries, 4),
                    'comparisons' => $searchComps,
                    'height' => $stats['height'],
                ],
                'sequential' => [
                    'search_time' => round($seqTime, 4),
                    'avg_search_time' => round($seqTime / $numQueries, 4),
                    'comparisons' => $seqComps,
                ],
                'speedup' => $searchTime > 0 ? round($seqTime / $searchTime, 2) : 0,
                'queries' => $numQueries,
            ];
        }

        return $results;
    }

    /**
     * Obtiene todos los resultados acumulados.
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
