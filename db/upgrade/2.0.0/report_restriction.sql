DROP PROCEDURE IF EXISTS patch_report_restriction;
  DELIMITER //
  CREATE PROCEDURE patch_report_restriction()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Adding records to report_restriction table" AS "";

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, null_allowed, restriction_type, custom, ",
        "subject, operator, enum_list, description ) ",
      "SELECT report_type.id, rank, restriction.name, restriction.title, mandatory, null_allowed, type, custom, ",
             "restriction.subject, operator, enum_list, restriction.description ",
      "FROM ", @cenozo, ".report_type, ( ",
        "SELECT ",
          "1 AS rank, ",
          "'qnaire' AS name, ",
          "'Questionnaire' AS title, ",
          "1 AS mandatory, ",
          "1 AS null_allowed, ",
          "'table' AS type, ",
          "1 AS custom, ",
          "'qnaire' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Determines which questionnaire\\'s appointments to list.' AS description ",
        "UNION SELECT ",
          "2 AS rank, ",
          "'site' AS name, ",
          "'Site' AS title, ",
          "0 AS mandatory, ",
          "0 AS null_allowed, ",
          "'table' AS type, ",
          "1 AS custom, ",
          "'site' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to a particular site.' AS description ",
        "UNION SELECT ",
          "3 AS rank, ",
          "'start_date' AS name, ",
          "'Start Date' AS title, ",
          "0 AS mandatory, ",
          "0 AS null_allowed, ",
          "'date' AS type, ",
          "0 AS custom, ",
          "'appointment.datetime' AS subject, ",
          "'>=' AS operator, ",
          "NULL AS enum_list, ",
          "'Appointments on or after the given date.' AS description ",
        "UNION SELECT ",
          "4 AS rank, ",
          "'end_date' AS name, ",
          "'End Date' AS title, ",
          "0 AS mandatory, ",
          "0 AS null_allowed, ",
          "'date' AS type, ",
          "0 AS custom, ",
          "'appointment.datetime' AS subject, ",
          "'<=' AS operator, ",
          "NULL AS enum_list, ",
          "'Appointments on or before the given date.' AS description ",
        "UNION SELECT ",
          "5 AS rank, ",
          "'outcome' AS name, ",
          "'Outcome' AS title, ",
          "0 AS mandatory, ",
          "1 AS null_allowed, ",
          "'enum' AS type, ",
          "0 AS custom, ",
          "'outcome' AS subject, ",
          "NULL AS operator, ",
          "'\"completed\",\"cancelled\"' AS enum_list, ",
          "'Restrict to a particilar appointment outcome (use \"empty\" to restrict to open appointments).' AS description ",
        "UNION SELECT ",
          "6 AS rank, ",
          "'appointment_type' AS name, ",
          "'Appointment Type' AS title, ",
          "0 AS mandatory, ",
          "1 AS null_allowed, ",
          "'table' AS type, ",
          "0 AS custom, ",
          "'appointment_type' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to a particular appointment type.' AS description ",
      ") AS restriction ",
      "WHERE report_type.name = 'appointment'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

    SET @sql = CONCAT(
      "INSERT IGNORE INTO ", @cenozo, ".report_restriction ( ",
        "report_type_id, rank, name, title, mandatory, null_allowed, restriction_type, custom, ",
        "subject, operator, enum_list, description ) ",
      "SELECT report_type.id, rank, restriction.name, restriction.title, mandatory, null_allowed, type, custom, ",
             "restriction.subject, operator, enum_list, restriction.description ",
      "FROM ", @cenozo, ".report_type, ( ",
        "SELECT ",
          "1 AS rank, ",
          "'collection' AS name, ",
          "'Collection' AS title, ",
          "0 AS mandatory, ",
          "0 AS null_allowed, ",
          "'table' AS type, ",
          "0 AS custom, ",
          "'collection' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to a particular collection.' AS description ",
        "UNION SELECT ",
          "2 AS rank, ",
          "'site' AS name, ",
          "'Site' AS title, ",
          "0 AS mandatory, ",
          "0 AS null_allowed, ",
          "'table' AS type, ",
          "0 AS custom, ",
          "'site' AS subject, ",
          "NULL AS operator, ",
          "NULL AS enum_list, ",
          "'Restrict to a particular site.' AS description ",
      ") AS restriction ",
      "WHERE report_type.name = 'sample'" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_report_restriction();
DROP PROCEDURE IF EXISTS patch_report_restriction;
