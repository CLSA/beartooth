DELETE FROM role_has_operation
WHERE operation_id IN (
  SELECT id
  FROM operation
  WHERE subject = "role"
  AND name IN ( "add_operation", "new_operation", "delete_operation" )
);

-- deleting the clerk role until it is redesigned
DELETE FROM role_has_operation
WHERE role_id = ( SELECT id FROM role WHERE name = "clerk" );

DELETE FROM role_has_operation
WHERE role_id = ( SELECT id FROM role WHERE name = "administrator" )
AND operation_id IN (
  SELECT id
  FROM operation
  WHERE subject = "onyx_instance"
  AND name IN ( "delete", "add", "new" )
);
