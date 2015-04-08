DROP PROCEDURE IF EXISTS patch_queue_has_participant;
  DELIMITER //
  CREATE PROCEDURE patch_queue_has_participant()
  BEGIN

    SELECT "Reparing nullable timestamp columns in queue_has_participant" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue_has_participant"
      AND COLUMN_NAME LIKE "%_timestamp"
      AND IS_NULLABLE = "YES" );
    IF @test > 0 THEN
      ALTER TABLE queue_has_participant
      MODIFY COLUMN update_timestamp timestamp NOT NULL;

      ALTER TABLE queue_has_participant
      MODIFY COLUMN create_timestamp timestamp NOT NULL;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue_has_participant();
DROP PROCEDURE IF EXISTS patch_queue_has_participant;
