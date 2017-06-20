-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE column_character_sets.sql
SOURCE overview.sql
SOURCE application_type_has_overview.sql
SOURCE role_has_overview.sql

SOURCE service.sql
SOURCE role_has_service.sql

SOURCE update_version_number.sql

COMMIT;
