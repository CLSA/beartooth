-- consent new for typist
INSERT IGNORE INTO role_has_operation
SET role_id = ( SELECT id FROM role WHERE name = "typist" ),
    operation_id = ( SELECT id FROM operation WHERE
      type = "push" AND subject = "consent" AND name = "new" );
