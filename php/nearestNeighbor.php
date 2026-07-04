<?php

/**
 * Endpoint: Buscar el vecino mas cercano en el KD-Tree.
 *
 * Metodo: POST
 * Parametros: cpu_usage, memory_usage, hana_memory, dialog_response_time, work_processes, enqueue_locks
 * Retorna: JSON con el vecino mas cercano y las metricas de busqueda.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../classes/TreeBuilder.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['kdtree_builder'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El arbol no ha sido construido. Construyalo primero.',
        ]);
        exit;
    }

    /** @var TreeBuilder $builder */
    $builder = unserialize($_SESSION['kdtree_builder']);

    if (!$builder->isTreeBuilt()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El arbol no esta disponible. Reconstruyalo.',
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos de entrada invalidos',
        ]);
        exit;
    }

    $result = $builder->findNearestNeighbor($input);

    // Guardar el log de recorrido en sesion para la animacion
    if (isset($result['log'])) {
        $_SESSION['traversal_log'] = $result['log'];
    }

    $_SESSION['kdtree_builder'] = serialize($builder);

    echo json_encode([
        'success' => true,
        'result' => $result,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
