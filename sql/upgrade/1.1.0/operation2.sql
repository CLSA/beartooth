-- only patch the operation table if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_operation2;
DELIMITER //
CREATE PROCEDURE patch_operation2()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = ( SELECT DATABASE() )
      AND table_name = "user" );
    IF @test = 1 THEN

      -- remove operation list
      DELETE FROM operation
      WHERE subject = "operation"
      AND name = "list";

      -- remove participant_sync operations
      DELETE FROM operation
      WHERE subject = "participant"
      AND name = "sync";

      -- remove all "primary" operations
      DELETE FROM operation
      WHERE name = "primary";

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation2();
DROP PROCEDURE IF EXISTS patch_operation2;
