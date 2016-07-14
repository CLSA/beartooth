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
    $member = 'ConclusiveStatus';
    $member = 'timeEnd';
    $member = 'timeEnd';
    $member = 'timeStart';
    $member = 'timeStart';
    $member = 'PCF_CSTSAMP_COM';
    $member = 'ADM_NUMB_COM';
    $member = 'ADM_NUMB_NB_COM';
    $member = 'ADM_PROV_COM';
    $member = 'PCF_CSTGVDB_COM';
    $member = 'pdfForm';
  }

  /**
   * TODO: document
   */
  private function process_hin( $db_participant, $object )
  {
  }

  /**
   * TODO: document
   */
  private function process_participant( $db_participant, $object )
  {
    $onyx_instance_class_name = lib::get_class_name( 'database\onyx_instance' );
    $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

    $member = 'Admin.Interview.endDate';
    $datetime = util::get_datetime_object( property_exists( $object, $member ) ? $object->$member : NULL );

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
      if( is_null( $db_last_consent ) ||
          $db_last_consent->accept != $value ||
          $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime;
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
      if( is_null( $db_last_consent ) ||
          $db_last_consent->accept != $value ||
          $db_last_consent->written != true )
      {
        $db_consent = lib::create( 'database\consent' );
        $db_consent->participant_id = $db_participant->id;
        $db_consent->consent_type_id = $db_consent_type->id;
        $db_consent->accept = $value;
        $db_consent->written = true;
        $db_consent->datetime = $datetime;
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
        $db_interview->complete( NULL, $datetime );
      }
    }
  }

  /**
   * TODO: document
   */
  private function process_proxy( $db_participant, $object )
  {
/*
    $object_vars = get_object_vars( $proxy_data );
    if( 1 >= count( $object_vars ) ) continue;

    $noid = array( 'user.name' => $db_user->name );
    $entry = array();

    $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
    if( is_null( $db_participant ) )
      throw lib::create( 'exception\runtime',
        sprintf( 'Participant UID "%s" does not exist.', $uid ),
        __METHOD__ );
    $entry['uid'] = $db_participant->uid;

    $db_data_collection = $db_participant->get_data_collection();
    if( is_null( $db_data_collection ) )
    {
      $db_data_collection = lib::create( 'database\data_collection' );
      $db_data_collection->participant_id = $db_participant->id;
    }

    // try timeEnd, if null then try timeStart
    $var_name = 'timeEnd';
    if( !array_key_exists( $var_name, $object_vars ) ||
        0 == strlen( $proxy_data->$var_name ) )
    {
      $var_name = 'timeStart';
      if( !array_key_exists( $var_name, $object_vars ) ||
          0 == strlen( $proxy_data->$var_name ) )
        throw lib::create( 'exception\argument',
          $var_name, NULL, __METHOD__ );
    
    }
    $entry['date'] = util::get_datetime_object( $proxy_data->$var_name )->format( 'Y-m-d' );

    $var_name = 'ICF_IDPROXY_COM';
    $entry['proxy'] =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_OKPROXY_COM';
    $entry['already_identified'] =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_PXFIRSTNAME_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['proxy_first_name'] = $proxy_data->$var_name;

    $var_name = 'ICF_PXLASTNAME_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['proxy_last_name'] = $proxy_data->$var_name;

    $var_name = 'ICF_PXADD_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $parts = explode( ' ', trim( $proxy_data->$var_name ), 2 );
      $entry['proxy_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $entry['proxy_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $var_name = 'ICF_PXADD2_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['proxy_address_other'] = $proxy_data->$var_name;

    $var_name = 'ICF_PXCITY_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['proxy_city'] = $proxy_data->$var_name;

    $var_name = 'ICF_PXPROVINCE_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $db_region =
        $region_class_name::get_unique_record( 'abbreviation', $proxy_data->$var_name );
      if( is_null( $db_region ) )
        $db_region =
          $region_class_name::get_unique_record( 'name', $proxy_data->$var_name );

      if( !is_null( $db_region ) )
        $noid['proxy_region.abbreviation'] = $db_region->abbreviation;
    }

    $var_name = 'ICF_PXPOSTALCODE_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $postcode = $proxy_data->$var_name;
      $postcode = trim( $postcode );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s',
                             substr( $postcode, 0, 3 ),
                             substr( $postcode, 3 ) );
      $entry['proxy_postcode'] = $postcode;
    }

    $var_name = 'ICF_PXTEL_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $phone = $proxy_data->$var_name;
      $phone = preg_replace( '/[^0-9]/', '', $phone );
      $phone = sprintf( '%s-%s-%s',
                        substr( $phone, 0, 3 ),
                        substr( $phone, 3, 3 ),
                        substr( $phone, 6 ) );
      $entry['proxy_phone'] = $phone;
    }

    $var_name = 'ICF_PRXINF_COM';
    $entry['informant'] =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_PRXINFSM_COM';
    $entry['same_as_proxy'] =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_INFFIRSTNAME_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['informant_first_name'] = $proxy_data->$var_name;

    $var_name = 'ICF_INFLASTNAME_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['informant_last_name'] = $proxy_data->$var_name;

    $var_name = 'ICF_INFADD_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $parts = explode( ' ', trim( $proxy_data->$var_name ), 2 );
      $entry['informant_street_number'] = array_key_exists( 0, $parts ) ? $parts[0] : NULL;
      $entry['informant_street_name'] = array_key_exists( 1, $parts ) ? $parts[1] : NULL;
    }

    $var_name = 'ICF_INFADD2_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['informant_address_other'] = $proxy_data->$var_name;

    $var_name = 'ICF_INFCITY_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
      $entry['informant_city'] = $proxy_data->$var_name;

    $var_name = 'ICF_INFPROVINCE_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $db_region =
        $region_class_name::get_unique_record( 'abbreviation', $proxy_data->$var_name );
      if( is_null( $db_region ) )
        $db_region =
          $region_class_name::get_unique_record( 'name', $proxy_data->$var_name );

      if( !is_null( $db_region ) )
        $noid['informant_region.abbreviation'] = $db_region->abbreviation;
    }

    $var_name = 'ICF_INFPOSTALCODE_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $postcode = $proxy_data->$var_name;
      $postcode = trim( $postcode );
      if( 6 == strlen( $postcode ) )
        $postcode = sprintf( '%s %s',
                             substr( $postcode, 0, 3 ),
                             substr( $postcode, 3 ) );
      $entry['informant_postcode'] = $postcode;
    }

    $var_name = 'ICF_INFTEL_COM';
    if( array_key_exists( $var_name, $object_vars ) && 0 < strlen( $proxy_data->$var_name ) )
    {
      $phone = $proxy_data->$var_name;
      $phone = preg_replace( '/[^0-9]/', '', $phone );
      $phone = sprintf( '%s-%s-%s',
                        substr( $phone, 0, 3 ),
                        substr( $phone, 3, 3 ),
                        substr( $phone, 6 ) );
      $entry['informant_phone'] = $phone;
    }

    $var_name = 'ICF_ANSW_COM';
    $entry['informant_continue'] =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_TEST_COM';
    $db_data_collection->physical_tests_continue =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_SAMP_COM';
    $db_data_collection->draw_blood_continue =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    $var_name = 'ICF_HCNUMB_COM';
    $entry['health_card'] =
      array_key_exists( $var_name, $object_vars ) &&
      1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

    // now pass on the data to Mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $args = array(
      'columns' => array(
        'from_onyx' => 1,
        'complete' => 0,
        'date' => $entry['date'] ),
      'entry' => $entry,
      'noid' => $noid );
    if( array_key_exists( 'pdfForm', $object_vars ) )
      $args['form'] = $proxy_data->pdfForm;
    $mastodon_manager->push( 'proxy_form', 'new', $args );

    // update the participant and data_collection
    // NOTE: these calls need to happen AFTER the mastodon push operation above, otherwise
    // a database lock will prevent the operation from completing
    $db_participant->save();
    $db_data_collection->save();
*/
  }

  /**
   * A list of participant/object pairs used for processing.  Each element contains and associative
   * array with two elements, a participant record and the object used for processing.
   * @var array( array( 'participant', 'object' ) )
   * @access private
   */
  private $object_list = array();
}
