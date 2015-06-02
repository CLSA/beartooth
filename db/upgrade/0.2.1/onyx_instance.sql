-- remove the uq_site_id_interviewer_id key from the onyx_instance table
-- we need to create a procedure which only alters the onyx_instance table if the
-- unique key exists
DROP PROCEDURE IF EXISTS patch_onyx_instance;
DELIMITER //
CREATE PROCEDURE patch_onyx_instance()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "onyx_instance"
      AND CONSTRAINT_NAME = "uq_site_id_interviewer_id" );
    IF @test = 1 THEN
      ALTER TABLE onyx_instance
      DROP KEY uq_site_id_interviewer_id;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_onyx_instance();
DROP PROCEDURE IF EXISTS patch_onyx_instance;
