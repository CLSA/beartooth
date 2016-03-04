DROP PROCEDURE IF EXISTS patch_setting;
  DELIMITER //
  CREATE PROCEDURE patch_setting()
  BEGIN

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id" );

    SELECT "Replacing existing setting table with new design" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "old_setting_value" );
    IF @test = 1 THEN

      DROP TABLE setting;

      SET @sql = CONCAT(
        "CREATE TABLE setting ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "survey_without_sip TINYINT(1) NOT NULL DEFAULT 0, ",
          "calling_start_time TIME NOT NULL DEFAULT '9:00', ",
          "calling_end_time TIME NOT NULL DEFAULT '21:00', ",
          "appointment_update_span INT UNSIGNED NOT NULL DEFAULT 30, ",
          "appointment_home_duration INT UNSIGNED NOT NULL DEFAULT 120, ",
          "appointment_site_duration INT UNSIGNED NOT NULL DEFAULT 120, ",
          "pre_call_window INT UNSIGNED NOT NULL DEFAULT 5, ",
          "contacted_wait INT UNSIGNED NOT NULL DEFAULT 10080, ",
          "busy_wait INT UNSIGNED NOT NULL DEFAULT 15, ",
          "fax_wait INT UNSIGNED NOT NULL DEFAULT 15, ",
          "no_answer_wait INT UNSIGNED NOT NULL DEFAULT 1440, ",
          "not_reached_wait INT UNSIGNED NOT NULL DEFAULT 4320, ",
          "hang_up_wait INT UNSIGNED NOT NULL DEFAULT 2880, ",
          "soft_refusal_wait INT UNSIGNED NOT NULL DEFAULT 525600, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_site_id (site_id ASC), ",
          "UNIQUE INDEX uq_site_id (site_id ASC), ",
          "CONSTRAINT fk_setting_site_id ",
            "FOREIGN KEY (site_id) ",
            "REFERENCES ", @cenozo, ".site (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION ) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      -- insert data from temporary "old_setting_value" table
      INSERT INTO setting( site_id ) SELECT DISTINCT site_id FROM old_setting_value ORDER BY site_id;

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET survey_without_sip = "true" = old_setting_value.value
      WHERE old_setting_value.category = "voip"
      AND old_setting_value.name = "survey without sip";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET calling_start_time = value
      WHERE old_setting_value.category = "calling"
      AND old_setting_value.name = "start time";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET calling_end_time = value
      WHERE old_setting_value.category = "calling"
      AND old_setting_value.name = "end time";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET appointment_update_span = value
      WHERE old_setting_value.category = "appointment"
      AND old_setting_value.name = "update span";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET appointment_home_duration = value
      WHERE old_setting_value.category = "appointment"
      AND old_setting_value.name = "home duration";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET appointment_site_duration = value
      WHERE old_setting_value.category = "appointment"
      AND old_setting_value.name = "site duration";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET pre_call_window = value
      WHERE old_setting_value.category = "callback"
      AND old_setting_value.name = "call pre-window";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET contacted_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "contacted";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET busy_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "busy";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET fax_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "fax";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET no_answer_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "no answer";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET not_reached_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "not reached";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET hang_up_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "hang up";

      UPDATE setting JOIN old_setting_value USING( site_id )
      SET soft_refusal_wait = value
      WHERE old_setting_value.category = "callback timing"
      AND old_setting_value.name = "soft refusal";

      DROP TABLE old_setting_value;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_setting();
DROP PROCEDURE IF EXISTS patch_setting;