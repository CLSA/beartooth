CREATE TRIGGER interview_AFTER_UPDATE
AFTER UPDATE ON beartooth.interview
FOR EACH ROW
BEGIN
  CALL update_participant_last_interview( NEW.participant_id );
  CALL update_interview_last_assignment( NEW.id );
END$$