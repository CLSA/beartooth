-- Patch to upgrade database to version 2.10

SET AUTOCOMMIT=0;

SOURCE appointment_type.sql
SOURCE appointment_type_reason.sql
SOURCE appointment.sql
SOURCE role_has_overview.sql

SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
