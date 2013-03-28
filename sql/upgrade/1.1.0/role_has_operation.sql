-- only add the new role_has_operation entries if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = ( SELECT DATABASE() )
      AND table_name = "user" );
    IF @test = 1 THEN

      -- alternate

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "alternate" AND operation.name = "add"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "alternate" AND operation.name = "add_address"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "alternate" AND operation.name = "add_phone"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "alternate" AND operation.name = "delete"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "alternate" AND operation.name = "delete_address"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "alternate" AND operation.name = "delete_phone"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "alternate" AND operation.name = "edit"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "alternate" AND operation.name = "list"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "alternate" AND operation.name = "new"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "alternate" AND operation.name = "view"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      -- callback

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "callback" AND operation.name = "add"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "callback" AND operation.name = "calendar"
      AND role.name IN ( "coordinator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "callback" AND operation.name = "delete"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "callback" AND operation.name = "edit"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "pull" AND subject = "callback" AND operation.name = "feed"
      AND role.name IN ( "coordinator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "callback" AND operation.name = "list"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "callback" AND operation.name = "new"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "callback" AND operation.name = "view"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      -- event

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "event" AND operation.name = "add"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "event" AND operation.name = "delete"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "event" AND operation.name = "edit"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "event" AND operation.name = "list"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "event" AND operation.name = "new"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "event" AND operation.name = "view"
      AND role.name IN ( "administrator" );

      -- participant

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "participant" AND operation.name = "add_alternate"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "participant" AND operation.name = "add_callback"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "participant" AND operation.name = "add_event"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "participant" AND operation.name = "delete_alternate"
      AND role.name IN ( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "participant" AND operation.name = "delete_callback"
      AND role.name IN( "administrator", "coordinator", "interviewer" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "participant" AND operation.name = "delete_event"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "participant" AND operation.name = "multinote"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "participant" AND operation.name = "multinote"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "pull" AND subject = "participant" AND operation.name = "report"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "participant" AND operation.name = "report"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "push" AND subject = "participant" AND operation.name = "site_reassign"
      AND role.name IN ( "administrator" );

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "participant" AND operation.name = "site_reassign"
      AND role.name IN ( "administrator" );

      -- quota

      INSERT IGNORE INTO role_has_operation( role_id, operation_id )
      SELECT role.id, operation.id FROM role, operation
      WHERE type = "widget" AND subject = "quota" AND operation.name = "chart"
      AND role.name IN ( "administrator" );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
