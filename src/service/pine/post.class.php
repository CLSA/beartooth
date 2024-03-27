<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\pine;
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
      $data = $this->get_file_as_object();

      // make sure all participants exist
      $input_list = array();
      foreach( $data as $respondent ) $input_list[$respondent->uid] = $respondent;
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
          'data' => $input_list[$db_participant->uid]
        );
      }

      // search for missing uids in the object list
      if( count( $input_list ) != count( $this->object_list ) )
      {
        $missing_list = array_diff( array_keys( $input_list ), array_keys( $this->object_list ) );
        $this->set_data( sprintf( 'The following UIDs are invalid: "%s"', implode( ', ', $missing_list ) ) );
        $this->status->set_code( 306 );
      }
    }
  }


  /**
   * Override parent method since pine is a meta-resource
   */
  protected function execute()
  {
    foreach( $this->object_list as $object )
    {
      $db_participant = $object['participant'];
      $data = $object['data'];

      $interviewing_instance_class_name = lib::get_class_name( 'database\interviewing_instance' );
      $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
      $region_class_name = lib::get_class_name( 'database\region' );
      $application_class_name = lib::get_class_name( 'database\application' );
      $queue_class_name = lib::get_class_name( 'database\queue' );

      $setting_manager = lib::create( 'business\setting_manager' );
      $session = lib::create( 'business\session' );
      $db_application = $session->get_application();
      $db_user = $session->get_user();

      // get the pine instance to tell whether this is a home or site instance
      $db_interviewing_instance = $interviewing_instance_class_name::get_unique_record( 'user_id', $db_user->id );
      if( is_null( $db_interviewing_instance ) )
      {
        throw lib::create( 'exception\runtime',
          sprintf( 'Pine user "%s" is not linked to any pine instance.', $db_user->name ),
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
      $interview_mod->order_desc( 'interview.start_datetime' );
      $interview_list = $db_participant->get_interview_object_list( $interview_mod );
      if( 0 == count( $interview_list ) )
      {
        throw lib::create( 'exception\runtime',
          sprintf( 'Trying to export Pine interview but no matching %s interview can be found.', $interview_type ),
          __METHOD__
        );
      }
      $db_interview = current( $interview_list );
      $db_qnaire = $db_interview->get_qnaire();

      if( property_exists( $data, 'participant' ) )
      {
        foreach( $data->participant as $column => $value ) $db_participant->$column = $value;
        $db_participant->save();
      }

      if( property_exists( $data, 'address' ) )
      {
        $db_address = $db_participant->get_primary_address();
        foreach( $data->address as $column => $value ) $db_address->$column = $value;
        $db_address->save();
      }

      if( property_exists( $data, 'interview' ) )
      {
        $datetime_obj = util::get_datetime_object( $data->interview->datetime );

        $db_interview->interviewing_instance_id = $db_interviewing_instance->id;
        if( property_exists( $data->interview, 'comment_list' ) )
          $db_interview->note = implode( "\n\n", $data->interview->comment_list );

        if( is_null( $db_interview->end_datetime ) )
        {
          // interview and appointment status
          $db_interview->complete( NULL, $datetime_obj ); // this will save the interview record
          $db_participant->repopulate_queue( false );
        }
        else
        {
          // already complete, so just update the interview end datetime
          $db_interview->end_datetime = $datetime_obj;
          $db_interview->save();
        }
      }

      if( property_exists( $data, 'form_list' ) )
      {
        foreach( $data->form_list as $form )
        {
          if( 'CONSENT_GP' == $form->name )
          {
            $json_data = util::json_decode( $form->data );
            $object = current( $json_data->results );
            $paddress = explode( ' ', trim( $object->ProxyAddress ), 2 );
            $iaddress = explode( ' ', trim( $object->InformantAddress ), 2 );

            $form_data = array(
              'from_instance' => 'pine',
              'date' => $json_data->session->end_time,
              'user_id' => $session->get_user()->id,
              'uid' => $db_participant->uid,
              'continue_questionnaires' => $object->DCScontinue_mandatoryField ? 1 : 0,
              'hin_future_access' => $object->AgreeGiveNumber_mandatoryField ? 1 : 0,
              'continue_dcs_visits' => $object->DCScontinue_mandatoryField ? 1 : 0, 
              'proxy_first_name' => 0 < strlen( $object->ProxyFirstName ) ? $object->ProxyFirstName : NULL,
              'proxy_last_name' => 0 < strlen( $object->ProxyLastName ) ? $object->ProxyLastName : NULL,
              // pine never sends international contact information
              'proxy_address_international' => false,
              'proxy_phone_international' => false,
              'proxy_street_number' => array_key_exists( 0, $paddress ) && 0 < strlen( $paddress[0] ) ? $paddress[0] : NULL,
              'proxy_street_name' => array_key_exists( 1, $paddress ) && 0 < strlen( $paddress[1] ) ? $paddress[1] : NULL,
              'proxy_address_other' =>
                property_exists( $object, 'ProxyAddress2' ) && 0 < strlen( $object->ProxyAddress2 ) ?
                $object->ProxyAddress2 :
                NULL,
              'proxy_city' => 0 < strlen( $object->ProxyCity ) ? $object->ProxyCity : NULL,
              'already_identified' => $object->DMalready_mandatoryField ? 1 : 0,
              'same_as_proxy' => $object->informantIsProxy ? 1 : 0,
              'informant_first_name' => 0 < strlen( $object->InformantFirstName ) ? $object->InformantFirstName : NULL,
              'informant_last_name' => 0 < strlen( $object->InformantLastName ) ? $object->InformantLastName : NULL,

              'informant_street_number' => array_key_exists( 0, $iaddress ) && 0 < strlen( $iaddress[0] ) ? $iaddress[0] : NULL,
              'informant_street_name' => array_key_exists( 1, $iaddress ) && 0 < strlen( $iaddress[1] ) ? $iaddress[1] : NULL,

              'informant_address_other' => 0 < strlen( $object->InformantAddress2 ) ? $object->InformantAddress2 : NULL,
              'informant_city' => 0 < strlen( $object->InformantCity ) ? $object->InformantCity : NULL,
              // the form will have a list of files, but CONSENT_GP always has one only
              'data' => current( $form->file_list ) // base64 encoded PDF file
            );

            if( 0 < strlen( $object->ProxyProvince ) )
            {
              $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->ProxyProvince );
              if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->ProxyProvince );
              if( !is_null( $db_region ) ) $form_data['proxy_region_id'] = $db_region->id;
            }

            if( 0 < strlen( $object->ProxyPostalCode ) )
            {
              $postcode = trim( $object->ProxyPostalCode );
              if( 6 == strlen( $postcode ) )
                $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
              $form_data['proxy_postcode'] = $postcode;
            }

            if( 0 < strlen( $object->ProxyTelephone ) )
            {
              $phone = preg_replace( '/[^0-9]/', '', $object->ProxyTelephone );
              $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
              $form_data['proxy_phone'] = $phone;
            }


            if( !$form_data['same_as_proxy'] )
            {
              // pine never sends international contact information (only include this if informant isn't the same as proxy)
              $form_data['informant_address_international'] = false;
              $form_data['informant_phone_international'] = false;
            }

            if( 0 < strlen( $object->InformantProvince ) )
            {
              $db_region = $region_class_name::get_unique_record( 'abbreviation', $object->InformantProvince );
              if( is_null( $db_region ) ) $db_region = $region_class_name::get_unique_record( 'name', $object->InformantProvince );
              if( !is_null( $db_region ) ) $form_data['informant_region_id'] = $db_region->id;
            }

            if( 0 < strlen( $object->InformantPostalCode ) )
            {
              $postcode = trim( $object->InformantPostalCode );
              if( 6 == strlen( $postcode ) )
                $postcode = sprintf( '%s %s', substr( $postcode, 0, 3 ), substr( $postcode, 3 ) );
              $form_data['informant_postcode'] = $postcode;
            }

            if( 0 < strlen( $object->InformantTelephone ) )
            {
              $phone = preg_replace( '/[^0-9]/', '', $object->InformantTelephone );
              $phone = sprintf( '%s-%s-%s', substr( $phone, 0, 3 ), substr( $phone, 3, 3 ), substr( $phone, 6 ) );
              $form_data['informant_phone'] = $phone;
            }

            // we need to repopulate the queue and complete any transactions before continuing
            $queue_class_name::execute_delayed();
            $session->get_database()->complete_transaction();

            // now send all data to mastodon's data entry system
            $curl = curl_init();

            $authentication = sprintf( '%s:%s',
              $setting_manager->get_setting( 'utility', 'username' ),
              $setting_manager->get_setting( 'utility', 'password' )
            );

            // set URL and other appropriate options
            $db_mastodon_application = $application_class_name::get_unique_record( 'name', 'mastodon' );
            curl_setopt( $curl, CURLOPT_URL, sprintf( '%s/api/general_proxy_form', $db_mastodon_application->url ) );
            curl_setopt(
              $curl,
              CURLOPT_HTTPHEADER,
              array( sprintf( 'Authorization:Basic %s', base64_encode( $authentication ) ) )
            );
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
        }
      }
    }

    if( is_null( $this->status->get_code() ) ) $this->status->set_code( 201 );
  }


  /**
   * A list of participant/object pairs used for processing.  Each element contains and associative
   * array with two elements, a participant record and the object used for processing.
   * @var array( array( 'uid', 'object' ) )
   * @access private
   */
  private $object_list = array();
}
