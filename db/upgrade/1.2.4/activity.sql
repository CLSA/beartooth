SELECT "Converting home_appointment-report activity to appointment-report" AS "";

UPDATE activity
SET operation_id = (
  SELECT id FROM operation
  WHERE type = "pull"
  AND subject = "appointment"
  AND name = "report"
)
WHERE operation_id = (
  SELECT id FROM operation
  WHERE type = "pull"
  AND subject = "home_appointment"
  AND name = "report"
);

UPDATE activity
SET operation_id = (
  SELECT id FROM operation
  WHERE type = "widget"
  AND subject = "appointment"
  AND name = "report"
)
WHERE operation_id = (
  SELECT id FROM operation
  WHERE type = "widget"
  AND subject = "home_appointment"
  AND name = "report"
);
