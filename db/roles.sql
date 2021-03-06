-- -----------------------------------------------------
-- Roles
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- make sure all roles exist
INSERT IGNORE INTO cenozo.role( name, tier ) VALUES
( "administrator", 3, true ),
( "coordinator", 2, false ),
( "curator", 2, true ),
( "helpline", 2, true ),
( "interviewer", 1, false ),
( "interviewer+", 1, false ),
( "interviewing_instance", 1, false );

COMMIT;
