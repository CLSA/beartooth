DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN

    SELECT "Adding new allow_missing_consent column to qnaire table" AS "";
    
    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "qnaire"
    AND column_name = "allow_missing_consent";

    IF @test = 0 THEN
      ALTER TABLE qnaire ADD COLUMN allow_missing_consent TINYINT(1) NOT NULL DEFAULT 1 AFTER prev_event_type_id;
    END IF;

  END //
DELIMITER ;

CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
