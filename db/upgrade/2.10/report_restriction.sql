DROP PROCEDURE IF EXISTS patch_report_restriction;
  DELIMITER //
  CREATE PROCEDURE patch_report_restriction()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, restriction_type, custom, subject, description ",
      ") ",
      'SELECT report_type.id, 1, "site", "Site", 1, "table", 1, "site", ',
        '"Restrict to a particular site." ',
      "FROM ", @cenozo, ".report_type ",
      "WHERE report_type.name = 'address'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, restriction_type, custom, subject, description ",
      ") ",
      'SELECT report_type.id, 2, "qnaire", "Questionnaire", 1, "table", 1, "qnaire", ',
        '"Defines which questionnaire the included participants must be currently eligible for." ',
      "FROM ", @cenozo, ".report_type ",
      "WHERE report_type.name = 'address'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report_restriction();
DROP PROCEDURE IF EXISTS patch_report_restriction;
