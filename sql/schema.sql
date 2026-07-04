CREATE DATABASE IF NOT EXISTS sap_monitor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sap_monitor;

DROP TABLE IF EXISTS server_metrics;

CREATE TABLE server_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_name VARCHAR(100) NOT NULL,
    cpu_usage DECIMAL(5,2) NOT NULL COMMENT 'Porcentaje de uso de CPU',
    memory_usage DECIMAL(5,2) NOT NULL COMMENT 'Porcentaje de uso de memoria',
    hana_memory DECIMAL(5,2) NOT NULL COMMENT 'Porcentaje de memoria SAP HANA',
    dialog_response_time DECIMAL(10,2) NOT NULL COMMENT 'Tiempo promedio de respuesta en ms',
    work_processes INT NOT NULL COMMENT 'Cantidad de Work Processes ocupados',
    enqueue_locks INT NOT NULL COMMENT 'Numero de bloqueos Enqueue',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
