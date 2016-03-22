DROP PROCEDURE IF EXISTS patch_appointment_type;
  DELIMITER //
  CREATE PROCEDURE patch_appointment_type()
  BEGIN

    SELECT "Adding new color column to appointment_type" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment_type"
      AND COLUMN_NAME = "color" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE appointment_type 
      ADD COLUMN color CHAR(7) NOT NULL
      AFTER name;

      UPDATE appointment_type SET color = "#00ff00";

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment_type();
DROP PROCEDURE IF EXISTS patch_appointment_type;
