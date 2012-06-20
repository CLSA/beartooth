-- adding the home appointment to the coordinator
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "home_appointment" AND name = "calendar" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "home_appointment" AND name = "feed" );

-- add the new onyx proxy push
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "onyx" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "onyx" AND name = "proxy" );
