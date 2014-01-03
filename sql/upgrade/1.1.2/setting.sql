DROP PROCEDURE IF EXISTS patch_setting;
DELIMITER //
CREATE PROCEDURE patch_setting()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Updating settings" AS "";

    DELETE FROM setting_value WHERE setting_id IN (
      SELECT id FROM setting
      WHERE category = "queue state"
      AND name LIKE "% not available"
    );

    DELETE FROM setting
    WHERE category = "queue state"
    AND name LIKE "% not available";
    
    UPDATE setting
    SET name = CONCAT( SUBSTR( name, 1, CHAR_LENGTH( name ) - 10 ), " ready" )
    WHERE category = "queue state"
    AND name LIKE "% available";

    UPDATE setting
    SET name = "new participant"
    WHERE category = "queue state"
    AND name = "new participant ready";

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;
