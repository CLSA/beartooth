<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $session = lib::create( 'business\session' );
    $is_interviewer = 'interviewer' == $this->db_role->name || 'interviewer+' == $this->db_role->name;

    // get whether this is a site or home qnaire
    $restriction_list = $this->get_restriction_list();
    $restriction = current( $restriction_list ); // there is always one and only one custom restriction
    $db_qnaire = lib::create( 'database\qnaire', $restriction['value'] );

    $modifier = lib::create( 'database\modifier' );
    $select = lib::create( 'database\select' );
    $select->from( $this->db_report->get_report_type()->subject );
    if( $this->db_role->all_sites )
      $select->add_table_column( 'site', 'IFNULL( site.name, "(none)" )', 'Site', false );
    $select->add_table_column(
      'participant',
      'CONCAT_WS( " ", honorific, participant.first_name, CONCAT( "(", other_name, ")" ), participant.last_name )',
      'Name',
      false );
    $select->add_table_column( 'participant', 'uid', 'UID' );
    $select->add_column( $this->get_datetime_column( 'appointment.datetime', 'date' ), 'Date', false );
    $select->add_column( $this->get_datetime_column( 'appointment.datetime', 'time' ), 'Time', false );
    $select->add_table_column( 'participant', 'TIMESTAMPDIFF( YEAR, date_of_birth, CURDATE() )', 'Age', false );

    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->where( 'qnaire.id', '=', $db_qnaire->id );

    if( 'home' == $db_qnaire->type )
    {
      $select->add_table_column(
        'address',
        'CONCAT_WS( " ", address.address1, address.address2, address.city, '.
                        'region.abbreviation, address.postcode )',
        'Address',
        false );

      if( !$is_interviewer )
      {
        $select->add_table_column(
          'user',
          'CONCAT_WS( " ", user.first_name, user.last_name )',
          'Interviewer',
          false );
      }

      $modifier->join( 'address', 'appointment.address_id', 'address.id' );
      $modifier->join( 'region', 'address.region_id', 'region.id' );
      $modifier->join( 'user', 'appointment.user_id', 'user.id' );
    }
    else // site qnaire
    {
      if( !$is_interviewer )
      {
        $select->add_table_column(
          'user',
          'CONCAT_WS( " ", user.first_name, user.last_name )',
          'Home Interviewer',
          false );
      }

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

    $this->apply_restrictions( $modifier );

    if( !$modifier->has_join( 'participant_site' ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
      $join_mod->where( 'participant_site.application_id', '=', $this->db_application->id );
      $modifier->join_modifier( 'participant_site', $join_mod );
    }
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
