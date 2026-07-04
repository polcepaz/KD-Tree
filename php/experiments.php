<?php
/**
 * Endpoint: Experimentos de KD-Tree
 * Pruebas de escalabilidad, benchmark, y variacion de parametros.
 *
 * Metodo: GET
 * Parametros: action=scalability|benchmark|dimensions
 * Retorna: JSON con resultados experimentales.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database/ServerMetrics.php';
require_once __DIR__ . '/../classes/KDTree.php';
require_once __DIR__ . '/../classes/KDNode.php';
require_once __DIR__ . '/../classes/Point.php';
require_once __DIR__ . '/../classes/Distance.php';
require_once __DIR__ . '/../classes/Metrics.php';
require_once __DIR__ . '/../classes/TreeBuilder.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? 'scalability';

try {
    $metrics = new ServerMetrics();
    $allData = $metrics->getAllAsArray();

    if (empty($allData)) {
        echo json_encode(['success' => false, 'error' => 'No hay datos']);
        exit;
    }

    // Convertir a Point objects
    $allPoints = [];
    foreach ($allData as $row) {
        $allPoints[] = Point::fromDatabaseRow($row);
    }

    switch ($action) {
        case 'scalability':
            // Escalabilidad: 100, 500, 1000, 3000, 5000
            $sizes = [100, 500, 1000, 3000, 5000];
            $results = [];
            shuffle($allPoints);

            foreach ($sizes as $size) {
                if ($size > count($allPoints)) break;
                $subset = array_slice($allPoints, 0, $size);
                $numQueries = min(10, (int)($size * 0.02));
                $numQueries = max(3, $numQueries); // minimo 3

                // Generar queries
                $queries = [];
                for ($j = 0; $j < $numQueries; $j++) {
                    $queries[] = $subset[array_rand($subset)];
                }

                // Memoria base
                $memBase = memory_get_usage(false);

                // KD-Tree
                $tree = new KDTree(6);
                $bStart = microtime(true);
                $tree->build($subset);
                $bTime = (microtime(true) - $bStart) * 1000;
                $memTree = memory_get_usage(false);
                $memDelta = max(0, $memTree - $memBase);

                // KD-Tree searches
                $kStart = microtime(true);
                $kdComps = 0;
                $kdVisits = 0;
                $kdDepths = [];
                $kdTimes = [];
                $kdCompList = [];
                foreach ($queries as $query) {
                    $qStart = microtime(true);
                    $result = $tree->nearestNeighbor($query);
                    $kdTimes[] = (microtime(true) - $qStart) * 1000;
                    $kdComps += $result['comparisons'];
                    $kdVisits += $result['visited'];
                    $kdDepths[] = $result['depth'] ?? 0;
                    $kdCompList[] = $result['comparisons'];
                }
                $kTime = (microtime(true) - $kStart) * 1000;
                $kdThroughput = $kTime > 0 ? round(($numQueries / $kTime) * 1000, 1) : 0; // queries/sec
                $kdAvgDepth = $numQueries > 0 ? round(array_sum($kdDepths) / $numQueries, 1) : 0;

                // Secuencial
                $sStart = microtime(true);
                $seqComps = 0;
                $seqTimes = [];
                $seqCompList = [];
                foreach ($queries as $query) {
                    $qStart = microtime(true);
                    $bestDist = INF;
                    $compCount = 0;
                    foreach ($subset as $p) {
                        $compCount++;
                        $d = Distance::squaredEuclidean($query, $p);
                        if ($d < $bestDist && $p->getId() !== $query->getId()) {
                            $bestDist = $d;
                        }
                    }
                    $seqTimes[] = (microtime(true) - $qStart) * 1000;
                    $seqComps += $compCount;
                    $seqCompList[] = $compCount;
                }
                $sTime = (microtime(true) - $sStart) * 1000;
                $seqThroughput = $sTime > 0 ? round(($numQueries / $sTime) * 1000, 1) : 0;

                // Precision
                $precision = 0;
                foreach ($queries as $query) {
                    $qId = $query->getId();
                    $kdResult = $tree->nearestNeighbor($query);
                    // sequential exact (exclude same point)
                    $bestDist = INF; $bestID = null;
                    foreach ($subset as $p) {
                        if ($p->getId() === $qId) continue;
                        $d = Distance::squaredEuclidean($query, $p);
                        if ($d < $bestDist) { $bestDist = $d; $bestID = $p->getId(); }
                    }
                    $kdID = $kdResult['point'] ? $kdResult['point']->getId() : null;
                    if ($kdID === $qId || $kdID === $bestID) $precision++;
                }
                $precisionPct = $numQueries > 0 ? round(($precision / $numQueries) * 100, 1) : 100;

                $stats = $tree->getStatistics();

                // Histogram bins (buckets)
                $histBins = [1, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000];
                $kdHist = [];
                $seqHist = [];
                foreach ($histBins as $bin) $kdHist[$bin] = 0;
                foreach ($histBins as $bin) $seqHist[$bin] = 0;
                foreach ($kdCompList as $c) {
                    foreach (array_reverse($histBins) as $bin) {
                        if ($c <= $bin) { $kdHist[$bin]++; }
                    }
                }
                foreach ($seqCompList as $c) {
                    foreach (array_reverse($histBins) as $bin) {
                        if ($c <= $bin) { $seqHist[$bin]++; }
                    }
                }

                $results[] = [
                    'size' => $size,
                    'build_time_ms' => round($bTime, 3),
                    'kdtree' => [
                        'total_time_ms' => round($kTime, 3),
                        'avg_time_ms' => round($kTime / $numQueries, 4),
                        'comparisons' => $kdComps,
                        'avg_comparisons' => round($kdComps / $numQueries, 1),
                        'visited' => $kdVisits,
                        'height' => $stats['height'],
                        'max_depth' => $stats['max_depth'],
                        'avg_search_depth' => $kdAvgDepth,
                        'throughput_qps' => $kdThroughput,
                        'histogram' => $kdHist,
                    ],
                    'sequential' => [
                        'total_time_ms' => round($sTime, 3),
                        'avg_time_ms' => round($sTime / $numQueries, 4),
                        'comparisons' => $seqComps,
                        'avg_comparisons' => round($seqComps / $numQueries, 1),
                        'throughput_qps' => $seqThroughput,
                        'histogram' => $seqHist,
                    ],
                    'speedup' => $kTime > 0 ? round($sTime / $kTime, 2) : 0,
                    'queries' => $numQueries,
                    'precision_pct' => $precisionPct,
                    'precision_hits' => $precision,
                    'memory_kb' => round($memDelta / 1024, 1),
                    'memory_mb' => round($memDelta / (1024 * 1024), 2),
                    'memory_kb_per_node' => $size > 0 ? round($memDelta / 1024 / $size, 2) : 0,
                ];
            }

            echo json_encode(['success' => true, 'scalability' => $results]);
            break;

        case 'benchmark':
            // Benchmark con dataset completo y N consultas
            $numQueries = isset($_GET['queries']) ? min(200, max(1, (int)$_GET['queries'])) : 50;
            // Usar el arbol de la sesion si existe (construido desde el Visualizador)
            $sessionTree = null;
            $sessionInfo = '';

            // Intentar desde sesion
            if (isset($_SESSION['kdtree_builder'])) {
                try {
                    $builder = unserialize($_SESSION['kdtree_builder']);
                    if ($builder && $builder->isTreeBuilt()) {
                        $sessionTree = $builder->getTree();
                        $sessionInfo = 'Desde sesion (' . $sessionTree->getSize() . ' nodos)';
                    }
                } catch (Exception $e) {}
            }

            // Si no hay sesion, intentar desde archivo temporal
            if (!$sessionTree) {
                $tmpFile = __DIR__ . '/../tmp_kdtree.dat';
                if (file_exists($tmpFile)) {
                    $data = file_get_contents($tmpFile);
                    if ($data) {
                        try {
                            $builder = unserialize($data);
                            if ($builder && $builder->isTreeBuilt()) {
                                $sessionTree = $builder->getTree();
                                $sessionInfo = 'Desde archivo (' . $sessionTree->getSize() . ' nodos)';
                            }
                        } catch (Exception $e) {}
                    }
                }
            }

            if ($sessionTree !== null) {
                // Usar el arbol de la sesion
                $tree = $sessionTree;
                $subset = $tree->inOrderTraversal();
                $bTime = 0;
                $memDelta = 0;
                $numQueries = min($numQueries, count($subset));
            } else {
                // Crear nuevo arbol desde cero
                $subset = array_slice($allPoints, 0, min(1000, count($allPoints)));
                $memBase = memory_get_usage(false);
                $tree = new KDTree(6);
                $bStart = microtime(true);
                $tree->build($subset);
                $bTime = (microtime(true) - $bStart) * 1000;
                $memTree = memory_get_usage(true);
                $memDelta = max(0, $memTree - $memBase);
            }

            // KD-Tree
            $kStart = microtime(true);
            $kdComps = 0; $kdVisits = 0; $kdSumDepth = 0;
            for ($j = 0; $j < $numQueries; $j++) {
                $q = $subset[array_rand($subset)];
                $r = $tree->nearestNeighbor($q);
                $kdComps += $r['comparisons'];
                $kdVisits += $r['visited'];
                $kdSumDepth += $r['depth'] ?? 0;
            }
            $kTime = (microtime(true) - $kStart) * 1000;
            $kdThroughput = $kTime > 0 ? round(($numQueries / $kTime) * 1000, 1) : 0;
            $kdAvgDepth = $numQueries > 0 ? round($kdSumDepth / $numQueries, 1) : 0;

            // Secuencial
            $sStart = microtime(true);
            $seqComps = 0;
            for ($j = 0; $j < $numQueries; $j++) {
                $q = $subset[array_rand($subset)];
                $bestD = INF;
                foreach ($subset as $p) {
                    $seqComps++;
                    $d = Distance::squaredEuclidean($q, $p);
                    if ($d < $bestD && $p->getId() !== $q->getId()) $bestD = $d;
                }
            }
            $sTime = (microtime(true) - $sStart) * 1000;

            // Precision
            $precision = 0;
            for ($j = 0; $j < $numQueries; $j++) {
                $q = $subset[array_rand($subset)];
                $qId = $q->getId();
                $kr = $tree->nearestNeighbor($q);
                $kdID = $kr['point'] ? $kr['point']->getId() : null;
                $bestD = INF; $bestID = null;
                foreach ($subset as $p) {
                    if ($p->getId() === $qId) continue;
                    $d = Distance::squaredEuclidean($q, $p);
                    if ($d < $bestD) { $bestD = $d; $bestID = $p->getId(); }
                }
                if ($kdID === $qId || $kdID === $bestID) $precision++;
            }

            $speedup = $kTime > 0 ? round($sTime / $kTime, 2) : 0;

            $result = [
                'num_points' => count($subset),
                'num_queries' => $numQueries,
                'session_tree' => $sessionTree !== null,
                'session_info' => $sessionInfo,
                '_debug' => [
                    'session_id' => session_id(),
                    'has_kdtree_builder' => isset($_SESSION['kdtree_builder']),
                    'session_tree_found' => $sessionTree !== null,
                ],
                'sequential' => [
                    'total_time' => round($sTime, 4), 'avg_time' => round($sTime / $numQueries, 4),
                    'total_comparisons' => $seqComps, 'avg_comparisons' => round($seqComps / $numQueries, 1),
                ],
                'kdtree' => [
                    'build_time' => round($bTime, 2),
                    'total_time' => round($kTime, 4), 'avg_time' => round($kTime / $numQueries, 4),
                    'total_comparisons' => $kdComps, 'avg_comparisons' => round($kdComps / $numQueries, 1),
                    'visited' => $kdVisits,
                    'throughput_qps' => $kdThroughput,
                    'avg_search_depth' => $kdAvgDepth,
                ],
                'speedup' => $speedup,
                'precision_pct' => round(($precision / $numQueries) * 100, 1),
                'precision_hits' => $precision,
                'memory_kb' => $sessionTree !== null ? '-' : round($memDelta / 1024, 1),
                'improvement_percent' => round(($speedup - 1) * 100, 1),
            ];

            echo json_encode(['success' => true, 'benchmark' => $result]);
            break;

        case 'dimensions':
            // Variacion de dimensiones: 2D, 4D, 6D
            $dimTests = [2, 4, 6];
            $subset = array_slice($allPoints, 0, 1000);
            $dimResults = [];

            foreach ($dimTests as $dim) {
                $points = [];
                foreach ($subset as $p) {
                    $coords = $p->getCoordinates();
                    $reduced = array_slice($coords, 0, $dim);
                    $points[] = new Point($reduced, $p->getId(), $p->getServerName());
                }

                $tree = new KDTree($dim);
                $bStart = microtime(true);
                $tree->build($points);
                $bTime = (microtime(true) - $bStart) * 1000;

                $numQ = 10;
                $kStart = microtime(true);
                $kdComps = 0;
                for ($j = 0; $j < $numQ; $j++) {
                    $query = $points[array_rand($points)];
                    $result = $tree->nearestNeighbor($query);
                    $kdComps += $result['comparisons'];
                }
                $kTime = (microtime(true) - $kStart) * 1000;

                $stats = $tree->getStatistics();

                $dimResults[] = [
                    'dimensions' => $dim,
                    'build_time_ms' => round($bTime, 3),
                    'avg_search_ms' => round($kTime / $numQ, 4),
                    'avg_comparisons' => round($kdComps / $numQ, 1),
                    'height' => $stats['height'],
                    'max_depth' => $stats['max_depth'],
                    'leaf_nodes' => $stats['leaf_nodes'],
                ];
            }

            echo json_encode(['success' => true, 'dimensions' => $dimResults]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => "Accion desconocida: {$action}"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
