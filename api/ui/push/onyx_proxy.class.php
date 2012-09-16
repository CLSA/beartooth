<?php
/**
 * onyx_proxy.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx proxy
 * 
 * Allows Onyx to update proxy and interview details
 */
class onyx_proxy extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx', 'proxy', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $participant_class_name = lib::create( 'database\participant' );
    $region_class_name = lib::create( 'database\region' );
    $onyx_instance_class_name = lib::create( 'database\onyx_instance' );

    $db_onyx_user = lib::create( 'business\session' )->get_user();
    $db_onyx_instance =
      $onyx_instance_class_name::get_unique_record( 'user_id', $db_onyx_user->id );

    // get the user who is sending the request
    // NOTE: if this is a site instance then there is no interviewer, so use the instance itself
    $db_user = $db_onyx_instance->get_interviewer_user();
    if( is_null( $db_user ) ) $db_user = $db_onyx_instance->get_user();

    // get the body of the request
    $data = util::json_decode( http_get_request_body() );

    // loop through the proxy array, if everything works then send the data to
    // Mastodon as a new alternate, if anything goes wrong then send it into the
    // data entry system
    foreach( $data->Consent as $proxy_list )
    {
      foreach( get_object_vars( $proxy_list ) as $uid => $proxy_data )
      {
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
          $entry['proxy_street_name'] = array_key_exists( 0, $parts ) ? $parts[1] : NULL;
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
        $db_participant->physical_tests_continue =
          array_key_exists( $var_name, $object_vars ) &&
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

        $var_name = 'ICF_SAMP_COM';
        $db_participant->consent_to_draw_blood_continue =
          array_key_exists( $var_name, $object_vars ) &&
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

        $var_name = 'ICF_HCNUMB_COM';
        $entry['health_card'] =
          array_key_exists( $var_name, $object_vars ) &&
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name ) ? 1 : 0;

        // update the participant
        $db_participant->save();

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
      }
    }
  }
}
?>
