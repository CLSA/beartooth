-- Patch to upgrade database to version 1.2.5

SET AUTOCOMMIT=0;

SOURCE activity.sql
SOURCE role_has_operation.sql
SOURCE operation2.sql
SOURCE update_version_number.sql

COMMIT;
