-- Patch to upgrade database to version 1.1.1

SET AUTOCOMMIT=0;

SOURCE role.sql
SOURCE assignment_note.sql

COMMIT;
