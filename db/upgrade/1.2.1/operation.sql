SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "self", "temporary_file", false,
"Upload a temporary file to the server." );
