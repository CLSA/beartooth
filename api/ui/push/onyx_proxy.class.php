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

        if( array_key_exists( 'ICF_IDPROXY_COM' ) )
          $columns['proxy'] = preg_match( '/y|yes/i', $proxy_data->ICF_IDPROXY_COM );
        else $columns['proxy'] = false;

        if( array_key_exists( 'ICF_OKPROXY_COM' ) )
          $columns['already_identified'] = preg_match( '/y|yes/i', $proxy_data->ICF_OKPROXY_COM );
        else $columns['already_identified'] = false;

        if( array_key_exists( 'ICF_PXNAME_COM' ) )
          $columns[''] = $proxy_data->ICF_PXNAME_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_PXADD_COM' ) )
          $columns[''] = $proxy_data->ICF_PXADD_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_PXTEL_COM' ) )
          $columns[''] = $proxy_data->ICF_PXTEL_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_INFADD_COM' ) )
          $columns[''] = $proxy_data->ICF_INFADD_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_HCNUMB_COM' ) )
          $columns[''] = $proxy_data->ICF_HCNUMB_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_INFNAME_COM' ) )
          $columns[''] = $proxy_data->ICF_INFNAME_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_INFTEL_COM' ) )
          $columns[''] = $proxy_data->ICF_INFTEL_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_SAMP_COM' ) )
          $columns[''] = $proxy_data->ICF_SAMP_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_ANSW_COM' ) )
          $columns[''] = $proxy_data->ICF_ANSW_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_PRXINF_COM' ) )
          $columns[''] = $proxy_data->ICF_PRXINF_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_PRXINFSM_COM' ) )
          $columns[''] = $proxy_data->ICF_PRXINFSM_COM;
        else $columns[''] = ;

        if( array_key_exists( 'ICF_TEST_COM' ) )
          $columns[''] = $proxy_data->ICF_TEST_COM;
        else $columns[''] = ;


        

        // see if this form already exists
        /* TODO: change code to reflect proxy form instead of consent form
        $consent_mod = lib::create( 'database\modifier' );
        $consent_mod->where( 'event', '=', $event );
        $consent_mod->where( 'date', '=', $date );
        if( 0 == $db_participant->get_consent_count( $consent_mod ) )
        {
          $columns = array( 'participant_id' => $db_participant->id,
                            'date' => $date,
                            'event' => $event,
                            'note' => 'Provided by Onyx.' );
          $args = array( 'columns' => $columns );
          if( array_key_exists( 'pdfForm', $object_vars ) )
            $args['form'] = $proxy_data->pdfForm;
          $operation = lib::create( 'ui\push\consent_new', $args );
          $operation->finish();
        }
        */
      }
    }
  }
}
?>
