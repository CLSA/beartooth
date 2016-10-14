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
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    $select = lib::create( 'database\select' );
    $select->from( 'participant' );
    $select->add_column( 'uid', 'UID' );
    if( $this->db_role->all_sites )
      $select->add_table_column( 'site', 'IFNULL( site.name, "(none)" )', 'Site', false );
    $select->add_column( 'IF( participant.active, "Yes", "No" )', 'Active', false );
    $select->add_table_column( 'blood_consent', 'IF( blood_consent.accept, "Yes", "No" )', 'Blood', false );
    $select->add_table_column( 'state', 'IFNULL( state.name, "(none)" )', 'Condition', false );
    $select->add_table_column(
      'application_has_participant',
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
    $select->add_table_column( 'home_interview',
      sprintf( 'IF( home_interview.end_datetime IS NOT NULL, %s, %s )',
               $this->get_datetime_column( 'home_interview.end_datetime' ),
               $this->get_datetime_column( 'home_appointment.datetime' ) ),
      'Home Interview Date',
      false
    );
    $select->add_table_column( 'home_interview',
      'IF( home_interview.end_datetime IS NOT NULL, "(exported)", interviewer.name )',
      'Home Interviewer',
      false
    );
    $select->add_table_column( 'home_interview',
      'IF( home_interview.end_datetime IS NOT NULL, "Yes", "No" )',
      'Home Completed',
      false
    );
    $select->add_table_column( 'site_interview',
      sprintf( 'IF( site_interview.end_datetime IS NOT NULL, %s, %s )',
               $this->get_datetime_column( 'site_interview.end_datetime' ),
               $this->get_datetime_column( 'site_appointment.datetime' ) ),
      'Site Interview Date',
      false
    );

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
    $modifier->left_join( 'state', 'participant.state_id', 'state.id' );

    $modifier->inner_join( 'qnaire', NULL, 'home_qnaire' );
    $modifier->where( 'home_qnaire.type', '=', 'home' );
    $modifier->join(
      'event_type', 'home_qnaire.completed_event_type_id', 'home_event_type.id', '', 'home_event_type' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'home_interview.participant_id', false );
    $join_mod->where( 'home_qnaire.id', '=', 'home_interview.qnaire_id', false );
    $modifier->join_modifier( 'interview', $join_mod, 'left', 'home_interview' );
    $modifier->left_join(
      'interview_last_appointment',
      'home_interview.id',
      'home_interview_last_appointment.interview_id',
      'home_interview_last_appointment'
    );
    $modifier->left_join(
      'appointment',
      'home_interview_last_appointment.appointment_id',
      'home_appointment.id',
      'home_appointment'
    );
    $modifier->left_join( 'user', 'home_appointment.user_id', 'interviewer.id', 'interviewer' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'home_event.participant_id', false );
    $join_mod->where( 'home_event_type.id', '=', 'home_event.event_type_id', false );
    $modifier->join_modifier( 'event', $join_mod, 'left', 'home_event' );
    
    $modifier->inner_join( 'qnaire', NULL, 'site_qnaire' );
    $modifier->where( 'site_qnaire.type', '=', 'site' );
    $modifier->join(
      'event_type', 'site_qnaire.completed_event_type_id', 'site_event_type.id', '', 'site_event_type' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'site_interview.participant_id', false );
    $join_mod->where( 'site_qnaire.id', '=', 'site_interview.qnaire_id', false );
    $modifier->join_modifier( 'interview', $join_mod, 'left', 'site_interview' );
    $modifier->where( 'site_interview.end_datetime', '=', NULL ); // don't include completed site interviews
    $modifier->left_join(
      'interview_last_appointment',
      'site_interview.id',
      'site_interview_last_appointment.interview_id',
      'site_interview_last_appointment'
    );
    $modifier->left_join(
      'appointment',
      'site_interview_last_appointment.appointment_id',
      'site_appointment.id',
      'site_appointment'
    );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'site_event.participant_id', false );
    $join_mod->where( 'site_event_type.id', '=', 'site_event.event_type_id', false );
    $modifier->join_modifier( 'event', $join_mod, 'left', 'site_event' );

    // set up restrictions
    $this->apply_restrictions( $modifier );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
