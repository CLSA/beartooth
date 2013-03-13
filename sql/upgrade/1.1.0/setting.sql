-- only patch the operation table if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = ( SELECT DATABASE() )
      AND table_name = "user" );
    IF @test = 1 THEN

      INSERT IGNORE INTO setting( category, name, type, value, description )
      VALUES( "callback", "call pre-window", "integer", "5",
      "Number of minutes before a callback when it is considered assignable." );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
