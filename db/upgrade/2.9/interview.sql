DROP PROCEDURE IF EXISTS patch_interview;
DELIMITER //
CREATE PROCEDURE patch_interview()
  BEGIN

    SELECT "Adding interviewing_instance_id column to interview table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "interview"
    AND column_name = "interviewing_instance_id";

    IF @test = 0 THEN
      ALTER TABLE interview ADD COLUMN interviewing_instance_id INT(10) UNSIGNED NULL DEFAULT NULL AFTER site_id;

      ALTER TABLE interview
      ADD KEY fk_interviewing_instance_id (interviewing_instance_id),
      ADD CONSTRAINT fk_interview_interviewing_instance_id
        FOREIGN KEY (interviewing_instance_id)
        REFERENCES interviewing_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION;

    END IF;

  END //
DELIMITER ;

CALL patch_interview();
DROP PROCEDURE IF EXISTS patch_interview;
