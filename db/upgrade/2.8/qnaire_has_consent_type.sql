DROP PROCEDURE IF EXISTS patch_qnaire_has_consent_type;
DELIMITER //
CREATE PROCEDURE patch_qnaire_has_consent_type()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Creating new qnaire_has_consent_type table" AS "";

    SELECT COUNT(*) INTO @total
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire_has_consent_type";

    IF 0 = @total THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS qnaire_has_consent_type ( ",
          "qnaire_id INT(10) UNSIGNED NOT NULL, ",
          "consent_type_id INT(10) UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (qnaire_id, consent_type_id), ",
          "INDEX fk_consent_type_id (consent_type_id ASC), ",
          "INDEX fk_qnaire_id (qnaire_id ASC), ",
          "CONSTRAINT fk_qnaire_has_consent_type_qnaire_id ",
            "FOREIGN KEY (qnaire_id) ",
            "REFERENCES qnaire (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_qnaire_has_consent_type_consent_type_id ",
            "FOREIGN KEY (consent_type_id) ",
            "REFERENCES ", @cenozo, ".consent_type (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- all site qnaires will already have draw-blood as a consent type of interest
      SET @sql = CONCAT(
        "INSERT INTO qnaire_has_consent_type( qnaire_id, consent_type_id ) ",
        "SELECT qnaire.id, consent_type.id ",
        "FROM qnaire, ", @cenozo, ".consent_type ",
        "WHERE qnaire.type = 'site' ",
        "AND consent_type.name = 'draw blood'"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_qnaire_has_consent_type();
DROP PROCEDURE IF EXISTS patch_qnaire_has_consent_type;
