-- add the new defer_until column
-- we need to create a procedure which only alters the participant table if the
-- the defer_until column does not exist
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
      AND COLUMN_NAME = "defer_until" );
    IF @test = 0 THEN
      ALTER TABLE participant
      ADD COLUMN defer_until DATE NULL
      AFTER site_id;
      ALTER TABLE participant
      ADD INDEX dk_defer_until (defer_until ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_participant();
DROP PROCEDURE IF EXISTS patch_participant;
