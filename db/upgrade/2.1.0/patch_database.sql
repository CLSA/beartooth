-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE column_character_sets.sql

SOURCE update_version_number.sql

COMMIT;
