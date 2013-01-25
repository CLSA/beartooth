-- Patch to upgrade database to version 1.1.0

SET AUTOCOMMIT=0;

SOURCE quota.sql
SOURCE queue.sql
SOURCE activity.sql

COMMIT;
