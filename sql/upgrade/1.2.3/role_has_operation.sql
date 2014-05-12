SELECT "Removing defunct monitor operations from roles" AS "";

DELETE FROM role_has_operation
WHERE operation_id IN (
  SELECT id
  FROM operation
  WHERE name LIKE "%_monitor"
);
