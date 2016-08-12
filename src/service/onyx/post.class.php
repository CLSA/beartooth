<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\onyx;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\service
{
  /** 
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
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

    if( 300 > $this->status->get_code() )
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
      else if( 'participants' == $type )
        foreach( $this->object_list as $data )
          $this->process_participant( $data['participant'], $data['object'] );
      else if( 'proxy' == $type )
        foreach( $this->object_list as $data )
          $this->process_proxy( $data['participant'], $data['object'] );
    }
    catch( \cenozo\exception\runtime $e )
    {
      $this->set_data( $e->get_raw_message() );
      $this->status->set_code( 306 );
    }

    $this->status->set_code( 201 );
  }

  /**
   * TODO: document
   */
  private function get_property_name()
  {
    $type = $this->get_resource( 0 );
    if( 'consent' == $type ) return 'Consent';
    else if( 'hin' == $type ) return 'ConsentHIN';
    else if( 'participants' == $type ) return 'Participants';
    else if( 'proxy' == $type ) return 'Consent';
    return NULL;
  }

  /**
   * TODO: document
   */
  private function process_consent( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    // get the datetime of the export
    // try timeEnd, if null then try timeStart, if null then use today's date
    $member = 'timeEnd';
    $datetime = NULL;
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) ) $datetime = $object->$member;
    else
    {
      $member = 'timeStart';
      if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) ) $datetime = $object->$member;
    }
    $datetime_obj = util::get_datetime_object( $datetime );

    // consent information
    $member = 'ConclusiveStatus';
    if( property_exists( $object, $member ) )
    {
      $value = 'CONSENT' == $object->$member ? 1 : 0;

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'participation' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    $member = 'PCF_CSTSAMP_COM';
    if( property_exists( $object, $member ) )
    {
      $value = $object->$member;
      if( is_string( $value ) ) $value = 1 === preg_match( '/y|yes|true|1/i', $value );
      else $value = (bool) $value;

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'draw blood' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    $member = 'PCF_CSTGVDB_COM';
    if( property_exists( $object, $member ) )
    {
      $value = 1 == preg_match( '/y|yes|true|1/i', $object->PCF_CSTGVDB_COM );

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'HIN access' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    // HIN information
    $member = 'ADM_NUMB_COM';
    if( property_exists( $object, $member ) && 'HEALTH-NUMBER' == $object->$member )
    {
      $member = 'ADM_NUMB_NB_COM';
      if( property_exists( $object, $member ) )
      {
        $code = $object->$member;

        $db_region = NULL;
        $member = 'ADM_PROV_COM';
        if( property_exists( $object, $member ) )
        {
          // convert province text to a region
          $province = 'NEW-FOUNDLAND-LABRADOR' == $object->$member
                    ? 'Newfoundland and Labrador' // special case
                    : ucwords( trim( str_replace( '-', ' ', $object->$member ) ) );
          $db_region = $region_class_name::get_unique_record( 'name', $province );
        }

        // add the hin information if it doesn't already exist
        $db_last_hin = $db_participant->get_last_hin();
        if( is_null( $db_last_hin ) ||
            $code != $db_last_hin->code ||
            ( is_null( $db_region ) && !is_null( $db_last_hin->region_id ) ) ||
            ( !is_null( $db_region ) && $db_region->id != $db_last_hin->region_id ) )
        {
          $db_hin = lib::create( 'database\hin' );
          $db_hin->participant_id = $db_participant->id;
          $db_hin->code = $code;
          $db_hin->region_id = is_null( $db_region ) ? NULL : $db_region->id;
          $db_hin->datetime = $datetime_obj;
        }
      }
    }

    // PDF form
    $member = 'pdfForm';
    if( property_exists( $object, $member ) )
    { // if a form is included we need to send the request to mastodon
      $form = $object->$member;
      // TODO: add to form system
    }
  }

  /**
   * TODO: document
   */
  private function process_hin( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );

    // get the datetime of the export
    // try timeEnd, if null then try timeStart, if null then use today's date
    $member = 'timeEnd';
    $datetime = NULL;
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) ) $datetime = $object->$member;
    else
    {
      $member = 'timeStart';
      if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) ) $datetime = $object->$member;
    }
    $datetime_obj = util::get_datetime_object( $datetime );

    // update the HIN details
    $member = 'ICF_10HIN_COM';
    if( property_exists( $object, $member ) )
    {
      $value = 1 == preg_match( '/y|yes|true|1/i', $object->$member );
      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'HIN extended access' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    // PDF form
    $member = 'pdfForm';
    if( property_exists( $object, $member ) )
    { // if a form is included we need to send the request to mastodon
      $form = $object->$member;
      // TODO: add to form system
    }
  }

  /**
   * TODO: document
   */
  private function process_participant( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $onyx_instance_class_name = lib::get_class_name( 'database\onyx_instance' );
    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

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
    $member = 'Admin.Participant.birthDate';
    if( property_exists( $object, $member ) )
      $db_participant->date_of_birth = util::get_datetime_object( $object->$member )->format( 'Y-m-d' );
    $db_participant->save();

    // next-of-kin information
    $db_next_of_kin = $db_participant->get_next_of_kin();
    if( is_null( $db_next_of_kin ) )
    {
      $db_next_of_kin = lib::create( 'database\next_of_kin' );
      $db_next_of_kin->participant_id = $db_participant->id;
    }
    $member = 'Admin.Participant.nextOfKin.firstName';
    if( property_exists( $object, $member ) ) $db_next_of_kin->first_name = $object->$member;
    $member = 'Admin.Participant.nextOfKin.lastName';
    if( property_exists( $object, $member ) ) $db_next_of_kin->last_name = $object->$member;
    $member = 'Admin.Participant.nextOfKin.gender';
    if( property_exists( $object, $member ) ) $db_next_of_kin->gender = $object->$member;
    $member = 'Admin.Participant.nextOfKin.phone';
    if( property_exists( $object, $member ) ) $db_next_of_kin->phone = $object->$member;
    $member = 'Admin.Participant.nextOfKin.street';
    if( property_exists( $object, $member ) ) $db_next_of_kin->street = $object->$member;
    $member = 'Admin.Participant.nextOfKin.city';
    if( property_exists( $object, $member ) ) $db_next_of_kin->city = $object->$member;
    $member = 'Admin.Participant.nextOfKin.province';
    if( property_exists( $object, $member ) ) $db_next_of_kin->province = $object->$member;
    $member = 'Admin.Participant.nextOfKin.postalCode';
    if( property_exists( $object, $member ) ) $db_next_of_kin->postal_code = $object->$member;
    $db_next_of_kin->save();

    // consent information
    $member = 'Admin.Participant.consentToDrawBlood';
    if( property_exists( $object, $member ) )
    {
      $value = $object->$member;
      if( is_string( $value ) ) $value = 1 === preg_match( '/y|yes|true|1/i', $value );
      else $value = (bool) $value;

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'draw blood' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    $member = 'Admin.Participant.consentToTakeUrine';
    if( property_exists( $object, $member ) )
    {
      $value = $object->$member;
      if( is_string( $value ) ) $value = 1 === preg_match( '/y|yes|true|1/i', $value );
      else $value = (bool) $value;

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'take urine' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    // interview and appointment status
    $member = 'Admin.Interview.status';
    if( property_exists( $object, $member ) && 'completed' == strtolower( $object->$member ) )
    {
      $member = 'Admin.ApplicationConfiguration.siteCode';
      if( !property_exists( $object, $member ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Missing required object member "%s" from %s', $member, $db_participant->uid ),
          __METHOD__ );
      
      $onyx_instance_sel = lib::create( 'database\select' );
      $onyx_instance_mod = lib::create( 'database\modifier' );
      $onyx_instance_mod->join( 'user', 'onyx_instance.user_id', 'user.id' );
      $onyx_instance_mod->where( 'user.name', '=', $object->$member );
      $onyx_instance_list = $onyx_instance_class_name::select_objects( $onyx_instance_mod );
      if( 0 < count( $onyx_instance_list ) )
      {
        $db_onyx_instance = current( $onyx_instance_list );
        $interview_type = is_null( $db_onyx_instance->interviewer_user_id ) ? 'site' : 'home';
      }
      else
      {
        // can't find the onyx instance, get interview type based on the name instead
        if( preg_match( '/dcs|site/i', $object->$member ) ) $interview_type = 'site';
        else if( preg_match( '/home/i', $object->$member ) ) $interview_type = 'home';
        else $interview_type = false;
      }

      // get the interview corresponding with this export
      $interview_sel = lib::create( 'database\select' );
      $interview_sel->from( 'interview' );
      $interview_sel->add_column( 'id' );
      $interview_mod = lib::create( 'database\modifier' );
      $interview_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $interview_mod->where( 'qnaire.type', '=', $interview_type );
      $interview_mod->where( 'interview.end_datetime', '=', NULL );
      $interview_mod->order_desc( 'interview.start_datetime' );
      $interview_list = $db_participant->get_interview_object_list( $interview_mod );
      if( 0 < count( $interview_list ) )
      {
        $db_interview = current( $interview_list );
        $db_interview->complete( NULL, $datetime_obj );
      }
    }
  }

  /**
   * TODO: document
   */
  private function process_proxy( $db_participant, $object )
  {
    if( 1 >= count( get_object_vars( $object ) ) ) return;

    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
    $region_class_name = lib::get_class_name( 'database\region' );

    // get the datetime of the export
    // try timeEnd, if null then try timeStart, if null then use today's date
    $member = 'timeEnd';
    $datetime = NULL;
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) ) $datetime = $object->$member;
    else
    {
      $member = 'timeStart';
      if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) ) $datetime = $object->$member;
    }
    $datetime_obj = util::get_datetime_object( $datetime );

    // consent information
    $member = 'ICF_TEST_COM';
    if( property_exists( $object, $member ) )
    {
      $value = 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'continue physical tests' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    $member = 'ICF_SAMP_COM';
    if( property_exists( $object, $member ) )
    {
      $value = 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', 'continue draw blood' );
      $db_last_consent = $db_participant->get_last_consent( $db_consent_type );
      if( is_null( $db_last_consent ) || $db_last_consent->accept != $value || $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime_obj;
        $db_consent->note = 'Provided by Onyx.';
      }
    }

    // proxy form information
    $entry = array();
    $entry['date'] = $datetime_obj->format( 'Y-m-d' );

    $member = 'ICF_IDPROXY_COM';
    $entry['proxy'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_OKPROXY_COM';
    $entry['already_identified'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_PXFIRSTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['proxy_first_name'] = $object->$member;

    $member = 'ICF_PXLASTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['proxy_last_name'] = $object->$member;

    $member = 'ICF_PXADD_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $parts = explode( ' ', trim( $object->$member ), 2 );
      $entry['proxy_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $entry['proxy_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $member = 'ICF_PXADD2_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['proxy_address_other'] = $object->$member;

    $member = 'ICF_PXCITY_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['proxy_city'] = $object->$member;

    $member = 'ICF_PXPROVINCE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->$member );
      if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->$member );
      if( !is_null( $db_region ) ) $entry['proxy_region_id'] = $db_region->id;
    }

    $member = 'ICF_PXPOSTALCODE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $postcode = trim( $object->$member );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
      $entry['proxy_postcode'] = $postcode;
    }

    $member = 'ICF_PXTEL_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $phone = preg_replace( '/[^0-9]/', '', $object->$member );
      $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
      $entry['proxy_phone'] = $phone;
    }

    $member = 'ICF_PRXINF_COM';
    $entry['informant'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_PRXINFSM_COM';
    $entry['same_as_proxy'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_INFFIRSTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['informant_first_name'] = $object->$member;

    $member = 'ICF_INFLASTNAME_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['informant_last_name'] = $object->$member;

    $member = 'ICF_INFADD_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $parts = explode( ' ', trim( $object->$member ), 2 );
      $entry['informant_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $entry['informant_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $member = 'ICF_INFADD2_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['informant_address_other'] = $object->$member;

    $member = 'ICF_INFCITY_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
      $entry['informant_city'] = $object->$member;

    $member = 'ICF_INFPROVINCE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->$member );
      if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->$member );
      if( !is_null( $db_region ) ) $entry['informant_region_id'] = $db_region->id;
    }

    $member = 'ICF_INFPOSTALCODE_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $postcode = trim( $object->$member );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
      $entry['informant_postcode'] = $postcode;
    }

    $member = 'ICF_INFTEL_COM';
    if( property_exists( $object, $member ) && 0 < strlen( $object->$member ) )
    {
      $phone = preg_replace( '/[^0-9]/', '', $object->$member );
      $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
      $entry['informant_phone'] = $phone;
    }

    $member = 'ICF_ANSW_COM';
    $entry['informant_continue'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $member = 'ICF_HCNUMB_COM';
    $entry['health_card'] =
      property_exists( $object, $member ) && 1 == preg_match( '/y|yes|true|1/i', $object->$member ) ? 1 : 0;

    $db_participant->save();

    // PDF form
    $member = 'pdfForm';
    if( property_exists( $object, $member ) )
    { // if a form is included we need to send the request to mastodon
      $form = $object->$member;
      // TODO: add to form system
    }
  }

  /**
   * A list of participant/object pairs used for processing.  Each element contains and associative
   * array with two elements, a participant record and the object used for processing.
   * @var array( array( 'participant', 'object' ) )
   * @access private
   */
  private $object_list = array();
}
