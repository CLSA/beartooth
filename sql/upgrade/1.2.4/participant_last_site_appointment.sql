DROP PROCEDURE IF EXISTS patch_participant_last_site_appointment;
DELIMITER //
CREATE PROCEDURE patch_participant_last_site_appointment()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new participant_last_site_appointment view" AS "";

    SET @sql = CONCAT(
      "CREATE OR REPLACE VIEW participant_last_site_appointment AS ",
      "SELECT participant.id AS participant_id, t1.id AS appointment_id, t1.completed ",
      "FROM ", @cenozo, ".participant ",
      "LEFT JOIN appointment t1 ",
      "ON participant.id = t1.participant_id ",
      "AND t1.datetime = ( ",
        "SELECT MAX( t2.datetime ) FROM appointment t2 ",
        "WHERE t1.participant_id = t2.participant_id ",
        "AND t2.user_id IS NULL ) ",
      "GROUP BY participant.id" );
    PREPARE statement FROM @sql;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;

  END //
DELIMITER ;

CALL patch_participant_last_site_appointment();
DROP PROCEDURE IF EXISTS patch_participant_last_site_appointment;
