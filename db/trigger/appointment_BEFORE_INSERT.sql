CREATE TRIGGER appointment_BEFORE_INSERT BEFORE INSERT ON appointment FOR EACH ROW
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
END ;;
