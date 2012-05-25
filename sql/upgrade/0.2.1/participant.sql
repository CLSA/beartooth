-- add the new site_id, source_id, consent_to_draw_blood_continue, physical_tests_continue
-- and next of kin columns we need to create a procedure which only alters the participant
-- table if these columns are missing
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
      AND COLUMN_NAME = "consent_to_draw_blood_continue" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN consent_to_draw_blood_continue TINYINT(1) NULL DEFAULT NULL
      AFTER consent_to_draw_blood;
      ALTER TABLE participant
      ADD COLUMN physical_tests_continue TINYINT(1) NULL DEFAULT NULL
      AFTER consent_to_draw_blood_continue;
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

-- the consent to draw blood variable is now an enum and may be null
ALTER TABLE participant MODIFY consent_to_draw_blood ENUM( "YES", "NO" ) NULL DEFAULT NULL;
UPDATE participant SET consent_to_draw_blood = NULL WHERE consent_to_draw_blood = "";
