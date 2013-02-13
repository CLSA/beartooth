-- censor passwords
UPDATE activity SET query = "(censored)"
WHERE operation_id IN ( SELECT id FROM operation WHERE name = "set_password" )
AND query != "(censored)";

-- remove participant_sync activity
DELETE FROM activity WHERE operation_id IN (
  SELECT id FROM operation
  WHERE subject = "participant"
  AND name = "sync" );
