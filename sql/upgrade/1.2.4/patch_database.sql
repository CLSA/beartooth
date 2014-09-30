-- Patch to upgrade database to version 1.2.4

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE activity.sql
SOURCE operation2.sql
SOURCE participant_last_home_appointment.sql
SOURCE participant_last_site_appointment.sql

SOURCE update_version_number.sql

COMMIT;
