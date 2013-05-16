INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "secondary", true, "Lists a participant's alternates for sourcing purposes." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "activity", "chart", true, "Displays a chart describing system activity." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "mailout_required", "report", true, "Set up a mailout required report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "mailout_required", "report", true, "Download a mailout required report." );
