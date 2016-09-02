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
    $db_user = lib::create( 'business\session' )->get_user();
    $date_string = sprintf( 'DATE( CONVERT_TZ( datetime, "UTC", "%s" ) )', $db_user->timezone );
    $this->lower_date = array( 'null' => false, 'column' => $date_string );
    $this->upper_date = array( 'null' => false, 'column' => $date_string );
  }

  /**
   * Extend parent method
   */
  protected function get_argument( $name, $default = NULL )
  {
    $session = lib::create( 'business\session' );
    $db_site = $session->get_site();
    $db_role = $session->get_role();

    // return specific values for min_date and max_date for the onyx role
    if( 'min_date' == $name && 'onyx' == $db_role->name )
    {
      return util::get_datetime_object()->format( 'Y-m-d' );
    }
    else if( 'max_date' == $name && 'onyx' == $db_role->name )
    {
      $db_setting = $db_site->get_setting();
      $date_obj = util::get_datetime_object();
      $date_obj->add( new \DateInterval( sprintf( 'P%dD', $db_setting->appointment_update_span ) ) );
      return $date_obj->format( 'Y-m-d' );
    }

    return parent::get_argument( $name, $default );
  }

  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
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
          if( 0 == $db_application->get_participant_count( $modifier ) )
          {
            $this->get_status()->set_code( 404 );
            return;
          }
        }

        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          $db_effective_site = $db_participant->get_effective_site();
          if( is_null( $db_effective_site ) || $db_restrict_site->id != $db_effective_site->id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }

        // we don't restrict by role here since tier-1 roles need to see other people's appointments
      }

      if( $service_class_name::is_write_method( $method ) )
      {
        // no writing of appointments if interview is completed
        if( !is_null( $db_interview ) && null !== $db_interview->end_datetime )
        {
          $this->set_data( 'Appointments cannot be changed after an interview is complete.' );
          $this->get_status()->set_code( 306 );
        }
        // no writing of appointments if they have passed
        else if( !is_null( $db_appointment ) && $db_appointment->datetime < util::get_datetime_object() )
        {
          $this->set_data( 'Appointments cannot be changed after they have passed.' );
          $this->get_status()->set_code( 306 );
        }
        else
        {
          // make sure mandatory scripts have been submitted before allowing a new appointment
          if( 'POST' == $method && !$db_appointment->are_scripts_complete() )
          {
            $this->set_data(
              'An appointment cannot be made for this participant until '.
              'all mandatory scripts have been submitted.' );
            $this->get_status()->set_code( 306 );
          }
          // validate if we are changing the datetime
          if( 'POST' == $method ||
              ( 'PATCH' == $method && array_key_exists( 'datetime', $this->get_file_as_array() ) ) )
          {
            if( !$db_appointment->validate_date() )
            {
              $this->set_data( 'An appointment cannot currently be made for this participant.' );
              $this->get_status()->set_code( 306 );
            }
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
    $db_application = $session->get_application();
    $db_user = $session->get_user();
    $db_role = $session->get_role();

    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $participant_site_join_mod = lib::create( 'database\modifier' );
    $participant_site_join_mod->where(
      'interview.participant_id', '=', 'participant_site.participant_id', false );
    $participant_site_join_mod->where(
      'participant_site.application_id', '=', $db_application->id );
    $modifier->join_modifier( 'participant_site', $participant_site_join_mod, 'left' );

    // onyx roles need to be treated specially
    if( 'onyx' == $db_role->name )
    {
      $onyx_instance_class_name = lib::create( 'database\onyx_instance' );
      $appointment_type_class_name = lib::create( 'database\appointment_type' );

      // add specific columns
      $select->remove_column();
      $select->add_table_column( 'participant', 'uid' );
      $select->add_table_column( 'participant', 'honorific' );
      $select->add_table_column( 'participant', 'first_name' );
      $select->add_column( 'IFNULL( participant.other_name, "" )', 'otherName', false );
      $select->add_table_column( 'participant', 'last_name' );
      $select->add_column( 'IFNULL( participant.date_of_birth, "" )', 'dob', false );
      $select->add_table_column( 'participant', 'sex', 'gender' );
      $select->add_column( 'datetime' );
      $select->add_table_column( 'address', 'address1' );
      $select->add_table_column( 'address', 'city' );
      $select->add_table_column( 'region', 'name', 'region' );
      $select->add_table_column( 'address', 'postcode' );
      $select->add_table_column( 'participant', 'email' );

      $modifier->join(
        'participant_primary_address', 'participant.id', 'participant_primary_address.participant_id' );
      $modifier->left_join( 'address', 'participant_primary_address.address_id', 'address.id' );
      $modifier->left_join( 'region', 'address.region_id', 'region.id' );

      // restrict by onyx instance
      $db_onyx_instance = $onyx_instance_class_name::get_unique_record( 'user_id', $db_user->id );
      if( is_null( $db_onyx_instance ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Tried to get appointment list for onyx user "%s" that has no onyx instance record.',
                   $db_user->name ),
          __METHOD__ );

      $db_interviewer_user = $db_onyx_instance->get_interviewer_user();
      if( is_null( $db_interviewer_user ) )
      {
        $modifier->where( 'appointment.user_id', '=', NULL );
        $select->add_column(
          'IF( IFNULL( blood_consent.accept, true ), "Yes", "No" )',
          'consentToDrawBlood',
          false );
        $select->add_column(
          'IF( IFNULL( urine_consent.accept, true ), "Yes", "No" )',
          'consentToTakeUrine',
          false );

        $modifier->join(
          'participant_last_consent',
          'participant.id',
          'participant_last_blood_consent.participant_id',
          '', // regular join type
          'participant_last_blood_consent' );
        $modifier->join(
          'consent_type',
          'participant_last_blood_consent.consent_type_id',
          'blood_consent_type.id',
          '', // regular join type
          'blood_consent_type' );
        $modifier->left_join(
          'consent',
          'participant_last_blood_consent.consent_id',
          'blood_consent.id',
          'blood_consent' );
        $modifier->where( 'blood_consent_type.name', '=', 'draw blood' );

        $modifier->join(
          'participant_last_consent',
          'participant.id',
          'participant_last_urine_consent.participant_id',
          '', // regular join type
          'participant_last_urine_consent' );
        $modifier->join(
          'consent_type',
          'participant_last_urine_consent.consent_type_id',
          'urine_consent_type.id',
          '', // regular join type
          'urine_consent_type' );
        $modifier->left_join(
          'consent',
          'participant_last_urine_consent.consent_id',
          'urine_consent.id',
          'urine_consent' );
        $modifier->where( 'urine_consent_type.name', '=', 'take urine' );
      }
      else
      {
        $modifier->where( 'appointment.user_id', '=', $db_interviewer_user->id );
      }

      // restrict by site
      $db_restricted_site = $this->get_restricted_site();
      if( !is_null( $db_restricted_site ) )
        $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

      // restrict by appointment type
      $appointment_type = $this->get_argument( 'type', false );
      if( !$appointment_type )
      {
        $modifier->where( 'appointment_type_id', '=', NULL );
      }
      else
      {
        $default = false;
        $appointment_type_id_list = array();

        foreach( explode( ';', $appointment_type ) as $type )
        {
          $type = trim( $type );
          if( 'default' == $type )
          {
            $default = true;
          }
          else
          {
            $db_appointment_type = $appointment_type_class_name::get_unique_record( 'name', $type );
            if( is_null( $db_appointment_type ) )
            {
              log::warning( sprintf(
                'Tried to get onyx appointment list by undefined appointment type "%s".', $type ) );
            }
            else
            {
              $appointment_type_id_list[] = $db_appointment_type->id;
            }
          }
        }

        if( $default && 0 < count( $appointment_type_id_list ) ) 
        {   
          $modifier->where_bracket( true );
          $modifier->where( 'appointment_type_id', '=', NULL );
          $modifier->or_where( 'appointment_type_id', 'IN', $appointment_type_id_list );
          $modifier->where_bracket( false );
        }   
        else if( $default )
        {
          $modifier->where( 'appointment_type_id', '=', NULL );
        }
        else if( 0 < count( $appointment_type_id_list ) ) 
        {
          $modifier->where( 'appointment_type_id', 'IN', $appointment_type_id_list );
        }
      }
    }
    else
    {
      // add the appointment's duration
      $modifier->left_join( 'setting', 'participant_site.site_id', 'setting.site_id' );
      $select->add_column(
        "IF(\n".
        "    appointment.user_id IS NULL,\n".
        "    IF(\n".
        "      setting.id IS NOT NULL,\n".
        "      setting.appointment_site_duration,\n".
        "      ( SELECT DEFAULT( appointment_site_duration ) FROM setting LIMIT 1 )\n".
        "    ),\n".
        "    IF(\n".
        "      setting.id IS NOT NULL,\n".
        "      setting.appointment_home_duration,\n".
        "      ( SELECT DEFAULT( appointment_home_duration ) FROM setting LIMIT 1 )\n".
        "    )\n".
        "  )",
        'duration',
        false );

      $modifier->left_join( 'user', 'appointment.user_id', 'user.id' );
      // include the user first/last/name as supplemental data (for both get and query)
      $select->add_column(
        'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
        'formatted_user_id',
        false );

      // include the participant uid and interviewer name as supplemental data
      $modifier->left_join( 'user', 'appointment.user_id', 'user.id' );
      $select->add_table_column( 'participant', 'uid' );
      $select->add_table_column( 'user', 'name', 'username' );

      // add the address "summary" column if needed
      if( $select->has_column( 'address_summary' ) )
      {
        $modifier->left_join( 'address', 'appointment.address_id', 'address.id' );
        $modifier->left_join( 'region', 'address.region_id', 'region.id' );
        $select->add_column(
          'CONCAT_WS( ", ", address1, address2, city, region.name )', 'address_summary', false );
      }

      // restrict by site
      $db_restricted_site = $this->get_restricted_site();
      if( !is_null( $db_restricted_site ) )
        $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

      // restrict by user
      if( 1 == $db_role->tier && !$db_role->all_sites )
        $modifier->where( sprintf( 'IFNULL( appointment.user_id, %s )', $db_user->id ), '=', $db_user->id );

      if( $select->has_table_columns( 'appointment_type' ) )
        $modifier->left_join( 'appointment_type', 'appointment.appointment_type_id', 'appointment_type.id' );

      if( $select->has_column( 'state' ) )
      {
        // specialized sql used to determine the appointment's current state
        $sql =
          'IF( appointment.outcome IS NOT NULL, '.
            'outcome, '.
            'IF( UTC_TIMESTAMP() < appointment.datetime, "upcoming", "passed" ) '.
          ')';

        $select->add_column( $sql, 'state', false );
      }

      // restrict by type
      $type = $this->get_argument( 'type', NULL );
      if( !is_null( $type ) ) $modifier->where( 'qnaire.type', '=', $type );
    }
  }
}
