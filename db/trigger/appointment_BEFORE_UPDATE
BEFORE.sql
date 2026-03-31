CREATE TRIGGER appointment_BEFORE_UPDATE
BEFORE UPDATE ON appointment FOR EACH ROW
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