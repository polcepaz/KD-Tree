<?php

/**
 * Endpoint: Insertar un nuevo punto en la base de datos y en el KD-Tree.
 *
 * Metodo: POST
 * Parametros: server_name, cpu_usage, memory_usage, hana_memory, 
 *             dialog_response_time, work_processes, enqueue_locks
 * Retorna: JSON con el resultado de la insercion.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database/ServerMetrics.php';
require_once __DIR__ . '/../classes/TreeBuilder.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['server_name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos de entrada invalidos. Se requiere server_name.',
        ]);
        exit;
    }

    // Insertar en base de datos
    $metrics = new ServerMetrics();
    $newId = $metrics->insert($input);

    $insertResult = [
        'id' => $newId,
        'server_name' => $input['server_name'],
        'cpu_usage' => $input['cpu_usage'],
        'memory_usage' => $input['memory_usage'],
        'hana_memory' => $input['hana_memory'],
        'dialog_response_time' => $input['dialog_response_time'],
        'work_processes' => $input['work_processes'],
        'enqueue_locks' => $input['enqueue_locks'],
    ];

    // Insertar en el arbol si existe
    $treeUpdate = null;
    if (isset($_SESSION['kdtree_builder'])) {
        /** @var TreeBuilder $builder */
        $builder = unserialize($_SESSION['kdtree_builder']);
        if ($builder->isTreeBuilt()) {
            $input['id'] = $newId;
            $treeUpdate = $builder->insertPoint($input);
            $_SESSION['kdtree_builder'] = serialize($builder);
        }
    }

    echo json_encode([
        'success' => true,
        'record' => $insertResult,
        'tree_update' => $treeUpdate,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
