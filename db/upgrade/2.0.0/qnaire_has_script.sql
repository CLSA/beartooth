DROP PROCEDURE IF EXISTS patch_qnaire_has_script;
  DELIMITER //
  CREATE PROCEDURE patch_qnaire_has_script()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Creating new qnaire_has_script table" AS "";

    SET @sql = CONCAT(
      "CREATE TABLE IF NOT EXISTS qnaire_has_script ( ",
        "qnaire_id INT UNSIGNED NOT NULL, ",
        "script_id INT UNSIGNED NOT NULL, ",
        "update_timestamp TIMESTAMP NOT NULL, ",
        "create_timestamp TIMESTAMP NOT NULL, ",
        "PRIMARY KEY (qnaire_id, script_id), ",
        "INDEX fk_script_id (script_id ASC), ",
        "INDEX fk_qnaire_id (qnaire_id ASC), ",
        "CONSTRAINT fk_qnaire_has_script_qnaire_id ",
          "FOREIGN KEY (qnaire_id) ",
          "REFERENCES qnaire (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE, ",
        "CONSTRAINT fk_qnaire_has_script_script_id ",
          "FOREIGN KEY (script_id) ",
          "REFERENCES ", @cenozo, ".script (id) ",
          "ON DELETE CASCADE ",
          "ON UPDATE CASCADE) ",
      "ENGINE = InnoDB ",
      "COMMENT = 'aka: mandatory script'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire_has_script();
DROP PROCEDURE IF EXISTS patch_qnaire_has_script;
