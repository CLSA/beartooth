-- Patch to upgrade database to version 1.2.2

SET AUTOCOMMIT=0;

SOURCE operation.sql
SOURCE role_has_operation.sql
SOURCE jurisdiction.sql
SOURCE qnaire.sql
SOURCE service.sql
SOURCE qnaire_has_quota.sql
SOURCE quota_state.sql
SOURCE setting.sql

SOURCE update_version_number.sql

COMMIT;
