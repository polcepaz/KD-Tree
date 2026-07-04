<?php

/**
 * Endpoint: Construir el KD-Tree a partir de los datos en la base de datos.
 *
 * Metodo: POST
 * Retorna: JSON con el resultado de la construccion.
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

    $metrics = new ServerMetrics();
    $data = $metrics->getAllAsArray();

    if (empty($data)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No hay datos disponibles para construir el arbol',
        ]);
        exit;
    }

    $builder = new TreeBuilder(6);

    $requestedNodes = isset($_GET['nodes']) ? (int)$_GET['nodes'] : 0;
    if ($requestedNodes > 0 && $requestedNodes < count($data)) {
        $keys = array_rand($data, $requestedNodes);
        if (!is_array($keys)) $keys = [$keys];
        $selected = array_map(fn($k) => $data[$k], $keys);
        shuffle($selected);
        $result = $builder->buildFromData($selected);
        $result['message'] = "Arbol construido con {$requestedNodes} nodos de " . count($data) . " disponibles (seleccion aleatoria)";
    } else {
        $result = $builder->buildFromData($data);
    }

    // Guardar el arbol en la sesion
    $_SESSION['kdtree_builder'] = serialize($builder);

    // Respaldo: archivo temporal (accesible sin sesion)
    file_put_contents(__DIR__ . '/../tmp_kdtree.dat', serialize($builder));

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
