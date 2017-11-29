<?php
/**
 * sample.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\business\report;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Sample report
 */
class sample extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $db = lib::create( 'business\session' )->get_database();
    $participant_class_name = lib::get_class_name( 'database\participant' );

    // need to create temporary tables to make report more efficient
    $interview_assignment_total_sel = lib::create( 'database\select' );
    $interview_assignment_total_sel->from( 'interview' );
    $interview_assignment_total_sel->add_column( 'id' );
    $interview_assignment_total_sel->add_column( 'IF( assignment.id IS NULL, 0, COUNT(*) )', 'total', false );
    $interview_assignment_total_mod = lib::create( 'database\modifier' );
    $interview_assignment_total_mod->left_join( 'assignment', 'interview.id', 'assignment.interview_id' );
    $interview_assignment_total_mod->group( 'interview.id' );
    $db->execute( sprintf(
      "CREATE TEMPORARY TABLE interview_assignment_total\n%s %s",
      $interview_assignment_total_sel->get_sql(),
      $interview_assignment_total_mod->get_sql()
    ) );
    $db->execute( 'ALTER TABLE interview_assignment_total ADD INDEX dk_id( id )' );

    $home_data_sel = lib::create( 'database\select' );
    $home_data_sel->from( 'qnaire' );
    $home_data_sel->add_table_column( 'interview', 'id', 'interview_id' );
    $home_data_sel->add_table_column( 'interview', 'participant_id' );
    $home_data_sel->add_table_column( 'interview', 'end_datetime', 'interview_datetime' );
    $home_data_sel->add_table_column( 'appointment', 'datetime', 'appointment_datetime' );
    $home_data_sel->add_table_column( 'qnaire', 'completed_event_type_id', 'event_type_id' );
    $home_data_sel->add_table_column( 'interview_assignment_total', 'total', 'assignment_count' );
    $home_data_sel->add_table_column( 'user', 'name', 'interviewer' );
    $home_data_mod = lib::create( 'database\modifier' );
    $home_data_mod->join( 'interview', 'qnaire.id', 'interview.qnaire_id' );
    $home_data_mod->join( 'interview_assignment_total', 'interview.id', 'interview_assignment_total.id' );
    $home_data_mod->left_join(
      'interview_last_appointment', 'interview.id', 'interview_last_appointment.interview_id' );
    $home_data_mod->left_join( 'appointment', 'interview_last_appointment.appointment_id', 'appointment.id' );
    $home_data_mod->left_join( 'user', 'appointment.user_id', 'user.id' );
    $home_data_mod->where( 'qnaire.type', '=', 'home' );
    $db->execute( sprintf(
      "CREATE TEMPORARY TABLE home_data\n%s %s",
      $home_data_sel->get_sql(),
      $home_data_mod->get_sql()
    ) );
    $db->execute(
      "ALTER TABLE home_data\n".
      "ADD INDEX dk_interview_id( interview_id ),\n".
      "ADD INDEX dk_participant_id( participant_id ),\n".
      "ADD INDEX dk_event_type_id( event_type_id )"
    );

    $site_data_sel = lib::create( 'database\select' );
    $site_data_sel->from( 'qnaire' );
    $site_data_sel->add_table_column( 'interview', 'id', 'interview_id' );
    $site_data_sel->add_table_column( 'interview', 'participant_id' );
    $site_data_sel->add_table_column( 'interview', 'end_datetime', 'interview_datetime' );
    $site_data_sel->add_table_column( 'appointment', 'datetime', 'appointment_datetime' );
    $site_data_sel->add_table_column( 'qnaire', 'completed_event_type_id', 'event_type_id' );
    $site_data_sel->add_table_column( 'interview_assignment_total', 'total', 'assignment_count' );
    $site_data_sel->add_table_column( 'user', 'name', 'interviewer' );
    $site_data_mod = lib::create( 'database\modifier' );
    $site_data_mod->join( 'interview', 'qnaire.id', 'interview.qnaire_id' );
    $site_data_mod->join( 'interview_assignment_total', 'interview.id', 'interview_assignment_total.id' );
    $site_data_mod->left_join(
      'interview_last_appointment', 'interview.id', 'interview_last_appointment.interview_id' );
    $site_data_mod->left_join( 'appointment', 'interview_last_appointment.appointment_id', 'appointment.id' );
    $site_data_mod->left_join( 'user', 'appointment.user_id', 'user.id' );
    $site_data_mod->where( 'qnaire.type', '=', 'site' );
    $db->execute( sprintf(
      "CREATE TEMPORARY TABLE site_data\n%s %s",
      $site_data_sel->get_sql(),
      $site_data_mod->get_sql()
    ) );
    $db->execute(
      "ALTER TABLE site_data\n".
      "ADD INDEX dk_interview_id( interview_id ),\n".
      "ADD INDEX dk_participant_id( participant_id ),\n".
      "ADD INDEX dk_event_type_id( event_type_id )"
    );

    $select = lib::create( 'database\select' );
    $select->from( 'participant' );
    $select->add_column( 'uid', 'UID' );
    if( $this->db_role->all_sites )
      $select->add_column( 'IFNULL( site.name, "(none)" )', 'Site', false );
    $select->add_column( 'IF( participant.active, "Yes", "No" )', 'Active', false );
    $select->add_column( 'IF( blood_consent.accept, "Yes", "No" )', 'Blood', false );
    $select->add_column(
      'IF( hold_type.type IS NULL, "(none)", CONCAT( hold_type.type, ": ", hold_type.name ) )',
      'Condition',
      false
    );
    $select->add_column( 'IFNULL( language.name, "(none)" )', 'Language', false );
    $select->add_column(
      $this->get_datetime_column( 'application_has_participant.datetime' ),
      'Released',
      false
    );
    $select->add_column( 'IF( participant.email IS NOT NULL, "Yes", "No" )', 'Has Email', false );
    $select->add_column(
      $this->get_datetime_column( 'participant.callback' ),
      'Callback',
      false
    );
    $select->add_column(
      sprintf( 'IF( home_data.interview_datetime IS NOT NULL, %s, %s )',
               $this->get_datetime_column( 'home_data.interview_datetime' ),
               $this->get_datetime_column( 'home_data.appointment_datetime' ) ),
      'Home Interview Date',
      false
    );
    $select->add_column( 'home_data.assignment_count', 'Home Assignments', false );
    $select->add_column(
      'IF( home_data.interview_datetime IS NOT NULL, "(exported)", home_data.interviewer )',
      'Home Interviewer',
      false
    );
    $select->add_column(
      'IF( home_data.interview_datetime IS NOT NULL, "Yes", "No" )',
      'Home Completed',
      false
    );
    $select->add_column( 'site_data.assignment_count', 'Site Assignments', false );
    $select->add_column(
      sprintf( 'IF( site_data.interview_datetime IS NOT NULL, %s, %s )',
               $this->get_datetime_column( 'site_data.interview_datetime' ),
               $this->get_datetime_column( 'site_data.appointment_datetime' ) ),
      'Site Interview Date',
      false
    );
    $select->add_column( 'global_note', 'Special Note' );

    $modifier = lib::create( 'database\modifier' );

    // do not include withdrawn participants
    $modifier->join( 'participant_last_consent', 'participant.id', 'participant_last_consent.participant_id' );
    $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
    $modifier->where( 'consent_type.name', '=', 'participation' );
    $modifier->left_join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
    $modifier->where( 'IFNULL( consent.accept, true )', '=', true );

    $modifier->join(
      'participant_last_consent',
      'participant.id',
      'participant_last_blood_consent.participant_id',
      '',
      'participant_last_blood_consent'
    );
    $modifier->join(
      'consent_type',
      'participant_last_blood_consent.consent_type_id',
      'blood_consent_type.id',
      '',
      'blood_consent_type'
    );
    $modifier->where( 'blood_consent_type.name', '=', 'draw blood' );
    $modifier->left_join( 'consent', 'participant_last_consent.consent_id', 'blood_consent.id', 'blood_consent' );
    $modifier->join( 'participant_last_hold', 'participant.id', 'participant_last_hold.participant_id' );
    $modifier->join( 'hold', 'particiapnt_last_hold.hold_id', 'hold.id' );
    $modifier->left_join( 'hold_type', 'hold.hold_type_id', 'hold_type.id' );
    $modifier->join( 'language', 'participant.language_id', 'language.id' );

    $modifier->left_join( 'home_data', 'participant.id', 'home_data.participant_id' );
    $modifier->left_join( 'site_data', 'participant.id', 'site_data.participant_id' );
    $modifier->where( 'site_data.interview_datetime', '=', NULL );

    // set up restrictions
    $this->apply_restrictions( $modifier );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
