DROP PROCEDURE IF EXISTS patch_qnaire_has_study;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_study()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new qnaire_has_study table" AS "";

    SELECT COUNT(*) INTO @total
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire_has_study";

    IF 0 = @total THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS qnaire_has_study ( ",
          "qnaire_id INT(10) UNSIGNED NOT NULL, ",
          "study_id INT(10) UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (qnaire_id, study_id), ",
          "INDEX fk_study_id (study_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "CONSTRAINT fk_qnaire_has_study_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_qnaire_has_study_study_id ",
            "FOREIGN KEY (study_id) ",
            "REFERENCES ", @cenozo, ".study (id) ",
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

CALL patch_qnaire_has_study();
DROP PROCEDURE IF EXISTS patch_qnaire_has_study;
