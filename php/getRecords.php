<?php

/**
 * Endpoint: Obtener registros paginados de la base de datos.
 *
 * Metodo: GET
 * Parametros: page (opcional, por defecto 1), per_page (opcional, por defecto 50)
 * Retorna: JSON con los registros paginados.
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

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(5000, max(10, (int)$_GET['per_page'])) : 100;

    $metrics = new ServerMetrics();
    $result = $metrics->getPaginated($page, $perPage);

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
