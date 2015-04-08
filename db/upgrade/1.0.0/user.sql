-- add the new language column
-- we need to create a procedure which only alters the user
-- table if the language columns is missing
DROP PROCEDURE IF EXISTS patch_user;
DELIMITER //
CREATE PROCEDURE patch_user()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "user"
      AND COLUMN_NAME = "language" );
    IF @test = 0 THEN
      ALTER TABLE user
      ADD COLUMN language ENUM('any','en','fr') NOT NULL DEFAULT 'en';
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_user();
DROP PROCEDURE IF EXISTS patch_user;
