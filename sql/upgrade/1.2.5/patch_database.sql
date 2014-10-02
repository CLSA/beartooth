-- Patch to upgrade database to version 1.2.5

SET AUTOCOMMIT=0;

SOURCE update_version_number.sql

COMMIT;
