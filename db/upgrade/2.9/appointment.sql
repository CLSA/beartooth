DROP PROCEDURE IF EXISTS patch_appointment;
DELIMITER //
CREATE PROCEDURE patch_appointment()
  BEGIN

    SELECT "Adding new value to outcome enum column in appointment table" AS "";

    SELECT LOCATE( "rescheduled", column_type )
    INTO @rescheduled
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "appointment"
    AND column_name = "outcome";

    IF @rescheduled = 0 THEN
      ALTER TABLE appointment
      MODIFY COLUMN outcome ENUM('completed','rescheduled','cancelled') DEFAULT NULL;
    END IF;

  END //
DELIMITER ;

CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;
