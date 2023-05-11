DROP PROCEDURE IF EXISTS patch_qnaire_has_event_type;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_event_type()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new qnaire_has_event_type table" AS "";

    SELECT COUNT(*) INTO @total
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire_has_event_type";

    IF 0 = @total THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS qnaire_has_event_type ( ",
          "qnaire_id INT(10) UNSIGNED NOT NULL, ",
          "event_type_id INT(10) UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (qnaire_id, event_type_id), ",
          "INDEX fk_event_type_id (event_type_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "CONSTRAINT fk_qnaire_has_event_type_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_qnaire_has_event_type_event_type_id ",
            "FOREIGN KEY (event_type_id) ",
            "REFERENCES ", @cenozo, ".event_type (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_qnaire_has_event_type();
DROP PROCEDURE IF EXISTS patch_qnaire_has_event_type;
