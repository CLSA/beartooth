DROP PROCEDURE IF EXISTS patch_appointment;
DELIMITER //
CREATE PROCEDURE patch_appointment()
  BEGIN

    SELECT "Adding appointment_type_reason_id column to appointment table" AS "";

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "appointment"
    AND column_name = "appointment_type_reason_id";

    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN appointment_type_reason_id INT(10) UNSIGNED NULL DEFAULT NULL
      AFTER appointment_type_id;

      ALTER TABLE appointment
      ADD INDEX fk_appointment_type_reason_id (appointment_type_reason_id ASC);

      ALTER TABLE appointment
      ADD CONSTRAINT fk_appointment_appointment_type_reason_id
      FOREIGN KEY (appointment_type_reason_id)
      REFERENCES appointment_type_reason (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
    END IF;

    SELECT COUNT(*) INTO @test
    FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE()
    AND table_name = "appointment"
    AND column_name = "reason_extra";

    IF @test = 0 THEN
      ALTER TABLE appointment
      ADD COLUMN reason_extra VARCHAR(511) NULL DEFAULT NULL
      AFTER appointment_type_reason_id;
    END IF;

  END //
DELIMITER ;

CALL patch_appointment();
DROP PROCEDURE IF EXISTS patch_appointment;


DELIMITER $$

DROP TRIGGER IF EXISTS appointment_BEFORE_UPDATE$$
CREATE DEFINER=CURRENT_USER TRIGGER appointment_BEFORE_UPDATE BEFORE UPDATE ON appointment FOR EACH ROW
BEGIN
  IF (NEW.appointment_type_id IS NULL) THEN
    SET NEW.appointment_type_reason_id = NULL;
    SET NEW.reason_extra = NULL;
  ELSE
    IF (NEW.appointment_type_reason_id IS NULL) THEN
      SELECT id INTO @reason_id
      FROM appointment_type_reason
      WHERE appointment_type_id = NEW.appointment_type_id
      AND rank = 1;

      IF (@reason_id IS NOT NULL) THEN
        SET NEW.appointment_type_reason_id = @reason_id;
      END IF;
    END IF;

    SELECT extra INTO @extra
    FROM appointment_type_reason
    WHERE id = NEW.appointment_type_reason_id;

    IF (@extra IS NULL OR NOT @extra) THEN
      SET NEW.reason_extra = NULL;
    END IF;
  END IF;
END$$

DROP TRIGGER IF EXISTS appointment_BEFORE_INSERT$$
CREATE DEFINER=CURRENT_USER TRIGGER appointment_BEFORE_INSERT BEFORE INSERT ON appointment FOR EACH ROW
BEGIN
  IF (NEW.appointment_type_id IS NULL) THEN
    SET NEW.appointment_type_reason_id = NULL;
    SET NEW.reason_extra = NULL;
  ELSE
    IF (NEW.appointment_type_reason_id IS NULL) THEN
      SELECT id INTO @reason_id
      FROM appointment_type_reason
      WHERE appointment_type_id = NEW.appointment_type_id
      AND rank = 1;

      IF (@reason_id IS NOT NULL) THEN
        SET NEW.appointment_type_reason_id = @reason_id;
      END IF;
    END IF;

    SELECT extra INTO @extra
    FROM appointment_type_reason
    WHERE id = NEW.appointment_type_reason_id;

    IF (@extra IS NULL OR NOT @extra) THEN
      SET NEW.reason_extra = NULL;
    END IF;
  END IF;
END$$

DELIMITER ;
