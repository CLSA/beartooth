SELECT "Adding new operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "add", true,
"View a form for creating new association between regions and sites." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "delete", true,
"Removes an association between a region and a site from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "edit", true,
"Edits an association between a region and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "list", true,
"List associations between regions and sites in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "region_site", "new", true,
"Add a new association between a region and a site to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "region_site", "view", true,
"View an association between a region and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "add", true,
"View a form for creating new association between postcodes and sites." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "delete", true,
"Removes an association between a postcode and a site from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "edit", true,
"Edits an association between a postcode and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "list", true,
"List associations between postcodes and sites in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "jurisdiction", "new", true,
"Add a new association between a postcode and a site to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "jurisdiction", "view", true,
"View an association between a postcode and a site." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "quota", "add_qnaire", true,
"A form to disable a quota for a particular qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "delete_qnaire", true,
"Re-enable quotas for a particular qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "quota", "new_qnaire", true,
"Disable a quota for a particular qnaire." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "reverse_withdraw", true,
"Removes the last negative verbal consent from the participant and deletes all withdraw survey data." );
