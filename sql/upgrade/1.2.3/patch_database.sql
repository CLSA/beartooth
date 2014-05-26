-- Patch to upgrade database to version 1.2.3

SET AUTOCOMMIT=0;

SOURCE language.sql
SOURCE user_has_language.sql
SOURCE user.sql
SOURCE participant.sql
SOURCE service.sql
SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE operation2.sql

SOURCE update_version_number.sql

COMMIT;
