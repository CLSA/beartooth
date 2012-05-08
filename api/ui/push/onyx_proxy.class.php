<?php
/**
 * onyx_proxy.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx proxy
 * 
 * Allows Onyx to update proxy and interview details
 * @package beartooth\ui
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
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $participant_class_name = lib::create( 'database\participant' );
    $region_class_name = lib::create( 'database\address' );
    $onyx_instance_class_name = lib::create( 'database\address' );

    $db_onyx_user = lib::create( 'business\session' )->get_user();
    $db_onyx_instance =
      $onyx_instance_class_name::get_unique_record( 'user_id', $db_onyx_user->id );
    $db_user = $db_onyx_instance->get_interviewer_user();

    // get the body of the request
    $data = json_decode( http_get_request_body() );

    // loop through the proxy array, if everything works then send the data to
    // Mastodon as a new alternate, if anything goes wrong then send it into the
    // data entry system
    foreach( $data->Consent as $proxy_list )
    {
      foreach( get_object_vars( $proxy_list ) as $uid => $proxy_data )
      {
        $entry = array();
        $object_vars = get_object_vars( $proxy_data );

        $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
        if( is_null( $db_participant ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );
        $entry['uid'] = $db_participant->uid;

        if( !array_key_exists( 'timeEnd', $object_vars ) )
          throw lib::create( 'exception\argument',
            'timeEnd', NULL, __METHOD__ );
        $date = util::get_datetime_object( $proxy_data->timeEnd )->format( 'Y-m-d' );

        $var_name = 'ICF_IDPROXY_COM';
        if( !array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $use_proxy = 1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name );
        $entry['proxy'] = $use_proxy;

        $var_name = 'ICF_OKPROXY_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['already_identified'] =
            1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name );
        else $entry['already_identified'] = false;

        $var_name = 'ICF_PXFIRSTNAME_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_first_name'] = $proxy_data->$var_name;

        $var_name = 'ICF_PXLASTNAME_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_last_name'] = $proxy_data->$var_name;

        $var_name = 'ICF_PXADD_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_street_name'] = $proxy_data->$var_name;

        $var_name = 'ICF_PXADD2_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_address_other'] = $proxy_data->$var_name;

        $var_name = 'ICF_PXCITY_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_city'] = $proxy_data->$var_name;

        $var_name = 'ICF_PXPROVINCE_COM';
        if( array_key_exists( $var_name, $object_vars ) )
        {
          $db_region =
            $region_class_name::get_unique_record( 'abbreviation', $proxy_data->$var_name );
          if( is_null( $db_region ) )
            $db_region =
              $region_class_name::get_unique_record( 'name', $proxy_data->$var_name );

          if( !is_null( $db_region ) )
            $entry['proxy_region_id'] = $db_region->id;
        }

        $var_name = 'ICF_PXPOSTALCODE_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_postcode'] = $proxy_data->$var_name;

        $var_name = 'ICF_PXTEL_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['proxy_phone'] = $proxy_data->$var_name;

        $var_name = 'ICF_PRXINF_COM';
        if( !array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $entry['informant'] = $proxy_data->$var_name;

        $var_name = 'ICF_PRXINFSM_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $entry['same_as_proxy'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFFIRSTNAME_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_first_name'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFLASTNAME_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_last_name'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFADD_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_street_name'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFADD2_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_address_other'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFCITY_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_city'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFPROVINCE_COM';
        if( array_key_exists( $var_name, $object_vars ) )
        {
          $db_region =
            $region_class_name::get_unique_record( 'abbreviation', $proxy_data->$var_name );
          if( is_null( $db_region ) )
            $db_region =
              $region_class_name::get_unique_record( 'name', $proxy_data->$var_name );

          if( !is_null( $db_region ) )
            $entry['informant_region_id'] = $db_region->id;
        }

        $var_name = 'ICF_INFPOSTALCODE_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_postcode'] = $proxy_data->$var_name;

        $var_name = 'ICF_INFTEL_COM';
        if( array_key_exists( $var_name, $object_vars ) )
          $entry['informant_phone'] = $proxy_data->$var_name;

        $var_name = 'ICF_ANSW_COM';
        if( !array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $entry['informant_continue'] =
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name );

        $var_name = 'ICF_TEST_COM';
        if( !array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $db_participant->physical_tests_continue =
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name );

        $var_name = 'ICF_SAMP_COM';
        if( !array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $db_participant->consent_to_draw_blood_continue =
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name );

        $var_name = 'ICF_HCNUMB_COM';
        if( !array_key_exists( $var_name, $object_vars ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Proxy form missing variable "%s".', $var_name, __METHOD__ ) );
        $entry['health_card'] =
          1 == preg_match( '/y|yes|true|1/i', $proxy_data->$var_name );

        // update the participant
        $db_participant->save();

        // now pass on the data to Mastodon
        $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
        $args = array(
          'columns' => array(
            'complete' => false,
            'date' => util::get_datetime_object()->format( 'Y-m-d' ) ),
          'entry' => $entry,
          'noid' => array(
            'user.name' => $db_user->name ) );
        if( array_key_exists( 'pdfForm', $object_vars ) )
          $args['form'] = $proxy_data->pdfForm;
        $mastodon_manager->push( 'alternate', 'new', $args );
      }
    }
  }
}
?>
