-- deleting the clerk role until it is redesigned
DELETE FROM role WHERE name = "clerk";
INSERT IGNORE INTO role( name ) VALUES( "typist" );
