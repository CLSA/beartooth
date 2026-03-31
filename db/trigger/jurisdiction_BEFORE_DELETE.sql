CREATE TRIGGER jurisdiction_BEFORE_DELETE
BEFORE DELETE ON beartooth.jurisdiction
FOR EACH ROW
BEGIN
  DELETE FROM participant_site
  WHERE site_id = OLD.site_id;
END$$