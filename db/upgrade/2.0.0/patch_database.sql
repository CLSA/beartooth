-- Patch to upgrade database to version 2.0.0

SET AUTOCOMMIT=0;

SOURCE access.sql
SOURCE interview.sql
SOURCE participant_last_interview.sql
SOURCE update_participant_last_interview.sql
SOURCE interview_last_assignment.sql
SOURCE update_interview_last_assignment.sql
SOURCE assignment_last_phone_call.sql
SOURCE update_assignment_last_phone_call.sql
SOURCE participant_last_appointment.sql
SOURCE participant_last_home_appointment.sql
SOURCE participant_last_site_appointment.sql

SOURCE event_type.sql
SOURCE qnaire.sql
SOURCE queue_state.sql
SOURCE qnaire_has_quota.sql
SOURCE qnaire_has_site.sql
SOURCE qnaire_has_script.sql
SOURCE phase.sql
SOURCE queue_has_participant.sql
SOURCE assignment.sql
SOURCE queue.sql
SOURCE phone_call.sql
SOURCE activity.sql
SOURCE writelog.sql
SOURCE interview_phone_call_status_count.sql
SOURCE callback.sql
SOURCE appointment.sql
SOURCE appointment_type.sql
SOURCE service.sql
SOURCE role_has_operation.sql
SOURCE role_has_service.sql
SOURCE role_has_state.sql
SOURCE operation.sql
SOURCE setting_value.sql
SOURCE setting.sql
SOURCE system_message.sql
SOURCE user.sql
SOURCE recording.sql
SOURCE recording_file.sql
SOURCE site.sql
SOURCE consent.sql
SOURCE data_collection.sql
SOURCE next_of_kin.sql
SOURCE report_type.sql
SOURCE report_restriction.sql
SOURCE application_type_has_report_type.sql
SOURCE role_has_report_type.sql
SOURCE export.sql
SOURCE export_column.sql
SOURCE export_restriction.sql

SOURCE interview_last_appointment.sql
SOURCE update_interview_last_appointment.sql

SOURCE table_character_sets.sql
SOURCE column_character_sets.sql

SOURCE update_version_number.sql

COMMIT;
