<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
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
    $date_string = sprintf( 'DATE( CONVERT_TZ( appointment.datetime, "UTC", "%s" ) )', $db_user->timezone );
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
        if( !is_null( $db_interview ) && !is_null( $db_interview->end_datetime ) )
        {
          $this->set_data( 'Appointments cannot be changed after an interview is complete.' );
          $this->get_status()->set_code( 306 );
        }
        // no writing of appointments if they have passed
        else if( !is_null( $db_appointment ) && $db_appointment->datetime < util::get_datetime_object() )
        {
          // only allow changing of appointments if a passed appointment is having its outcome changed to cancelled
          $allow = false;
          if( 'PATCH' == $method )
          {
            $data = $this->get_file_as_array();
            if( 'passed' == $db_appointment->get_state() &&
                1 == count( $data ) &&
                array_key_exists( 'outcome', $data ) &&
                'cancelled' == $data['outcome'] )
            {
              $allow = true;
            }
          }

          if( !$allow )
          {
            $this->set_data( 'Appointments cannot be changed after they have passed.' );
            $this->get_status()->set_code( 306 );
          }
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
    $db_site = $session->get_site();
    $db_role = $session->get_role();

    $modifier->left_join( 'appointment_type', 'appointment.appointment_type_id', 'appointment_type.id' );
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $participant_site_join_mod = lib::create( 'database\modifier' );
    $participant_site_join_mod->where(
      'interview.participant_id', '=', 'participant_site.participant_id', false );
    $participant_site_join_mod->where(
      'participant_site.application_id', '=', $db_application->id );
    $modifier->join_modifier( 'participant_site', $participant_site_join_mod, 'left' );

    if( $select->has_table_columns( 'effective_site' ) )
      $modifier->join( 'site', 'participant_site.site_id', 'effective_site.id', 'left', 'effective_site' );

    // onyx roles need to be treated specially
    if( 'onyx' == $db_role->name )
    {
      $onyx_instance_class_name = lib::create( 'database\onyx_instance' );
      $appointment_type_class_name = lib::create( 'database\appointment_type' );
      $form_type_class_name = lib::create( 'database\form_type' );

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
      $select->add_table_column( 'address', 'address1', 'street' );
      $select->add_table_column( 'address', 'city' );
      $select->add_table_column( 'region', 'name', 'province' );
      $select->add_table_column( 'address', 'postcode' );
      $select->add_table_column( 'participant', 'IFNULL( email, "" )', 'email', false );
      $select->add_column(
        'IF( 70 <= TIMESTAMPDIFF( YEAR, date_of_birth, CURDATE() ) AND proxy_form.total = 0, 1, 0 )',
        'ask_proxy',
        false
      );

      // make sure the participant has consented to participate
      $modifier->join( 'participant_last_consent', 'participant.id', 'participant_last_consent.participant_id' );
      $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
      $modifier->join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
      $modifier->where( 'consent_type.name', '=', 'participation' );
      $modifier->where( 'consent.accept', '=', true );

      // link to the primary address
      $modifier->join(
        'participant_primary_address', 'participant.id', 'participant_primary_address.participant_id' );
      $modifier->left_join( 'address', 'participant_primary_address.address_id', 'address.id' );
      $modifier->left_join( 'region', 'address.region_id', 'region.id' );
      $modifier->where( 'participant_site.site_id', '=', $db_site->id );

      // link to the number of proxy forms the participant has (which may be zero)
      $proxy_form_type_id_list = array();
      $form_type_sel = lib::create( 'database\select' );
      $form_type_sel->add_column( 'id' );
      $form_type_mod = lib::create( 'database\modifier' );
      $form_type_mod->where( 'name', 'IN', array( 'proxy', 'general_proxy' ) );
      foreach( $form_type_class_name::select( $form_type_sel, $form_type_mod ) as $form_type )
        $proxy_form_type_id_list[] = $form_type['id'];

      $proxy_form_sel = lib::create( 'database\select' );
      $proxy_form_sel->add_column( 'id', 'participant_id' );
      $proxy_form_sel->add_column( 'IF( form.id IS NULL, 0, COUNT(*) )', 'total', false );
      $proxy_form_sel->from( 'participant' );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'form.participant_id', false );
      $join_mod->where( 'form.form_type_id', 'IN', $proxy_form_type_id_list );
      $proxy_form_mod = lib::create( 'database\modifier' );
      $proxy_form_mod->join_modifier( 'form', $join_mod, 'left' );
      $proxy_form_mod->group( 'participant.id' );

      $modifier->join(
        sprintf( '( %s %s ) AS proxy_form', $proxy_form_sel->get_sql(), $proxy_form_mod->get_sql() ),
        'participant.id',
        'proxy_form.participant_id'
      );

      // restrict by onyx instance
      $db_onyx_instance = $onyx_instance_class_name::get_unique_record( 'user_id', $db_user->id );
      if( is_null( $db_onyx_instance ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Tried to get appointment list for onyx user "%s" that has no onyx instance record.',
                   $db_user->name ),
          __METHOD__ );

      $db_interviewer_user = $db_onyx_instance->get_interviewer_user();
      if( !is_null( $db_interviewer_user ) )
      {
        // home interview
        $modifier->where( 'appointment.user_id', '=', $db_interviewer_user->id );
      }
      else
      {
        // site interview
        $modifier->where( 'appointment.user_id', '=', NULL );

        $select->add_column(
          'IF( IFNULL( hin_consent.accept, false ), "YES", "NO" )',
          'consentToHIN',
          false
        );
        $select->add_column(
          'IF( IFNULL( blood_consent.accept, true ), "YES", "NO" )',
          'consentToDrawBlood',
          false
        );
        $select->add_column(
          'IF( IFNULL( urine_consent.accept, true ), "YES", "NO" )',
          'consentToTakeUrine',
          false
        );

        // provide HIN access consent
        $modifier->join(
          'participant_last_consent',
          'participant.id',
          'participant_last_hin_consent.participant_id',
          '', // regular join type
          'participant_last_hin_consent'
        );
        $modifier->join(
          'consent_type',
          'participant_last_hin_consent.consent_type_id',
          'hin_consent_type.id',
          '', // regular join type
          'hin_consent_type'
        );
        $modifier->left_join(
          'consent',
          'participant_last_hin_consent.consent_id',
          'hin_consent.id',
          'hin_consent'
        );
        $modifier->where( 'hin_consent_type.name', '=', 'HIN access' );

        // provide blood consent
        $modifier->join(
          'participant_last_consent',
          'participant.id',
          'participant_last_blood_consent.participant_id',
          '', // regular join type
          'participant_last_blood_consent'
        );
        $modifier->join(
          'consent_type',
          'participant_last_blood_consent.consent_type_id',
          'blood_consent_type.id',
          '', // regular join type
          'blood_consent_type'
        );
        $modifier->left_join(
          'consent',
          'participant_last_blood_consent.consent_id',
          'blood_consent.id',
          'blood_consent'
        );
        $modifier->where( 'blood_consent_type.name', '=', 'draw blood' );

        // provide urine consent
        $modifier->join(
          'participant_last_consent',
          'participant.id',
          'participant_last_urine_consent.participant_id',
          '', // regular join type
          'participant_last_urine_consent'
        );
        $modifier->join(
          'consent_type',
          'participant_last_urine_consent.consent_type_id',
          'urine_consent_type.id',
          '', // regular join type
          'urine_consent_type'
        );
        $modifier->left_join(
          'consent',
          'participant_last_urine_consent.consent_id',
          'urine_consent.id',
          'urine_consent'
        );
        $modifier->where( 'urine_consent_type.name', '=', 'take urine' );
      }

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

      // add help text (for calendar events)
      $modifier->join( 'language', 'participant.language_id', 'language.id' );
      $modifier->left_join( 'phone', 'participant.id', 'phone.participant_id' );
      $modifier->where( 'IFNULL( phone.rank, 1 )', '=', 1 );
      $select->add_column(
        'CONCAT( '.
          'participant.first_name, " ", participant.last_name, " (", language.name, ")", '.
          'IF( phone.number IS NOT NULL, CONCAT( "\n", phone.number ), "" ), '.
          'IF( participant.global_note IS NOT NULL, CONCAT( "\n", participant.global_note ), "" ) '.
        ')',
        'help',
        false
      );
      // restrict by site
      $db_restricted_site = $this->get_restricted_site();
      if( !is_null( $db_restricted_site ) )
        $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

      // restrict by user
      if( 'interviewer' == $db_role->name )
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
