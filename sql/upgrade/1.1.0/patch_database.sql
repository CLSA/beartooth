-- Patch to upgrade database to version 1.1.0

SET AUTOCOMMIT=0;

SOURCE queue.sql
SOURCE activity.sql
SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE operation2.sql

-- this must be last
SOURCE convert_database.sql

COMMIT;
