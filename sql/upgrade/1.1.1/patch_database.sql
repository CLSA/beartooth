-- Patch to upgrade database to version 1.1.1

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role.sql
SOURCE role_has_operation.sql
SOURCE assignment_note.sql
SOURCE participant.sql
SOURCE queue.sql

COMMIT;
