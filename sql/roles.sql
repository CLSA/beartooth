-- -----------------------------------------------------
-- Roles
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- administrator (specific to this role)
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "administrator"
AND operation.subject = "administrator";

-- onyx instance
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "onyx_instance" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "onyx_instance" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "onyx_instance" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "onyx_instance" AND name = "primary" );

-- interview, assignment and calling
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "assignment" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "assignment" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone_call" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone_call" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone_call" AND name = "end" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "dtmf" );

-- participant
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "sync" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "sync" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "sync" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "list" );

-- appointment
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "list" );

-- availability
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_availability" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_availability" );

-- consent
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_consent" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_consent" );

-- contact information (address and phone)
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_address" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_address" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_phone" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_phone" );

-- qnaire/phase
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "qnaire" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "qnaire" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "qnaire" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "qnaire" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "qnaire" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "qnaire" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "qnaire" AND name = "add_phase" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "qnaire" AND name = "delete_phase" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phase" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phase" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phase" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phase" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phase" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phase" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "survey" AND name = "list" );

-- queue
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "tree" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "tree" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "queue_restriction" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "queue_restriction" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "queue_restriction" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue_restriction" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue_restriction" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue_restriction" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "queue_restriction" AND name = "primary" );

-- quota
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "quota" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "quota" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "quota" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "quota" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "quota" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "quota" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "quota" AND name = "primary" );

-- system messages
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "system_message" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "system_message" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "system_message" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "system_message" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "system_message" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "system_message" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "system_message" AND name = "primary" );

-- ALL reports except for the home appointment report
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "administrator"
AND operation.subject != "home_appointment"
AND operation.name = "report";


-- -----------------------------------------------------
-- -----------------------------------------------------
INSERT INTO role( name, tier ) VALUES( "coordinator", 2 );

-- coordinator (specific to this role)
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "coordinator"
AND operation.subject = "coordinator";

-- setting
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "setting" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "setting" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "setting" AND name = "list" );

-- calendars
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "home_appointment" AND name = "calendar" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "home_appointment" AND name = "feed" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "site_appointment" AND name = "calendar" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "site_appointment" AND name = "feed" );
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "widget" AND subject = "shift_template" AND name = "calendar" );
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "pull" AND subject = "shift_template" AND name = "feed" );

-- user/site/role
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "reset_password" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "set_password" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "user" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "user" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "user" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "user" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "role" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "role" AND name = "list" );

-- onyx instance
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "onyx_instance" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "onyx_instance" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "onyx_instance" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "onyx_instance" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "onyx_instance" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "onyx_instance" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "onyx_instance" AND name = "primary" );

-- operation
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "activity" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "operation" AND name = "list" );

-- access
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "access" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "access" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "user" AND name = "add_access" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "new_access" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "user" AND name = "delete_access" );

-- interview, assignment and calling
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "interview" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "interview" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "assignment" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "assignment" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "assignment" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "assignment" AND name = "end" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "site_assignment" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone_call" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone_call" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone_call" AND name = "end" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "dtmf" );

-- shift templates
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "push" AND subject = "shift_template" AND name = "delete" );
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "push" AND subject = "shift_template" AND name = "edit" );
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "push" AND subject = "shift_template" AND name = "new" );
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "widget" AND subject = "shift_template" AND name = "add" );
-- INSERT INTO role_has_operation
-- SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
--     operation_id = ( SELECT id FROM operation WHERE
--       type = "widget" AND subject = "shift_template" AND name = "view" );

-- participant
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "list" );

-- appointment
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "appointment" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "appointment" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "appointment" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_appointment" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_appointment" );

-- availability
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_availability" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_availability" );

-- consent
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_consent" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_consent" );

-- contact information (address and phone)
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_address" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_address" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_phone" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_phone" );

-- queue
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "queue" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "tree" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "tree" );

-- notes
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "note" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "note" AND name = "edit" );

-- ALL reports except for the home appointment report
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "coordinator"
AND operation.subject != "home_appointment"
AND operation.name = "report";

-- system messages
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "system_message" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "system_message" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "system_message" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "system_message" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "system_message" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "system_message" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "system_message" AND name = "primary" );


-- -----------------------------------------------------
-- -----------------------------------------------------
INSERT INTO role( name ) VALUES( "interviewer" );

-- interviewer (specific to this role)
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "interviewer"
AND operation.subject = "interviewer"
AND operation.name != "list";

-- calendars
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "home_appointment" AND name = "calendar" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "home_appointment" AND name = "feed" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "site_appointment" AND name = "calendar" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "site_appointment" AND name = "feed" );

-- interview, assignment and calling
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "assignment" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "assignment" AND name = "end" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "assignment" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "assignment" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "home_assignment" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "site_assignment" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone_call" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone_call" AND name = "begin" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone_call" AND name = "end" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "dtmf" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "play" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "begin_monitor" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "voip" AND name = "end_monitor" );

-- participant
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "participant" AND name = "primary" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "withdraw" );

-- appointment
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "appointment" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "appointment" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "appointment" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "appointment" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_appointment" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_appointment" );

-- availability
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "availability" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "availability" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_availability" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_availability" );

-- consent
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "consent" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_consent" );

-- contact information (address and phone)
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "address" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "address" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_address" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_address" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "delete" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "edit" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "phone" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "add" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "view" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "phone" AND name = "list" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "add_phone" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "delete_phone" );

-- report
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "home_appointment" AND name = "report" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "home_appointment" AND name = "report" );


-- -----------------------------------------------------
-- -----------------------------------------------------
INSERT INTO role( name ) VALUES( "onyx" );

-- onyx (specific to this role)
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "onyx"
AND operation.subject = "onyx";

INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "onyx" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "new" );
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "onyx" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "appointment" AND name = "list" );


-- -----------------------------------------------------
-- -----------------------------------------------------
INSERT INTO role( name ) VALUES( "typist" );

-- typist (specific to this role)
INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id
FROM role, operation
WHERE role.name = "typist"
AND operation.subject = "typist";

-- consent
INSERT INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "typist" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "new" );

COMMIT;
