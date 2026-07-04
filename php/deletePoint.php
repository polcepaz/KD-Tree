<?php

/**
 * Endpoint: Eliminar un punto de la base de datos y del KD-Tree.
 *
 * Metodo: POST
 * Parametros: id (ID del registro a eliminar)
 * Retorna: JSON con el resultado de la eliminacion.
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

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere el ID del registro a eliminar',
        ]);
        exit;
    }

    $id = (int)$input['id'];

    // Eliminar de base de datos
    $metrics = new ServerMetrics();
    $dbDeleted = $metrics->delete($id);

    // Eliminar del arbol si existe
    $treeUpdate = null;
    if (isset($_SESSION['kdtree_builder'])) {
        /** @var TreeBuilder $builder */
        $builder = unserialize($_SESSION['kdtree_builder']);
        if ($builder->isTreeBuilt()) {
            $treeUpdate = $builder->deletePoint($id);
            $_SESSION['kdtree_builder'] = serialize($builder);
        }
    }

    echo json_encode([
        'success' => $dbDeleted,
        'db_deleted' => $dbDeleted,
        'tree_update' => $treeUpdate,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
