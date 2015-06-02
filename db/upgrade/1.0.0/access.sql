-- deleting the clerk role until it is redesigned
DELETE FROM access
WHERE role_id = (
  SELECT id
  FROM role
  WHERE name = "clerk"
);
