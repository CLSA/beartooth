DROP PROCEDURE IF EXISTS patch_user;
DELIMITER //
CREATE PROCEDURE patch_user()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Renaming interviewing instance first_name in user table" AS "";

    SET @sql = CONCAT(
      "UPDATE ", @cenozo, ".user ",
      "SET first_name = REPLACE( first_name, 'onyx', 'interviewing' ) ",
      "WHERE first_name LIKE '%onyx instance'"
    );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_user();
DROP PROCEDURE IF EXISTS patch_user;
