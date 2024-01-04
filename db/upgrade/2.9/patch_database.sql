-- Patch to upgrade database to version 2.9

SET AUTOCOMMIT=0;

SOURCE custom_report.sql
SOURCE role_has_custom_report.sql
SOURCE interview.sql;
SOURCE qnaire_has_event_type.sql
SOURCE qnaire_has_study.sql
SOURCE application_type_has_role.sql
SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
