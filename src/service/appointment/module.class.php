<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\appointment;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\base_calendar_module
{
  /**
   * Contructor
   */
  public function __construct( $index, $service )
  {
    parent::__construct( $index, $service );
    $this->lower_date = array( 'null' => false, 'column' => 'DATE( datetime )' );
    $this->upper_date = array( 'null' => false, 'column' => 'DATE( datetime )' );
  }

  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    $service_class_name = lib::get_class_name( 'service\service' );
    $db_appointment = $this->get_resource();
    $db_interview = is_null( $db_appointment ) ? $this->get_parent_resource() : $db_appointment->get_interview();
    $method = $this->get_method();

    $db_application = lib::create( 'business\session' )->get_application();

    // make sure the application has access to the participant
    if( !is_null( $db_appointment ) )
    {
      $db_participant = $db_interview->get_participant();
      if( $db_application->release_based )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'participant_id', '=', $db_participant->id );
        if( 0 == $db_application->get_participant_count( $modifier ) ) $this->get_status()->set_code( 404 );
      }

      // restrict by site
      $db_restrict_site = $this->get_restricted_site();
      if( !is_null( $db_restrict_site ) )
      {
        $db_effective_site = $db_participant->get_effective_site();
        if( is_null( $db_effective_site ) || $db_restrict_site->id != $db_effective_site->id )
          $this->get_status()->set_code( 403 );
      }

      // we don't restrict by role here since tier-1 roles need to see other people's appointments
    }

    if( $service_class_name::is_write_method( $method ) )
    {
      // no writing of appointments if interview is completed
      if( !is_null( $db_interview ) && null !== $db_interview->end_datetime )
      {
        $this->set_data( 'Appointments cannot be changed after an interview is complete.' );
        $this->get_status()->set_code( 406 );
      }
      // no writing of appointments if it has passed
      else if( !is_null( $db_appointment ) && $db_appointment->datetime < util::get_datetime_object() )
      {
        $this->set_data( 'Appointments cannot be changed after they have passed.' );
        $this->get_status()->set_code( 406 );
      }
      else
      {
        // validate if we are changing the datetime
        if( 'POST' == $method ||
            ( 'PATCH' == $method && array_key_exists( 'datetime', $this->get_file_as_array() ) ) )
        {
          if( !$db_appointment->validate_date() )
          {
            $this->set_data( 'An appointment cannot currently be made for this participant.' );
            $this->get_status()->set_code( 406 );
          }
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );

    // include the user first/last/name as supplemental data
    $modifier->left_join( 'user', 'appointment.user_id', 'user.id' );
    $select->add_column(
      'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
      'formatted_user_id',
      false );

    // include the participant uid and interviewer name as supplemental data
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->left_join( 'user', 'appointment.user_id', 'user.id' );
    $select->add_table_column( 'participant', 'uid' );
    $select->add_table_column( 'user', 'name', 'username' );

    $participant_site_join_mod = lib::create( 'database\modifier' );
    $participant_site_join_mod->where(
      'interview.participant_id', '=', 'participant_site.participant_id', false );
    $participant_site_join_mod->where(
      'participant_site.application_id', '=', $session->get_application()->id );
    $modifier->join_modifier( 'participant_site', $participant_site_join_mod, 'left' );

    // add the address "summary" column if needed
    if( $select->has_column( 'address_summary' ) )
    {
      $modifier->left_join( 'address', 'appointment.address_id', 'address.id' );
      $modifier->left_join( 'region', 'address.region_id', 'region.id' );
      $select->add_column( 'CONCAT_WS( ", ", address1, address2, city, region.name )', 'address_summary', false );
    }

    // restrict by site
    $db_restricted_site = $this->get_restricted_site();
    if( !is_null( $db_restricted_site ) )
      $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

    // restrict by user
    if( 1 == $session->get_role()->tier )
    {
      $db_user = $session->get_user();
      $modifier->where( sprintf( 'IFNULL( appointment.user_id, %s )', $db_user->id ), '=', $db_user->id );
    }

    if( $select->has_table_columns( 'appointment_type' ) )
      $modifier->left_join( 'appointment_type', 'appointment.appointment_type_id', 'appointment_type.id' );

    if( $select->has_column( 'state' ) )
    {
      $modifier->left_join( 'setting', 'participant_site.site_id', 'setting.site_id' );

      // specialized sql used to determine the appointment's current state
      $sql =
        'IF( appointment.completed, '.
          '"completed", '.
          'IF( UTC_TIMESTAMP() < appointment.datetime, "upcoming", "passed" ) '.
        ')';

      $select->add_column( $sql, 'state', false );
    }

    // restrict by type
    $type = $this->get_argument( 'type', NULL );
    if( !is_null( $type ) ) $modifier->where( 'qnaire.type', '=', $type );
  }
}
