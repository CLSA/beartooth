-- only patch the operation table if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_activity;
DELIMITER //
CREATE PROCEDURE patch_activity()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = ( SELECT DATABASE() )
      AND table_name = "user" );
    IF @test = 1 THEN

      -- censor passwords
      UPDATE activity SET query = "(censored)"
      WHERE operation_id IN ( SELECT id FROM operation WHERE name = "set_password" )
      AND query != "(censored)";

      -- remove all "primary" operations
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE name = "primary" );

      -- remove operation list
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "operation"
        AND name = "list" );

      -- remove participant_sync operations
      DELETE FROM activity WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "participant"
        AND name = "sync" );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_activity();
DROP PROCEDURE IF EXISTS patch_activity;
