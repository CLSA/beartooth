-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- make sure all roles exist
INSERT IGNORE INTO cenozo.role( name, tier ) VALUES
( "administrator", 3, true ),
( "coordinator", 2, false ),
( "curator", 2, true ),
( "helpline", 2, true ),
( "interviewer", 1, false ),
( "onyx", 1, false );

-- add states to roles
INSERT IGNORE INTO cenozo.role_has_state( role_id, state_id )
SELECT role.id, state.id
FROM role, state
WHERE state.name NOT IN( "unreachable", "consent unavailable" );

INSERT IGNORE INTO cenozo.role_has_state( role_id, state_id )
SELECT role.id, state.id
FROM role, state
WHERE state.name = "unreachable"
AND role.name IN ( "administrator", "curator" );

INSERT IGNORE INTO cenozo.role_has_state( role_id, state_id )
SELECT role.id, state.id
FROM role, state
WHERE state.name = "consent unavailable"
AND role.name IN ( "administrator", "curator" );

-- access

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "access" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "access" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

-- activity

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "activity" AND operation.name = "chart"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "activity" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

-- address

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "address" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "address" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "address" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "address" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "address" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "address" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- alternate

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "alternate" AND operation.name = "add"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "alternate" AND operation.name = "add_address"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "alternate" AND operation.name = "add_phone"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "alternate" AND operation.name = "delete"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "alternate" AND operation.name = "delete_address"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "alternate" AND operation.name = "delete_phone"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "alternate" AND operation.name = "edit"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "alternate" AND operation.name = "list"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "alternate" AND operation.name = "new"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT IGNORE INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "alternate" AND operation.name = "view"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- appointment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "appointment" AND operation.name = "add"
AND role.name IN( "coordinator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "appointment" AND operation.name = "delete"
AND role.name IN( "coordinator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "appointment" AND operation.name = "edit"
AND role.name IN( "coordinator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "appointment" AND operation.name = "list"
AND role.name IN ( "onyx" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "appointment" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "appointment" AND operation.name = "new"
AND role.name IN( "coordinator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "appointment" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "helpline", "interviewer" );

-- assignment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "assignment" AND operation.name = "begin"
AND role.name IN( "coordinator", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "assignment" AND operation.name = "end"
AND role.name IN( "coordinator", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "assignment" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "assignment" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- availability

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "availability" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "availability" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "availability" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "availability" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "availability" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "availability" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- callback

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "callback" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "callback" AND operation.name = "calendar"
AND role.name IN ( "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "callback" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "callback" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "callback" AND operation.name = "feed"
AND role.name IN ( "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "callback" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "callback" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "callback" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- collection

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "add"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "add_participant"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "add_user"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "delete"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "delete_participant"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "delete_user"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "edit"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "list"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "new"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "new_participant"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "collection" AND operation.name = "new_user"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "collection" AND operation.name = "view"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

-- consent

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "consent" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "consent" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "consent" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "consent" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "consent" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer", "onyx" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "consent" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- email

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "email" AND operation.name = "report"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "email" AND operation.name = "report"
AND role.name IN ( "administrator", "curator" );

-- event

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "event" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "event" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "event" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "event" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- home_appointment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "home_appointment" AND operation.name = "calendar"
AND role.name IN( "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "home_appointment" AND operation.name = "feed"
AND role.name IN( "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "home_appointment" AND operation.name = "report"
AND role.name IN ( "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "home_appointment" AND operation.name = "report"
AND role.name IN ( "interviewer" );

-- home_assignment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "home_assignment" AND operation.name = "select"
AND role.name IN ( "interviewer" );

-- interview

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "interview" AND operation.name = "edit"
AND role.name IN ( "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "interview" AND operation.name = "report"
AND role.name IN ( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "interview" AND operation.name = "report"
AND role.name IN ( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "interview" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "interview" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

-- jurisdiction

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "jurisdiction" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "jurisdiction" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "jurisdiction" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "jurisdiction" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "jurisdiction" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "jurisdiction" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- language

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "language" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "language" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "language" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- mailout_required

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "mailout_required" AND operation.name = "report"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "mailout_required" AND operation.name = "report"
AND role.name IN ( "administrator" );

-- note

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "note" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "note" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

-- onyx

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "onyx" AND operation.name = "consent"
AND role.name IN ( "onyx" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "onyx" AND operation.name = "participants"
AND role.name IN ( "onyx" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "onyx" AND operation.name = "proxy"
AND role.name IN ( "onyx" );

-- onyx_instance

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "onyx_instance" AND operation.name = "add"
AND role.name IN ( "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "onyx_instance" AND operation.name = "delete"
AND role.name IN ( "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "onyx_instance" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "onyx_instance" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "onyx_instance" AND operation.name = "new"
AND role.name IN ( "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "onyx_instance" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator" );

-- participant

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add"
AND role.name IN ( "NULL" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_address"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_alternate"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_appointment"
AND role.name IN( "coordinator", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_availability"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_callback"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_consent"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_event"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "add_phone"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete"
AND role.name IN ( "NULL" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_address"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_alternate"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_appointment"
AND role.name IN( "coordinator", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_availability"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_callback"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_consent"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_event"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "delete_phone"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer", "onyx" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "multinote"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "multinote"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "multinote"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "new"
AND role.name IN ( "NULL" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "report"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "report"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "reverse_withdraw"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "search"
AND role.name IN( "administrator", "curator", "helpline", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "secondary"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "site_reassign"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "site_reassign"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "site_reassign"
AND role.name IN ( "administrator", "curator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant" AND operation.name = "tree"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "tree"
AND role.name IN( "administrator", "coordinator", "curator", "helpline" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "participant" AND operation.name = "withdraw"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant" AND operation.name = "withdraw"
AND role.name IN ( "administrator", "coordinator", "curator", "helpline" );

-- participant_tree

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "participant_tree" AND operation.name = "report"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "participant_tree" AND operation.name = "report"
AND role.name IN( "administrator", "coordinator" );

-- phase

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phase" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phase" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phase" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phase" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phase" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phase" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- phone

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phone" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phone" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phone" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phone" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phone" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phone" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- phone_call

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phone_call" AND operation.name = "begin"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "phone_call" AND operation.name = "end"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "phone_call" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

-- progress

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "progress" AND operation.name = "report"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "progress" AND operation.name = "report"
AND role.name IN( "administrator", "coordinator" );

-- qnaire

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "qnaire" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "qnaire" AND operation.name = "add_phase"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "qnaire" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "qnaire" AND operation.name = "delete_phase"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "qnaire" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "qnaire" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "qnaire" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "qnaire" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- queue

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "queue" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "queue" AND operation.name = "repopulate"
AND role.name IN( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "queue" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator" );

-- quota

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "quota" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "quota" AND operation.name = "add_qnaire"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "quota" AND operation.name = "chart"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "quota" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "quota" AND operation.name = "delete_qnaire"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "quota" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "quota" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "quota" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "quota" AND operation.name = "new_qnaire"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "quota" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator" );

-- region_site

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "region_site" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "region_site" AND operation.name = "delete"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "region_site" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "region_site" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "region_site" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "region_site" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- role

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "role" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

-- sample

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "sample" AND operation.name = "report"
AND role.name IN ( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "sample" AND operation.name = "report"
AND role.name IN ( "administrator", "coordinator" );

-- setting

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "setting" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "setting" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "setting" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator" );

-- site

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "add"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "add_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "delete_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "edit"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "list"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "new"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "site" AND operation.name = "new_access"
AND role.name IN ( "administrator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site" AND operation.name = "view"
AND role.name IN ( "administrator" );

-- site_appointment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site_appointment" AND operation.name = "calendar"
AND role.name IN( "coordinator", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "site_appointment" AND operation.name = "feed"
AND role.name IN( "coordinator", "interviewer" );

-- site_assignment

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "site_assignment" AND operation.name = "select"
AND role.name IN( "coordinator", "interviewer" );

-- survey

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "survey" AND operation.name = "list"
AND role.name IN ( "administrator" );

-- system_message

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "system_message" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "system_message" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "system_message" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "system_message" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "system_message" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "system_message" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator" );

-- user

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add_access"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "add_language"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete_access"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "delete_language"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "edit"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "pull" AND subject = "user" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "list"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new_access"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "new_language"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "reset_password"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "user" AND operation.name = "set_password"
AND role.name IN( "administrator", "coordinator" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "widget" AND subject = "user" AND operation.name = "view"
AND role.name IN( "administrator", "coordinator" );

-- voip

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "voip" AND operation.name = "dtmf"
AND role.name IN( "administrator", "coordinator", "curator", "helpline", "interviewer" );

INSERT INTO role_has_operation( role_id, operation_id )
SELECT role.id, operation.id FROM cenozo.role, operation
WHERE type = "push" AND subject = "voip" AND operation.name = "play"
AND role.name IN ( "interviewer" );

COMMIT;
