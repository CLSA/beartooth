-- add the new site_id, source_id and next of kin columns
-- we need to create a procedure which only alters the participant table if the
-- site_id, source_id or next of kin columns are missing
DROP PROCEDURE IF EXISTS patch_participant;
DELIMITER //
CREATE PROCEDURE patch_participant()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "site_id" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN site_id INT UNSIGNED NULL DEFAULT NULL
      AFTER language;
      ALTER TABLE participant
      ADD INDEX fk_site_id (site_id ASC);
      ALTER TABLE participant
      ADD CONSTRAINT fk_participant_site_id
      FOREIGN KEY (site_id)
      REFERENCES site (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "source_id" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN source_id INT UNSIGNED NULL DEFAULT NULL
      AFTER uid;
      ALTER TABLE participant
      ADD INDEX fk_source_id (source_id ASC);
      ALTER TABLE participant
      ADD CONSTRAINT fk_participant_source_id
      FOREIGN KEY (source_id)
      REFERENCES source (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;

    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "participant"
      AND COLUMN_NAME = "next_of_kin_first_name" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN next_of_kin_first_name VARCHAR(45) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_last_name VARCHAR(45) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_gender VARCHAR(45) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_phone VARCHAR(45) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_street VARCHAR(512) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_city VARCHAR(100) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_province VARCHAR(45) NULL DEFAULT NULL;
      ALTER TABLE participant
      ADD COLUMN next_of_kin_postal_code VARCHAR(45) NULL DEFAULT NULL;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
