<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\onyx;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\service
{
  /**
   * Constructor
   * 
   * @param string $path The URL of the service (not including the base)
   * @param array $args An associative array of arguments to be processed by the post operation.
   * @param string $file The raw file posted by the request
   * @access public
   */
  public function __construct( $path, $args, $file )
  {
    parent::__construct( 'POST', $path, $args, $file );
  }

  /**
   * Override parent method since onyx is a meta-resource
   */
  protected function create_resource( $index )
  {
    return $this->get_resource_value( $index );
  }

  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    if( $this->may_continue() )
    {
      $participant_class_name = lib::get_class_name( 'database\participant' );

      // determine the property based on the export type
      $session = lib::create( 'business\session' );
      $db_application = $session->get_application();
      $db_site = $session->get_site();
      $type = $this->get_resource( 0 );
      $data = $this->get_file_as_object();
      $property = $this->get_property_name();

      // determine any errors
      $error = NULL;
      if( is_null( $property ) ) $error = sprintf( 'Invalid export type "%s"', $type );
      else if( !is_object( $data ) ) $error = 'Unable to parse file as JSON data';
      else if( !property_exists( $data, $property ) ) $error = sprintf( 'Expecting property "%s"', $property );
      else if( !is_array( $data->$property ) )
        $error = sprintf( 'Expecting property "%s" to be an array', $property );
      else
      {
        // make sure all participants exist
        $input_list = array();
        foreach( $this->get_file_as_object()->$property as $list )
          $input_list = array_merge( $input_list, get_object_vars( $list ) );
        ksort( $input_list );

        // match all UIDs for participants belonging to the correct site and application
        $participant_mod = lib::create( 'database\modifier' );

        // restrict by application
        $sub_mod = lib::create( 'database\modifier' );
        $sub_mod->where( 'participant.id', '=', 'application_has_participant.participant_id', false );
        $sub_mod->where( 'application_has_participant.application_id', '=', $db_application->id );
        $sub_mod->where( 'application_has_participant.datetime', '!=', NULL );
        $participant_mod->join_modifier(
          'application_has_participant', $sub_mod, $db_application->release_based ? '' : 'left' );

        // restrict by site
        $sub_mod = lib::create( 'database\modifier' );
        $sub_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
        $sub_mod->where( 'participant_site.application_id', '=', $db_application->id );
        $sub_mod->where( 'participant_site.site_id', '=', $db_site->id );
        $participant_mod->join_modifier( 'participant_site', $sub_mod );
        $participant_mod->where( 'uid', 'IN', array_keys( $input_list ) );
        $participant_mod->order( 'uid' );
        foreach( $participant_class_name::select_objects( $participant_mod ) as $db_participant )
        {
          $this->object_list[$db_participant->uid] = array(
            'participant' => $db_participant,
            'object' => $input_list[$db_participant->uid] );
        }

        // search for missing uids in the object list
        if( count( $input_list ) != count( $this->object_list ) )
        {
          $missing_list = array_diff( array_keys( $input_list ), array_keys( $this->object_list ) );
          $error = sprintf( 'The following UIDs are invalid: "%s"', implode( ', ', $missing_list ) );
        }
      }

      if( !is_null( $error ) )
      {
        $this->set_data( $error );
        $this->status->set_code( 306 );
        log::warning( sprintf( 'Responding to onyx post request with 306 message: "%s"', $error ) );
      }
    }
  }

  /**
   * Override parent method since onyx is a meta-resource
   */
  protected function execute()
  {
    $type = $this->get_resource( 0 );

    try
    {
      if( 'consent' == $type )
        foreach( $this->object_list as $data )
          $this->process_consent( $data['participant'], $data['object'] );
      else if( 'hin' == $type )
        foreach( $this->object_list as $data )
          $this->process_hin( $data['participant'], $data['object'] );
      else if( 'extendedhin' == $type )
        foreach( $this->object_list as $data )
          $this->process_extended_hin( $data['participant'], $data['object'] );
      else if( 'participants' == $type )
        foreach( $this->object_list as $data )
          $this->process_participant( $data['participant'], $data['object'] );
      else if( 'generalproxy' == $type ) // note the missing underscore in general proxy
        foreach( $this->object_list as $data )
          $this->process_general_proxy( $data['participant'], $data['object'] );
      else if( 'proxy' == $type )
        foreach( $this->object_list as $data )
          $this->process_proxy( $data['participant'], $data['object'] );
    }
    catch( \cenozo\exception\runtime $e )
    {
      $this->set_data( $e->get_raw_message() );
      $this->status->set_code( 306 );
      log::warning( sprintf( 'Responding to onyx post request with 306 message: "%s"', $e->get_raw_message() ) );
    }

    if( is_null( $this->status->get_code() ) ) $this->status->set_code( 201 );
  }

  /**
   * Returns the property name sent by Onyx (in CamelCase)
   * 
   * @return string
   * @access private
   */
  private function get_property_name()
  {
    $type = $this->get_resource( 0 );
    if( in_array( $type, array( 'consent', 'proxy' ) ) ) return 'Consent';
    else if( 'generalproxy' == $type ) return 'GeneralProxy';
    else if( 'extendedhin' == $type ) return 'ExtendedHIN';
    else if( 'hin' == $type ) return 'ConsentHIN';
    else if( 'participants' == $type ) return 'Participants';
    return NULL;
  }

  /**
   * Processes the onyx/consent service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Onyx
   * @access private
   */
  private function process_consent( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $form_type_class_name = lib::get_class_name( 'database\form_type' );
    $region_class_name = lib::get_class_name( 'database\region' );

    $datetime_obj = $this->get_datetime_from_object( $object );

    // create the form
    $db_form_type = $form_type_class_name::get_unique_record( 'name', 'consent' );
    $db_form = lib::create( 'database\form' );
    $db_form->participant_id = $db_participant->id;
    $db_form->form_type_id = $db_form_type->id;
    $db_form->date = $datetime_obj;
    $db_form->save();

    // save the PDF form
    $member = 'pdfForm';
    if( property_exists( $object, $member ) )
    {
      if( !$db_form->write_file( base64_decode( chunk_split( $object->$member ) ) ) )
        throw lib::create( 'exception\runtime', 'Unable to write consent form file to disk.', __METHOD__ );
    }

    // add consent records
    $member = 'ConclusiveStatus';
    if( property_exists( $object, $member ) )
    {
      $db_form->add_consent(
        'participation',
        array( 'accept' => 'CONSENT' == $object->$member ? 1 : 0, 'datetime' => $datetime_obj ),
        'Provided by Onyx.'
      );
    }

    $member = 'PCF_CSTSAMP_COM';
    if( property_exists( $object, $member ) )
    {
      $db_form->add_consent(
        'draw blood',
        array( 'accept' => 1 == preg_match( '/y|yes|true|1/i', $object->$member ), 'datetime' => $datetime_obj ),
        'Provided by Onyx.'
      );
    }

    $member = 'PCF_CSTGVDB_COM';
    if( property_exists( $object, $member ) )
    {
      $db_form->add_consent(
        'HIN access',
        array( 'accept' => 1 == preg_match( '/y|yes|true|1/i', $object->$member ), 'datetime' => $datetime_obj ),
        'Provided by Onyx.'
      );
    }

    // HIN information
    $member = 'ADM_NUMB_COM';
    if( property_exists( $object, $member ) && 'HEALTH-NUMBER' == $object->$member )
    {
      $member = 'ADM_NUMB_NB_COM';
      if( property_exists( $object, $member ) )
      {
        $hin = array( 'code' => $object->$member );

        $member = 'ADM_PROV_COM';
        if( property_exists( $object, $member ) )
        {
          // convert province text to a region
          $province = 'NEW-FOUNDLAND-LABRADOR' == $object->$member
                    ? 'Newfoundland and Labrador' // special case
                    : ucwords( trim( str_replace( '-', ' ', $object->$member ) ) );
          $db_region = $region_class_name::get_unique_record( 'name', $province );
          $hin['region_id'] = is_null( $db_region ) ? NULL : $db_region->id;
        }

        $db_form->add_hin( $hin );
      }
    }
  }

  /**
   * Processes the onyx/hin service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Onyx
   * @access private
   */
  private function process_hin( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $form_type_class_name = lib::get_class_name( 'database\form_type' );

    $datetime_obj = $this->get_datetime_from_object( $object );

    // create the form
    $db_form_type = $form_type_class_name::get_unique_record( 'name', 'consent' );
    $db_form = lib::create( 'database\form' );
    $db_form->participant_id = $db_participant->id;
    $db_form->form_type_id = $db_form_type->id;
    $db_form->date = $datetime_obj;
    $db_form->save();

    // save the PDF form
    $member = 'pdfForm';
    if( property_exists( $object, $member ) )
    {
      if( !$db_form->write_file( base64_decode( chunk_split( $object->$member ) ) ) )
        throw lib::create( 'exception\runtime', 'Unable to write consent form file to disk.', __METHOD__ );
    }

    // extneded HIN consent
    $member = 'ICF_10HIN_COM';
    if( property_exists( $object, $member ) )
    {
      $db_form->add_consent(
        'HIN extended access',
        array( 'accept' => 1 == preg_match( '/y|yes|true|1/i', $object->$member ), 'datetime' => $datetime_obj ),
        'Provided by Onyx.'
      );
    }
  }

  /**
   * Processes the onyx/extendedhin service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Onyx
   * @access private
   */
  private function process_extended_hin( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $form_type_class_name = lib::get_class_name( 'database\form_type' );
    $region_class_name = lib::get_class_name( 'database\region' );

    $datetime_obj = $this->get_datetime_from_object( $object );

    // save the PDF form (and otherwise ignore if there is no form attached)
    $member = 'pdfForm';
    if( property_exists( $object, $member ) )
    {
      // create the form
      $db_form_type = $form_type_class_name::get_unique_record( 'name', 'extended_hin' );
      $db_form = lib::create( 'database\form' );
      $db_form->participant_id = $db_participant->id;
      $db_form->form_type_id = $db_form_type->id;
      $db_form->date = $datetime_obj;
      $db_form->save();

      if( !$db_form->write_file( base64_decode( chunk_split( $object->$member ) ) ) )
        throw lib::create( 'exception\runtime', 'Unable to write consent form file to disk.', __METHOD__ );
      
      // add consent records
      $member = 'ICF_HIN10YEARACCESS_COM';
      if( property_exists( $object, $member ) )
      {
        $db_form->add_consent(
          'Extended HIN Access',
          array( 'accept' => 1 == preg_match( '/y|yes|true|1/i', $object->$member ), 'datetime' => $datetime_obj ),
          'Provided by Onyx.'
        );
      }

      $member = 'ICF_CIHIHINACCESS_COM';
      if( property_exists( $object, $member ) )
      {
        $db_form->add_consent(
          'CIHI Access',
          array( 'accept' => 1 == preg_match( '/y|yes|true|1/i', $object->$member ), 'datetime' => $datetime_obj ),
          'Provided by Onyx.'
        );
      }

      $member = 'ICF_CIHI10YEARACCESS_COM';
      if( property_exists( $object, $member ) )
      {
        $db_form->add_consent(
          'Extended CIHI Access',
          array( 'accept' => 1 == preg_match( '/y|yes|true|1/i', $object->$member ), 'datetime' => $datetime_obj ),
          'Provided by Onyx.'
        );
      }
    }
  }

  /**
   * Processes the onyx/participants service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Onyx
   * @access private
   */
  private function process_participant( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $interviewing_instance_class_name = lib::get_class_name( 'database\interviewing_instance' );
    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
    $equipment_type_class_name = lib::get_class_name( 'database\equipment_type' );
    $equipment_class_name = lib::get_class_name( 'database\equipment' );
    $equipment_loan_class_name = lib::get_class_name( 'database\equipment_loan' );

    // get the onyx instance to tell whether this is a home or site instance
    $db_user = lib::create( 'business\session' )->get_user();
    $db_site = lib::create( 'business\session' )->get_site();
    $db_interviewing_instance = $interviewing_instance_class_name::get_unique_record( 'user_id', $db_user->id );
    if( is_null( $db_interviewing_instance ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf( 'Onyx user "%s" is not linked to any interviewing instance.', $db_user->name ),
        __METHOD__
      );
    }

    // get the interview corresponding with this export
    $interview_type = is_null( $db_interviewing_instance->interviewer_user_id ) ? 'site' : 'home';
    $interview_sel = lib::create( 'database\select' );
    $interview_sel->from( 'interview' );
    $interview_sel->add_column( 'id' );
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $interview_mod->where( 'qnaire.type', '=', $interview_type );
    $interview_mod->where( 'interview.end_datetime', '=', NULL );
    $interview_mod->order_desc( 'interview.start_datetime' );
    $interview_list = $db_participant->get_interview_object_list( $interview_mod );
    if( 0 == count( $interview_list ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf(
          'Trying to export Onyx interview for %s but no matching %s interview can be found.',
          $db_participant->uid,
          $interview_type
        ),
        __METHOD__
      );
    }
    $db_interview = current( $interview_list );

    $member = 'Admin.Interview.endDate';
    $datetime_obj = util::get_datetime_object( property_exists( $object, $member ) ? $object->$member : NULL );

    // participant information
    $member = 'Admin.Participant.honorific';
    if( property_exists( $object, $member ) ) $db_participant->honorific = $object->$member;
    $member = 'Admin.Participant.firstName';
    if( property_exists( $object, $member ) ) $db_participant->first_name = $object->$member;
    $member = 'Admin.Participant.otherName';
    if( property_exists( $object, $member ) ) $db_participant->other_name = $object->$member;
    $member = 'Admin.Participant.lastName';
    if( property_exists( $object, $member ) ) $db_participant->last_name = $object->$member;
    $member = 'Admin.Participant.email';
    if( property_exists( $object, $member ) ) $db_participant->email = $object->$member;
    $member = 'Admin.Participant.gender';
    if( property_exists( $object, $member ) )
      $db_participant->sex = 0 == strcasecmp( 'f', substr( $object->$member, 0, 1 ) ) ? 'female' : 'male';
    // note that we'll ignore the Admin.Participant.birthDate property, even if it exists
    $db_participant->save();

    // consent information: list of member name (from Onyx) and consent type (in database)
    $consent_variable_name_list = [
      array( 'member' => 'Admin.Participant.consentToDrawBlood', 'consent_type' => 'draw blood' ),
      array( 'member' => 'Admin.Participant.consentToTakeUrine', 'consent_type' => 'take urine' ),
      array( 'member' => 'Admin.Participant.f3_stool', 'consent_type' => 'F3 Stool' ),
      array( 'member' => 'Admin.Participant.f3_mobility_trackers', 'consent_type' => 'F3 Mobility Trackers' ),
      array( 'member' => 'Admin.Participant.f3_sleep_trackers', 'consent_type' => 'F3 Sleep Trackers' ),
      array( 'member' => 'Admin.Participant.weston_hbha_study', 'consent_type' => 'Weston HBHA Study' )
    ];

    foreach( $consent_variable_name_list as $consent_data )
    {
      $member = $consent_data['member'];
      if( property_exists( $object, $member ) )
      {
        $db_consent_type = $consent_type_class_name::get_unique_record( 'name', $consent_data['consent_type'] );
        $value = $object->$member;

        // ignore NULL values, it means there is no consent record at all (skipped)
        if( !is_null( $db_consent_type ) && !is_null( $value ) )
        {
          if( is_string( $value ) ) $value = 1 === preg_match( '/y|yes|true|1/i', $value );
          else $value = (bool) $value;

          $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
          if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
          {
            $db_consent = lib::create( 'database\consent' );
            $db_consent->participant_id = $db_participant->id;
            $db_consent->consent_type_id = $db_consent_type->id;
            $db_consent->accept = $value;
            $db_consent->written = false;
            $db_consent->datetime = $datetime_obj;
            $db_consent->note = 'Provided by Onyx.';
            $db_consent->save();
          }
        }
      }
    }

    // look for serial numbers in the object properties
    foreach( get_object_vars( $object ) as $property => $value )
    {
      $matches = NULL;
      if( preg_match( '/Admin.Participant.([^.]+?)([0-9]*).SerialNumber/', $property, $matches ) )
      {
        $serial_number_prop = $property;
        $sent_action_prop = str_replace( '.SerialNumber', '.Sent', $serial_number_prop );
        $received_action_prop = str_replace( '.SerialNumber', '.Received', $serial_number_prop );
        $equipment_number = 0 < strlen( $matches[2] ) ? intval( $matches[2] ) : NULL;

        // decode the equipment type name (provided in CamelCase, we need to Add Spaces)
        $equipment_type_name = preg_replace( '/(.)([A-Z])/', '$1 $2', $matches[1] );
        $db_equipment_type = $equipment_type_class_name::get_unique_record( 'name', $equipment_type_name );
        if( is_null( $db_equipment_type ) )
        {
          throw lib::create( 'exception\runtime',
            sprintf(
              'Recieved serial number for invalid equipment type "%s"',
              $equipment_type_name
            ),
            __METHOD__
          );
        }

        // determine the serial number and sent/received action and datetime
        $sent = NULL;
        $datetime_obj = NULL;
        $serial_number = $value;

        // Add the equipment number to serial number for for stool kits (since they aren't actual serial numbers)
        if( 'Stool Kit' == $equipment_type_name && !is_null( $equipment_number ) )
          $serial_number .= sprintf( '-%d', $equipment_number );

        if( property_exists( $object, $sent_action_prop ) )
        {
          $sent = true;
          $datetime_obj = util::get_datetime_object( $object->$sent_action_prop );
        }
        else if( property_exists( $object, $received_action_prop ) )
        {
          $sent = false;
          $datetime_obj = util::get_datetime_object( $object->$received_action_prop );
        }
        else
        {
          throw lib::create( 'exception\runtime',
            sprintf(
              'Equipment variable "%s" has no matching "%s" or "%s" variable.',
              $member,
              $sent_action_prop,
              $received_action_prop
            ),
            __METHOD__
          );
        }

        // create the equipment if it doesn't already exist
        $db_equipment = $equipment_class_name::get_unique_record( 'serial_number', $serial_number );
        if( is_null( $db_equipment ) )
        {
          $db_equipment = lib::create( 'database\equipment' );
          $db_equipment->equipment_type_id = $db_equipment_type->id;
          $db_equipment->site_id = $db_site->id;
          $db_equipment->serial_number = $serial_number;
          $db_equipment->note = 'Created by Onyx.';
          $db_equipment->save();
        }

        if( $sent )
        {
          // create the equipment loan if it doesn't already exist
          $db_equipment_loan = $equipment_loan_class_name::get_unique_record(
            ['participant_id','equipment_id','start_datetime'],
            [$db_participant->id, $db_equipment->id, $datetime_obj]
          );

          if( is_null( $db_equipment_loan ) )
          {
            // if the equipment is marked as loaned then mark it as returned
            $equipment_loan_mod = lib::create( 'database\modifier' );
            $equipment_loan_mod->where( 'end_datetime', '=', NULL );
            foreach( $db_equipment->get_equipment_loan_object_list( $equipment_loan_mod ) as $db_equipment_loan )
            {
              $db_equipment_loan->end_datetime = $datetime_obj;
              $db_equipment_loan->note = 'Automatically setting end date because of new loan.';
              $db_equipment_loan->save();
            }

            // and now create a new loan record
            $db_equipment_loan = lib::create( 'database\equipment_loan' );
            $db_equipment_loan->participant_id = $db_participant->id;
            $db_equipment_loan->equipment_id = $db_equipment->id;
            $db_equipment_loan->start_datetime = $datetime_obj;
            $db_equipment_loan->save();
          }
        }
        else // received
        {
          // try and find the participant's loan record
          $equipment_loan_mod = lib::create( 'database\modifier' );
          $equipment_loan_mod->where( 'participant_id', '=', $db_participant->id );
          $equipment_loan_mod->order( '-end_datetime' ); // put null values first
          $equipment_loan_list = $db_equipment->get_equipment_loan_object_list( $equipment_loan_mod );
          if( 0 < count( $equipment_loan_list ) )
          {
            // if the loan record is found set the end date (if it isn't already set)
            $db_equipment_loan = current( $equipment_loan_list );
            if( is_null( $db_equipment_loan->end_datetime ) )
            {
              $db_equipment_loan->end_datetime = $datetime_obj;
              $db_equipment_loan->save();
            }
          }
          else
          {
            // create the loan record if it doesn't already exist
            $db_equipment_loan = $equipment_loan_class_name::get_unique_record(
              ['participant_id','equipment_id','start_datetime'],
              [$db_participant->id, $db_equipment->id, $datetime_obj]
            );

            if( is_null( $db_equipment_loan ) )
            {
              $db_equipment_loan = lib::create( 'database\equipment_loan' );
              $db_equipment_loan->participant_id = $db_participant->id;
              $db_equipment_loan->equipment_id = $db_equipment->id;
              $db_equipment_loan->start_datetime = $datetime_obj;
              $db_equipment_loan->end_datetime = $datetime_obj;
              $db_equipment_loan->note = 'Automatically setting start date because loan was never created.';
              $db_equipment_loan->save();
            }
          }
        }
      }
    }

    // general interview comments
    $member = 'GeneralComments';
    if( property_exists( $object, $member ) )
    {
      $data = str_replace( '""', '"', preg_replace( '/^"(.*)"$/', '\1', $object->$member ) );
      $db_interview->note = implode( "\n\n", util::json_decode( $data ) );
      $db_interview->save();
    }

    // interview and appointment status
    $member = 'Admin.Interview.status';
    if( property_exists( $object, $member ) && 'completed' == strtolower( $object->$member ) )
    {
      $db_interview->interviewing_instance_id = $db_interviewing_instance->id;
      $db_interview->complete( NULL, $datetime_obj ); // this will save the interview record
      $db_participant->repopulate_queue( false );
    }
  }

  /**
   * Processes the onyx/general_proxy service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Onyx
   * @access private
   */
  private function process_general_proxy( $db_participant, $object )
  {
    // ignore empty or manual forms
    if( 1 >= count( get_object_vars( $object ) ) ||
        'MANUAL' == strtoupper( $object->mode ) ||
        !property_exists( $object, 'pdfForm' ) ) return;

    $region_class_name = lib::get_class_name( 'database\region' );
    $application_class_name = lib::get_class_name( 'database\application' );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );

    $datetime_obj = $this->get_datetime_from_object( $object );

    // general_proxy form information (send to mastodon for processing)
    $form_data = array(
      'from_instance' => 'onyx',
      'date' => $datetime_obj->format( 'Y-m-d' ),
      'user_id' => $session->get_user()->id,
      'uid' => $db_participant->uid,
      // onyx never sends international contact information (proxy is always sent)
      'proxy_address_international' => false,
      'proxy_phone_international' => false
    );

    $member = 'ICF_ANSW_COM';
    $form_data['continue_questionnaires'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_HCNUMB_COM';
    $form_data['hin_future_access'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_DCSCONTINUE_COM';
    $form_data['continue_dcs_visits'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_PXFIRSTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_first_name'] = $object->$member;

    $member = 'ICF_PXLASTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_last_name'] = $object->$member;

    $member = 'ICF_PXADD_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $parts = explode( ' ', trim( $object->$member ), 2 );
      $form_data['proxy_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $form_data['proxy_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $member = 'ICF_PXADD2_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_address_other'] = $object->$member;

    $member = 'ICF_PXCITY_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_city'] = $object->$member;

    $member = 'ICF_PXPROVINCE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->$member );
      if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->$member );
      if( !is_null( $db_region ) ) $form_data['proxy_region_id'] = $db_region->id;
    }

    $member = 'ICF_PXPOSTALCODE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $postcode = trim( $object->$member );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
      $form_data['proxy_postcode'] = $postcode;
    }

    $member = 'ICF_PXTEL_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $phone = preg_replace( '/[^0-9]/', '', $object->$member );
      $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
      $form_data['proxy_phone'] = $phone;
    }

    $member = 'ICF_OKPROXY_COM';
    $form_data['already_identified'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_PRXINFSM_COM';
    $form_data['same_as_proxy'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    if( !$form_data['same_as_proxy'] )
    {
      // onyx never sends international contact information (only include this if informant isn't the same as proxy)
      $form_data['informant_address_international'] = false;
      $form_data['informant_phone_international'] = false;
    }

    $member = 'ICF_INFFIRSTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_first_name'] = $object->$member;

    $member = 'ICF_INFLASTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_last_name'] = $object->$member;

    $member = 'ICF_INFADD_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $parts = explode( ' ', trim( $object->$member ), 2 );
      $form_data['informant_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $form_data['informant_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $member = 'ICF_INFADD2_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_address_other'] = $object->$member;

    $member = 'ICF_INFCITY_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_city'] = $object->$member;

    $member = 'ICF_INFPROVINCE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->$member );
      if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->$member );
      if( !is_null( $db_region ) ) $form_data['informant_region_id'] = $db_region->id;
    }

    $member = 'ICF_INFPOSTALCODE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $postcode = trim( $object->$member );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
      $form_data['informant_postcode'] = $postcode;
    }

    $member = 'ICF_INFTEL_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $phone = preg_replace( '/[^0-9]/', '', $object->$member );
      $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
      $form_data['informant_phone'] = $phone;
    }

    $member = 'ICF_TEST_COM';
    if( property_exists( $object, $member ) )
      $form_data['continue_physical_tests'] = 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'pdfForm';
    if( property_exists( $object, $member ) ) $form_data['data'] = $object->$member;

    // we need to repopulate the queue and complete any transactions before continuing
    $queue_class_name::execute_delayed();
    $session->get_database()->complete_transaction();

    // now send all data to mastodon's data entry system
    $curl = curl_init();

    $authentication = sprintf( '%s:%s',
      $setting_manager->get_setting( 'utility', 'username' ),
      $setting_manager->get_setting( 'utility', 'password' ) );

    // set URL and other appropriate options
    $db_mastodon_application = $application_class_name::get_unique_record( 'name', 'mastodon' );
    curl_setopt( $curl, CURLOPT_URL, sprintf( '%s/api/general_proxy_form', $db_mastodon_application->url ) );
    curl_setopt( $curl, CURLOPT_HTTPHEADER,
      array( sprintf( 'Authorization:Basic %s', base64_encode( $authentication ) ) ) );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, util::json_encode( $form_data ) );

    $response = curl_exec( $curl );
    $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
    if( 306 == $code )
    {
      log::warning( sprintf( 'Responding to onyx post request with 306 message: "%s"', $response ) );
    }
    else if( 409 == $code )
    {
      // ignore duplicate errors since we can proceed knowing the form already exists
      $code = 201;
      $response = '';
    }

    $this->set_data( $response );
    $this->status->set_code( $code );
  }

  /**
   * Processes the onyx/proxy service
   * 
   * @param database\participant $db_participant The participant being exported to
   * @param stdClass $object The data sent by Onyx
   * @access private
   */
  private function process_proxy( $db_participant, $object )
  {
    // ignore empty or manual forms
    if( 1 >= count( get_object_vars( $object ) ) ||
        'MANUAL' == strtoupper( $object->mode ) ||
        !property_exists( $object, 'pdfForm' ) ) return;

    $region_class_name = lib::get_class_name( 'database\region' );
    $application_class_name = lib::get_class_name( 'database\application' );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );

    $datetime_obj = $this->get_datetime_from_object( $object );

    // proxy form information (send to mastodon for processing)
    $form_data = array(
      'from_instance' => 'onyx',
      'date' => $datetime_obj->format( 'Y-m-d' ),
      'user_id' => $session->get_user()->id,
      'uid' => $db_participant->uid
    );

    $member = 'ICF_IDPROXY_COM';
    $form_data['proxy'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_OKPROXY_COM';
    $form_data['already_identified'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_PXFIRSTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_first_name'] = $object->$member;

    $member = 'ICF_PXLASTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_last_name'] = $object->$member;

    $member = 'ICF_PXADD_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $parts = explode( ' ', trim( $object->$member ), 2 );
      $form_data['proxy_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $form_data['proxy_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $member = 'ICF_PXADD2_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_address_other'] = $object->$member;

    $member = 'ICF_PXCITY_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['proxy_city'] = $object->$member;

    $member = 'ICF_PXPROVINCE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->$member );
      if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->$member );
      if( !is_null( $db_region ) ) $form_data['proxy_region_id'] = $db_region->id;
    }

    $member = 'ICF_PXPOSTALCODE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $postcode = trim( $object->$member );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
      $form_data['proxy_postcode'] = $postcode;
    }

    $member = 'ICF_PXTEL_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $phone = preg_replace( '/[^0-9]/', '', $object->$member );
      $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
      $form_data['proxy_phone'] = $phone;
    }

    $member = 'ICF_PRXINF_COM';
    $form_data['informant'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_PRXINFSM_COM';
    $form_data['same_as_proxy'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_INFFIRSTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_first_name'] = $object->$member;

    $member = 'ICF_INFLASTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_last_name'] = $object->$member;

    $member = 'ICF_INFADD_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $parts = explode( ' ', trim( $object->$member ), 2 );
      $form_data['informant_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $form_data['informant_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $member = 'ICF_INFADD2_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_address_other'] = $object->$member;

    $member = 'ICF_INFCITY_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $form_data['informant_city'] = $object->$member;

    $member = 'ICF_INFPROVINCE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->$member );
      if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->$member );
      if( !is_null( $db_region ) ) $form_data['informant_region_id'] = $db_region->id;
    }

    $member = 'ICF_INFPOSTALCODE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $postcode = trim( $object->$member );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
      $form_data['informant_postcode'] = $postcode;
    }

    $member = 'ICF_INFTEL_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $phone = preg_replace( '/[^0-9]/', '', $object->$member );
      $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
      $form_data['informant_phone'] = $phone;
    }

    $member = 'ICF_ANSW_COM';
    $form_data['continue_questionnaires'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_TEST_COM';
    if( property_exists( $object, $member ) )
      $form_data['continue_physical_tests'] = 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_SAMP_COM';
    if( property_exists( $object, $member ) )
      $form_data['continue_draw_blood'] = 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_HCNUMB_COM';
    $form_data['hin_future_access'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'pdfForm';
    if( property_exists( $object, $member ) ) $form_data['data'] = $object->$member;

    // we need to repopulate the queue and complete any transactions before continuing
    $queue_class_name::execute_delayed();
    $session->get_database()->complete_transaction();

    // now send all data to mastodon's data entry system
    $curl = curl_init();

    $authentication = sprintf( '%s:%s',
      $setting_manager->get_setting( 'utility', 'username' ),
      $setting_manager->get_setting( 'utility', 'password' ) );

    // set URL and other appropriate options
    $db_mastodon_application = $application_class_name::get_unique_record( 'name', 'mastodon' );
    curl_setopt( $curl, CURLOPT_URL, sprintf( '%s/api/proxy_form', $db_mastodon_application->url ) );
    curl_setopt( $curl, CURLOPT_HTTPHEADER,
      array( sprintf( 'Authorization:Basic %s', base64_encode( $authentication ) ) ) );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, util::json_encode( $form_data ) );

    $response = curl_exec( $curl );
    $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
    if( 306 == $code )
    {
      log::warning( sprintf( 'Responding to onyx post request with 306 message: "%s"', $response ) );
    }
    else if( 409 == $code )
    {
      // ignore duplicate errors since we can proceed knowing the form already exists
      $code = 201;
      $response = '';
    }

    $this->set_data( $response );
    $this->status->set_code( $code );
  }

  /**
   * Gets the datetime from an Onyx object (expecting timeEnd or timeStart properties)
   * 
   * @param stdClass $object
   * @return DateTime
   * @access private
   */
  private function get_datetime_from_object( $object )
  {
    // try timeEnd, if null then try timeStart, if null then use today's date
    $datetime = NULL;
    if( property_exists( $object, 'timeEnd' ) && 0 < strlen( $object->timeEnd ) )
      $datetime = $object->timeEnd;
    else if( property_exists( $object, 'timeStart' ) && 0 < strlen( $object->timeStart ) )
      $datetime = $object->timeStart;

    return util::get_datetime_object( $datetime );
  }

  /**
   * A list of participant/object pairs used for processing.  Each element contains and associative
   * array with two elements, a participant record and the object used for processing.
   * @var array( array( 'participant', 'object' ) )
   * @access private
   */
  private $object_list = array();
}
