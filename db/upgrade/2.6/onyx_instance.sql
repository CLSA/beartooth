DROP PROCEDURE IF EXISTS patch_onyx_instance;
DELIMITER //
CREATE PROCEDURE patch_onyx_instance()
  BEGIN

    -- determine the cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_access_site_id"
    );

    SELECT "Renaming onyx_instance table to interviewing_instance" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = "onyx_instance";

    IF @test = 1 THEN
      RENAME TABLE onyx_instance TO interviewing_instance;
    END IF;

    SELECT "Adding new type column to interviewing_instance" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interviewing_instance"
    AND column_name = "type";

    IF @test = 0 THEN
      ALTER TABLE interviewing_instance
      ADD COLUMN type ENUM('onyx', 'pine') NOT NULL;
      UPDATE intervieweing_instance SET type = 'onyx';
    END IF;

    SELECT "Renaming fk_onyx_instance_interviewer_user_id constraint" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE constraint_schema = DATABASE()
    AND table_name = "interviewing_instance"
    AND constraint_name = "fk_onyx_instance_interviewer_user_id";

    IF @test = 1 THEN
      ALTER TABLE interviewing_instance DROP CONSTRAINT fk_onyx_instance_interviewer_user_id;
      SET @sql = CONCAT(
        "ALTER TABLE interviewing_instance ",
        "ADD CONSTRAINT fk_interviewing_instance_interviewer_user_id ",
        "FOREIGN KEY (interviewer_user_id) ",
        "REFERENCES ", @cenozo, ".user (id) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Renaming fk_onyx_instance_site_id constraint" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE constraint_schema = DATABASE()
    AND table_name = "interviewing_instance"
    AND constraint_name = "fk_onyx_instance_site_id";

    IF @test = 1 THEN
      ALTER TABLE interviewing_instance DROP CONSTRAINT fk_onyx_instance_site_id;
      SET @sql = CONCAT(
        "ALTER TABLE interviewing_instance ",
        "ADD CONSTRAINT fk_interviewing_instance_site_id ",
        "FOREIGN KEY (site_id) ",
        "REFERENCES ", @cenozo, ".site (id) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

    SELECT "Renaming fk_onyx_instance_user_id constraint" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE constraint_schema = DATABASE()
    AND table_name = "interviewing_instance"
    AND constraint_name = "fk_onyx_instance_user_id";

    IF @test = 1 THEN
      ALTER TABLE interviewing_instance DROP CONSTRAINT fk_onyx_instance_user_id;
      SET @sql = CONCAT(
        "ALTER TABLE interviewing_instance ",
        "ADD CONSTRAINT fk_interviewing_instance_user_id ",
        "FOREIGN KEY (user_id) ",
        "REFERENCES ", @cenozo, ".user (id) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION"
      );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

CALL patch_onyx_instance();
DROP PROCEDURE IF EXISTS patch_onyx_instance;
