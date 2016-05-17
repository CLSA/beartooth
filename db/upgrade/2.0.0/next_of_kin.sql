DROP PROCEDURE IF EXISTS patch_next_of_kin;
DELIMITER //
CREATE PROCEDURE patch_next_of_kin()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Moving next_of_kin data to cenozo and dropping table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "next_of_kin" );
    IF @test = 1 THEN
      SET @sql = CONCAT( "INSERT IGNORE INTO ", @cenozo, ".next_of_kin SELECT * FROM next_of_kin" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      DROP TABLE next_of_kin;
    END IF;

  END //
DELIMITER ;

CALL patch_next_of_kin();
DROP PROCEDURE IF EXISTS patch_next_of_kin;
