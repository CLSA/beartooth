-- add the new disabled column to the quota table
DROP PROCEDURE IF EXISTS patch_quota;
DELIMITER //
CREATE PROCEDURE patch_quota()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "quota"
      AND COLUMN_NAME = "disabled" );
    IF @test = 0 THEN
      ALTER TABLE quota
      ADD COLUMN disabled TINYINT(1) NOT NULL DEFAULT false;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_quota();
DROP PROCEDURE IF EXISTS patch_quota;
