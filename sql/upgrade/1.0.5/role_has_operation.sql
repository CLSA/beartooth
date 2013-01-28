INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "secondary" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "secondary" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "interviewer" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "participant" AND name = "secondary" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "administrator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "activity" AND name = "chart" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "quota" AND name = "view" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "widget" AND subject = "quota" AND name = "list" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "coordinator" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "pull" AND subject = "quota" AND name = "primary" );
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "onyx" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "participant" AND name = "edit" );
