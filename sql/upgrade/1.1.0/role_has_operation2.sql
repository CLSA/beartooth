-- only add the new role_has_operation entries if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS update_role_has_operation;
DELIMITER //
CREATE PROCEDURE update_role_has_operation()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = ( SELECT DATABASE() )
      AND table_name = "user" );
    IF @test = 1 THEN
      DELETE FROM role_has_operation WHERE operation_id IN (
        SELECT id FROM operation
        WHERE subject = "participant"
        AND name = "sync" );
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL update_role_has_operation();
DROP PROCEDURE IF EXISTS update_role_has_operation;
