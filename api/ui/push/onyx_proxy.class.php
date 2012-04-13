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

    // get the body of the request
    $data = json_decode( http_get_request_body() );

    // loop through the proxy array
    foreach( $data->Consent as $proxy_list )
    {
      foreach( get_object_vars( $proxy_list ) as $uid => $proxy_data )
      {
        $object_vars = get_object_vars( $proxy_data );

        $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
        if( is_null( $db_participant ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );

        if( !array_key_exists( 'timeEnd', $object_vars ) )
          throw lib::create( 'exception\argument',
            'timeEnd', NULL, __METHOD__ );
        $date = util::get_datetime_object( $proxy_data->timeEnd )->format( 'Y-m-d' );

        $columns = array();

        if( array_key_exists( 'ICF_IDPROXY_COM', $object_vars ) )
          $columns['proxy'] = preg_match( '/y|yes/i', $proxy_data->ICF_IDPROXY_COM );
        else $columns['proxy'] = false;

        // TODO: translate variables into proxy (columns), address and phone arrays

        // now pass on the data to Mastodon
        $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
        $args = array(
          'columns' => $columns,
          'address' => $address,
          'phone' => $phone,
          'noid' => array(
            'participant.uid' => $db_participant->uid ) );
        if( array_key_exists( 'pdfForm', $object_vars ) )
          $args['form'] = $proxy_data->pdfForm;
        $mastodon_manager->push( 'proxy_form', 'new', $args );
      }
    }
  }
}
?>
