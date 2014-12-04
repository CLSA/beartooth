-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;


SOURCE appointment.sql
SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE participant_last_appointment.sql
SOURCE participant_last_home_appointment.sql
SOURCE participant_last_site_appointment.sql
SOURCE interview_last_appointment.sql

SOURCE update_version_number.sql

COMMIT;
