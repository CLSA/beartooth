-- Patch to upgrade database to version 2.5

SET AUTOCOMMIT=0;

SOURCE qnaire_has_collection.sql
SOURCE qnaire_has_hold_type.sql
SOURCE queue.sql

SOURCE update_version_number.sql

COMMIT;
