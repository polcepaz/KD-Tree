<?php
/**
 * Script para generar datos semilla en la base de datos.
 * Genera 1000 registros simulados de servidores SAP.
 */

require_once __DIR__ . '/../database/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Limpiar tabla
    $conn->exec("TRUNCATE TABLE server_metrics");

    $servers = ['PRD', 'DEV', 'QAS', 'TST', 'TRN', 'SAP'];
    $stmt = $conn->prepare(
        "INSERT INTO server_metrics (server_name, cpu_usage, memory_usage, hana_memory, dialog_response_time, work_processes, enqueue_locks) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $total = 1000;
    $inserted = 0;

    for ($i = 1; $i <= $total; $i++) {
        $server = $servers[array_rand($servers)];
        $cpu = round(10 + mt_rand() / mt_getrandmax() * 90, 2);
        $mem = round(20 + mt_rand() / mt_getrandmax() * 80, 2);
        $hana = round(5 + mt_rand() / mt_getrandmax() * 95, 2);
        $drt = round(5 + mt_rand() / mt_getrandmax() * 500, 2);
        $wp = rand(1, 50);
        $el = rand(0, 100);

        $stmt->execute([
            sprintf('%s_%04d', $server, $i),
            $cpu, $mem, $hana, $drt, $wp, $el
        ]);

        $inserted++;
    }

    echo "OK: {$inserted} registros insertados correctamente.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
