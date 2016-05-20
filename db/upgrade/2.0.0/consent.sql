DROP PROCEDURE IF EXISTS patch_consent;
  DELIMITER //
  CREATE PROCEDURE patch_consent()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
      GROUP BY unique_constraint_schema );

    SELECT "Transferring data collection consent information to consent table" AS "";
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "data_collection" );
    IF @test = 1 THEN
      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".consent( ",
          "participant_id, consent_type_id, accept, written, datetime, note ) ",
        "SELECT data_collection.participant_id, consent_type.id, draw_blood, false, ",
               "IFNULL( event.datetime, NOW() ), ",
               "'Transferred from old data collection information. Note that the datetime is approximate.' ",
        "FROM ", @cenozo, ".consent_type, data_collection ",
        "LEFT JOIN ", @cenozo, ".event ON data_collection.participant_id = event.participant_id ",
        "LEFT JOIN ", @cenozo, ".event_type ON event.event_type_id = event_type.id ",
        "WHERE data_collection.draw_blood IS NOT NULL ",
        "AND event_type.name = 'completed (Baseline Home)' ",
        "AND consent_type.name = 'draw blood'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".consent( ",
          "participant_id, consent_type_id, accept, written, datetime, note ) ",
        "SELECT data_collection.participant_id, consent_type.id, take_urine, false, ",
               "IFNULL( event.datetime, NOW() ), ",
               "'Transferred from old data collection information. Note that the datetime is approximate.' ",
        "FROM ", @cenozo, ".consent_type, data_collection ",
        "LEFT JOIN ", @cenozo, ".event ON data_collection.participant_id = event.participant_id ",
        "LEFT JOIN ", @cenozo, ".event_type ON event.event_type_id = event_type.id ",
        "WHERE data_collection.take_urine IS NOT NULL ",
        "AND event_type.name = 'completed (Baseline Home)' ",
        "AND consent_type.name = 'take urine'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO ", @cenozo, ".consent( ",
          "participant_id, consent_type_id, accept, written, datetime, note ) ",
        "SELECT data_collection.participant_id, consent_type.id, draw_blood_continue, false, ",
               "IFNULL( event.datetime, NOW() ), ",
               "'Transferred from old data collection information. Note that the datetime is approximate.' ",
        "FROM ", @cenozo, ".consent_type, data_collection ",
        "LEFT JOIN ", @cenozo, ".event ON data_collection.participant_id = event.participant_id ",
        "LEFT JOIN ", @cenozo, ".event_type ON event.event_type_id = event_type.id ",
        "WHERE data_collection.draw_blood_continue IS NOT NULL ",
        "AND event_type.name = 'completed (Baseline Home)' ",
        "AND consent_type.name = 'continue draw blood'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(

        "INSERT IGNORE INTO ", @cenozo, ".consent( ",
          "participant_id, consent_type_id, accept, written, datetime, note ) ",
        "SELECT data_collection.participant_id, consent_type.id, physical_tests_continue, false, ",
               "IFNULL( event.datetime, NOW() ), ",
               "'Transferred from old data collection information. Note that the datetime is approximate.' ",
        "FROM ", @cenozo, ".consent_type, data_collection ",
        "LEFT JOIN ", @cenozo, ".event ON data_collection.participant_id = event.participant_id ",
        "LEFT JOIN ", @cenozo, ".event_type ON event.event_type_id = event_type.id ",
        "WHERE data_collection.physical_tests_continue IS NOT NULL ",
        "AND event_type.name = 'completed (Baseline Home)' ",
        "AND consent_type.name = 'continue physical tests'" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_consent();
DROP PROCEDURE IF EXISTS patch_consent;
