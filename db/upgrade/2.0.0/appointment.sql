DROP PROCEDURE IF EXISTS patch_appointment;
  DELIMITER //
  CREATE PROCEDURE patch_appointment()
  BEGIN

    SELECT "Replacing participant_id with interview_id column in appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "interview_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE appointment 
      ADD COLUMN interview_id INT UNSIGNED NOT NULL
      AFTER participant_id;

      ALTER TABLE appointment 
      ADD INDEX fk_interview_id( interview_id ASC ), 
      ADD CONSTRAINT fk_appointment_interview_id 
      FOREIGN KEY( interview_id ) REFERENCES interview( id ) 
      ON DELETE NO ACTION ON UPDATE NO ACTION;

      -- fill in the new interview_id column using the existing participant_id column
      UPDATE appointment 
      JOIN interview ON appointment.participant_id = interview.participant_id
      JOIN qnaire ON interview.qnaire_id = qnaire.id
      SET interview_id = interview.id
      WHERE (
        appointment.address_id IS NOT NULL AND qnaire.type = "home"
      ) OR (
        appointment.address_id IS NULL AND qnaire.type = "site"
      );

      -- now get rid of the participant column, index and constraint
      ALTER TABLE appointment
      DROP FOREIGN KEY fk_appointment_participant_id;

      ALTER TABLE appointment
      DROP INDEX fk_participant_id;

      ALTER TABLE appointment
      DROP COLUMN participant_id;

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;

    SELECT "Replacing completed column with outcome in appointment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "appointment"
      AND COLUMN_NAME = "completed" );
    IF @test = 1 THEN
      ALTER TABLE appointment
      ADD COLUMN outcome ENUM('completed', 'cancelled') NULL DEFAULT NULL,
      ADD INDEX dk_outcome (outcome ASC);

      UPDATE appointment SET outcome = 'completed'
      WHERE completed = 1;

      ALTER TABLE appointment
      DROP INDEX dk_reached,
      DROP COLUMN completed;
    END IF;


  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;

SELECT "Adding new triggers to appointment table" AS "";

DELIMITER $$

DROP TRIGGER IF EXISTS appointment_AFTER_INSERT $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_AFTER_INSERT AFTER INSERT ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( NEW.interview_id );
END;$$


DROP TRIGGER IF EXISTS appointment_AFTER_UPDATE $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_AFTER_UPDATE AFTER UPDATE ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( NEW.interview_id );
END;$$


DROP TRIGGER IF EXISTS appointment_AFTER_DELETE $$
CREATE DEFINER = CURRENT_USER TRIGGER appointment_AFTER_DELETE AFTER DELETE ON appointment FOR EACH ROW
BEGIN
  CALL update_interview_last_appointment( OLD.interview_id );
END;$$

DELIMITER ;
