USE sap_monitor;

TRUNCATE TABLE server_metrics;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS generate_seed_data()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE server_base VARCHAR(50);
    DECLARE cpu_val DECIMAL(5,2);
    DECLARE mem_val DECIMAL(5,2);
    DECLARE hana_val DECIMAL(5,2);
    DECLARE drt_val DECIMAL(10,2);
    DECLARE wp_val INT;
    DECLARE el_val INT;

    WHILE i <= 1000 DO
        SET server_base = CONCAT(
            ELT(1 + FLOOR(RAND() * 6),
                'PRD', 'DEV', 'QAS', 'TST', 'TRN', 'SAP'
            )
        );

        SET cpu_val = ROUND(10 + RAND() * 90, 2);
        SET mem_val = ROUND(20 + RAND() * 80, 2);
        SET hana_val = ROUND(5 + RAND() * 95, 2);
        SET drt_val = ROUND(5 + RAND() * 500, 2);
        SET wp_val = FLOOR(1 + RAND() * 50);
        SET el_val = FLOOR(0 + RAND() * 100);

        INSERT INTO server_metrics (
            server_name, cpu_usage, memory_usage, hana_memory,
            dialog_response_time, work_processes, enqueue_locks
        ) VALUES (
            CONCAT(server_base, '_', LPAD(i, 4, '0')),
            cpu_val, mem_val, hana_val, drt_val, wp_val, el_val
        );

        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

CALL generate_seed_data();
DROP PROCEDURE IF EXISTS generate_seed_data;
