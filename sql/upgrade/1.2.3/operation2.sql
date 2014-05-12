SELECT "Removing defunct monitor operations" AS "";

DELETE FROM operation WHERE name LIKE "%_monitor";
