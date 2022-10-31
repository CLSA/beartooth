<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\business\report;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Appointment report
 */
class appointment extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $session = lib::create( 'business\session' );
    $is_interviewer = 'interviewer' == $this->db_role->name || 'interviewer+' == $this->db_role->name;

    // get whether restricting by qnaire or site
    $db_site = NULL;
    $db_qnaire = NULL;
    foreach( $this->get_restriction_list() as $restriction )
    {
      if( 'qnaire' == $restriction['name'] ) $db_qnaire = lib::create( 'database\qnaire', $restriction['value'] );
      else if( 'site' == $restriction['name'] ) $db_site = lib::create( 'database\site', $restriction['value'] );
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    if( !is_null( $db_qnaire ) ) $modifier->where( 'qnaire.id', '=', $db_qnaire->id );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );

    $select = lib::create( 'database\select' );
    $select->from( $this->db_report->get_report_type()->subject );
    if( $this->db_role->all_sites ) $select->add_column( 'IFNULL( site.name, "(none)" )', 'Site', false );
    else $db_site = $this->db_site; // always restrict to the user's site if they don't have all-site access
    $select->add_column(
      'CONCAT_WS( " ", honorific, participant.first_name, CONCAT( "(", other_name, ")" ), participant.last_name )',
      'Name',
      false );
    $select->add_column( 'participant.uid', 'UID', false );
    $this->add_application_identifier_columns( $select, $modifier );
    if( is_null( $db_qnaire ) ) $select->add_column( 'qnaire.name', 'Questionnaire', false );
    $select->add_column( $this->get_datetime_column( 'appointment.datetime', 'date' ), 'Date', false );
    $select->add_column( $this->get_datetime_column( 'appointment.datetime', 'time' ), 'Time', false );
    $select->add_column( 'TIMESTAMPDIFF( YEAR, participant.date_of_birth, CURDATE() )', 'Age', false );
    $select->add_column( 'participant.sex', 'Sex', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column(
      'IFNULL( appointment.outcome, IF( UTC_TIMESTAMP() < appointment.datetime, "upcoming", "passed" ) )',
      'State',
      false
    );
    $select->add_column(
      'IFNULL( appointment_type.name, "normal" )',
      'Appointment Type',
      false
    );

    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'phone.participant_id', false );
    $join_mod->where( 'phone.rank', '=', 1 );
    $modifier->join_modifier( 'phone', $join_mod, 'left' );

    $select->add_column( 'phone.number', 'Phone', false );
    $select->add_column( 'IFNULL( participant.email, "(none)" )', 'Email', false );

    // make sure the participant has consented to participate
    $modifier->join( 'participant_last_consent', 'participant.id', 'participant_last_consent.participant_id' );
    $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
    $modifier->join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
    $modifier->where( 'consent_type.name', '=', 'participation' );
    $modifier->where( 'consent.accept', '=', true );

    if( is_null( $db_qnaire ) )
    {
      if( $is_interviewer )
      {
        throw lib::create( 'exception\notice',
          'Interviewer tried to generate appointment report without specifying a questionnaire.',
          __METHOD__
        );
      }
    }
    else
    {
      if( 'home' == $db_qnaire->type )
      {
        $select->add_column(
          'CONCAT_WS( " ", address.address1, address.address2, address.city, '.
                          'region.abbreviation, address.postcode )',
          'Address',
          false );

        if( !$is_interviewer )
        {
          $select->add_column(
            'CONCAT_WS( " ", user.first_name, user.last_name )',
            'Interviewer',
            false );
        }

        $modifier->join( 'address', 'appointment.address_id', 'address.id' );
        $modifier->join( 'region', 'address.region_id', 'region.id' );
        $modifier->join( 'user', 'appointment.user_id', 'user.id' );
      }
      else if( 'site' == $db_qnaire->type )
      {
        if( !$is_interviewer )
        {
          $select->add_column(
            'CONCAT_WS( " ", user.first_name, user.last_name )',
            'Home Interviewer',
            false );
        }

        $select->add_column( $this->get_datetime_column( 'event.datetime', 'date' ), 'Home Appointment Date', false );
        $select->add_column( 'DATEDIFF( appointment.datetime, event.datetime )', 'Days Since Home', false );

        $modifier->left_join( 'qnaire', 'qnaire.rank', 'prev_qnaire.rank + 1', 'prev_qnaire' );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where(
          'prev_qnaire.completed_event_type_id', '=', 'participant_last_event.event_type_id', false );
        $join_mod->where( 'participant.id', '=', 'participant_last_event.participant_id', false );
        $modifier->join_modifier( 'participant_last_event', $join_mod, 'left' );
        $modifier->left_join( 'event', 'participant_last_event.event_id', 'event.id' );
        $modifier->left_join( 'user', 'event.user_id', 'user.id' );
      }

      if( $is_interviewer ) $modifier->where( 'user.id', '=', $this->db_user->id );
    }

    $this->apply_restrictions( $modifier );

    if( !$modifier->has_join( 'appointment_type' ) )
      $modifier->left_join( 'appointment_type', 'appointment.appointment_type_id', 'appointment_type.id' );

    if( !$modifier->has_join( 'participant_site' ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
      $join_mod->where( 'participant_site.application_id', '=', $this->db_application->id );
      $modifier->join_modifier( 'participant_site', $join_mod );
    }
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );
    if( !is_null( $db_site ) ) $modifier->where( 'site.id', '=', $db_site->id );

    $modifier->order( 'appointment.datetime' );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
