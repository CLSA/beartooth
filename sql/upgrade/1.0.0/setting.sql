-- replace appointment update interval setting with update span
DELETE FROM setting_value WHERE setting_id = ( 
  SELECT id FROM setting WHERE category = "appointment" and name = "update interval" );
DELETE FROM setting WHERE category = "appointment" and name = "update interval";
INSERT IGNORE INTO setting( category, name, type, value, description )
VALUES( "appointment", "update span", "integer", "30",
"How many days into the future to include appointments when fetching the appointment list." );
