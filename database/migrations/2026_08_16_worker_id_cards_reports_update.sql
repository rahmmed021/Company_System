-- Naoshin Enterprise ERP update: worker IDs, blood groups, searchable indexes and expanded designations.
-- Safe to run more than once on MySQL 8+.

ALTER TABLE workers
  ADD COLUMN IF NOT EXISTS blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL AFTER address;

ALTER TABLE workers
  MODIFY role ENUM('foreman','labor','supervisor','mistry','helper','welder','engineer') NOT NULL;

ALTER TABLE id_cards
  MODIFY designation ENUM('foreman','labor','supervisor','mistry','helper','welder','engineer') NOT NULL;

DROP PROCEDURE IF EXISTS backfill_worker_ids;
DROP PROCEDURE IF EXISTS add_index_if_missing;

DELIMITER $$

CREATE PROCEDURE add_index_if_missing(
    IN table_name_input VARCHAR(64),
    IN index_name_input VARCHAR(64),
    IN ddl_input TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = table_name_input
          AND INDEX_NAME = index_name_input
    ) THEN
        SET @ddl = ddl_input;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE backfill_worker_ids()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE worker_pk INT UNSIGNED;
    DECLARE join_date DATE;
    DECLARE date_key CHAR(8);
    DECLARE next_number INT;
    DECLARE generated_id VARCHAR(80);
    DECLARE worker_cursor CURSOR FOR
        SELECT id, COALESCE(joining_date, DATE(created_at), CURRENT_DATE)
        FROM workers
        WHERE id_number IS NULL
           OR id_number = ''
           OR id_number NOT REGEXP '^NEP[0-9]{8}[0-9]{4}$'
        ORDER BY COALESCE(joining_date, DATE(created_at), CURRENT_DATE), id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN worker_cursor;
    read_loop: LOOP
        FETCH worker_cursor INTO worker_pk, join_date;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET date_key = DATE_FORMAT(join_date, '%Y%m%d');
        SELECT COALESCE(MAX(CAST(RIGHT(id_number, 4) AS UNSIGNED)), 0) + 1
          INTO next_number
          FROM workers
         WHERE id_number REGEXP CONCAT('^NEP', date_key, '[0-9]{4}$');

        SET generated_id = CONCAT('NEP', date_key, LPAD(next_number, 4, '0'));
        WHILE EXISTS (SELECT 1 FROM workers WHERE id_number = generated_id AND id <> worker_pk) DO
            SET next_number = next_number + 1;
            SET generated_id = CONCAT('NEP', date_key, LPAD(next_number, 4, '0'));
        END WHILE;

        UPDATE workers SET id_number = generated_id WHERE id = worker_pk;
    END LOOP;
    CLOSE worker_cursor;

    UPDATE id_cards c
    INNER JOIN workers w ON w.id = c.worker_id
       SET c.id_number = w.id_number,
           c.designation = w.role
     WHERE w.id_number IS NOT NULL
       AND (c.id_number IS NULL OR c.id_number = '' OR c.id_number <> w.id_number OR c.designation <> w.role);
END$$

DELIMITER ;

CALL add_index_if_missing('workers', 'idx_workers_search', 'CREATE INDEX idx_workers_search ON workers (id_number, full_name, mobile, joining_date)');
CALL add_index_if_missing('projects', 'idx_projects_search', 'CREATE INDEX idx_projects_search ON projects (name_en, client_mobile)');
CALL add_index_if_missing('attendance', 'idx_attendance_worker_date', 'CREATE INDEX idx_attendance_worker_date ON attendance (worker_id, attendance_date)');
CALL add_index_if_missing('advances', 'idx_advances_worker_project_date', 'CREATE INDEX idx_advances_worker_project_date ON advances (worker_id, project_id, date)');

CALL backfill_worker_ids();

DROP PROCEDURE IF EXISTS backfill_worker_ids;
DROP PROCEDURE IF EXISTS add_index_if_missing;
