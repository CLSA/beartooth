-- Patch to upgrade database to version 2.9

SET AUTOCOMMIT=0;

SOURCE qnaire_has_event_type.sql
SOURCE qnaire_has_study.sql

SOURCE update_version_number.sql

COMMIT;
