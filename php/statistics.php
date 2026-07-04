<?php

/**
 * Endpoint: Obtener estadisticas del KD-Tree y ejecutar pruebas comparativas.
 *
 * Metodo: GET
 * Retorna: JSON con estadisticas del arbol y resultados de benchmarks.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database/ServerMetrics.php';
require_once __DIR__ . '/../classes/TreeBuilder.php';
require_once __DIR__ . '/../classes/Metrics.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $action = $_GET['action'] ?? 'status';

    switch ($action) {
        case 'status':
            // Estado actual del arbol
            $response = [
                'tree_built' => false,
                'statistics' => null,
                'total_records' => 0,
            ];

            $metrics = new ServerMetrics();
            $response['total_records'] = $metrics->count();

            if (isset($_SESSION['kdtree_builder'])) {
                /** @var TreeBuilder $builder */
                $builder = unserialize($_SESSION['kdtree_builder']);
                if ($builder->isTreeBuilt()) {
                    $response['tree_built'] = true;
                    $response['statistics'] = $builder->getStatistics();
                    $response['tree'] = $builder->getTreeVisualization();
                }
            }

            $response['php_version'] = phpversion();
            $response['memory_limit'] = ini_get('memory_limit');

            echo json_encode($response);
            break;

        case 'benchmark':
            // Pruebas comparativas
            $metrics = new ServerMetrics();
            $allData = $metrics->getAllAsArray();

            if (empty($allData)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No hay datos para realizar benchmarks',
                ]);
                exit;
            }

            $benchmark = new Metrics();
            $numQueries = (int)($_GET['queries'] ?? 50);
            $numQueries = min($numQueries, count($allData));

            $result = $benchmark->runBenchmark($allData, $numQueries);

            echo json_encode([
                'success' => true,
                'benchmark' => $result,
            ]);
            break;

        case 'scalability':
            // Prueba de escalabilidad
            $metrics = new ServerMetrics();
            $allData = $metrics->getAllAsArray();

            if (empty($allData)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No hay datos para realizar pruebas de escalabilidad',
                ]);
                exit;
            }

            $benchmark = new Metrics();
            $results = $benchmark->runScalabilityTest($allData);

            echo json_encode([
                'success' => true,
                'scalability' => $results,
            ]);
            break;

        case 'rebuild':
            // Reconstruir el arbol
            if (!isset($_SESSION['kdtree_builder'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'El arbol no ha sido construido',
                ]);
                exit;
            }

            $requestedNodes = isset($_GET['nodes']) ? (int)$_GET['nodes'] : 0;

            if ($requestedNodes > 0) {
                // Reconstruir con un subconjunto especifico de nodos
                require_once __DIR__ . '/../database/ServerMetrics.php';
                $metrics = new ServerMetrics();
                $allData = $metrics->getAllAsArray();

                if (empty($allData)) {
                    echo json_encode(['success' => false, 'error' => 'No hay datos en la base de datos']);
                    exit;
                }

                $totalAvailable = count($allData);
                $numNodes = min($requestedNodes, $totalAvailable);

                // Seleccion aleatoria
                $keys = array_rand($allData, $numNodes);
                if (!is_array($keys)) $keys = [$keys];
                $selected = array_map(fn($k) => $allData[$k], $keys);
                shuffle($selected);

                $builder = new \TreeBuilder(6);
                $rebuildResult = $builder->buildFromData($selected);
                $rebuildResult['message'] = "Arbol reconstruido con {$numNodes} nodos de {$totalAvailable} disponibles (seleccion aleatoria)";
            } else {
                // Reconstruir con todos los nodos (rebalanceo)
                /** @var TreeBuilder $builder */
                $builder = unserialize($_SESSION['kdtree_builder']);
                if (!$builder->isTreeBuilt()) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'El arbol no esta disponible',
                    ]);
                    exit;
                }
                $rebuildResult = $builder->rebuild();
            }

            $_SESSION['kdtree_builder'] = serialize($builder);
            echo json_encode($rebuildResult);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Accion desconocida: {$action}",
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
