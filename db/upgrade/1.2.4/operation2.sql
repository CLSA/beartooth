SELECT "Removing defunct operations" AS "";

DELETE FROM operation
WHERE subject = "home_appointment"
AND name = "report";
