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
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "delete" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "edit" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "new" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "add" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "view" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "list" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "participant" AND name = "add_callback" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "participant" AND name = "delete_callback" );

      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "delete" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "edit" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "new" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "add" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "view" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "list" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "participant" AND name = "add_callback" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "participant" AND name = "delete_callback" );

      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "delete" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "edit" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "callback" AND name = "new" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "add" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "view" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "list" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "callback" AND name = "calendar" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "pull" AND subject = "callback" AND name = "feed" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "widget" AND subject = "participant" AND name = "add_callback" );
      INSERT IGNORE INTO role_has_operation
      SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
          operation_id = ( SELECT id FROM operation WHERE
            type = "push" AND subject = "participant" AND name = "delete_callback" );
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL update_role_has_operation();
DROP PROCEDURE IF EXISTS update_role_has_operation;
