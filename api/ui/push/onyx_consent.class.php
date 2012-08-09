<?php
/**
 * onyx_consent.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx consent
 * 
 * Allows Onyx to update consent and interview details
 */
class onyx_consent extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx', 'consent', $args );
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

    // get the body of the request
    $body = http_get_request_body();
    $data = util::json_decode( $body );

    if( !is_object( $data ) )
      throw lib::create( 'exception\runtime',
        'Unable to decode request body, received: '.print_r( $body, true ), __METHOD__ );

    // loop through the consent array
    foreach( $data->Consent as $consent_list )
    {
      foreach( get_object_vars( $consent_list ) as $uid => $consent_data )
      {
        $object_vars = get_object_vars( $consent_data );

        $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
        if( is_null( $db_participant ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );

        if( !array_key_exists( 'ConclusiveStatus', $object_vars ) )
          throw lib::create( 'exception\argument',
            'ConclusiveStatus', NULL, __METHOD__ );
        if( 'CONSENT' == $consent_data->ConclusiveStatus ) $event = 'written accept';
        else if( 'RETRACT' == $consent_data->ConclusiveStatus ) $event = 'retract';
        else $event = 'withdraw';

        // try timeEnd, if null then try timeStart, if null then use today's date
        $var_name = 'timeEnd';
        if( array_key_exists( 'timeEnd', $object_vars ) &&
            0 < strlen( $consent_data->timeEnd ) )
        {
          $date_obj = util::get_datetime_object( $consent_data->timeEnd );
        }
        else if( array_key_exists( 'timeStart', $object_vars ) &&
                 0 < strlen( $consent_data->timeStart ) )
        {
          $date_obj = util::get_datetime_object( $consent_data->timeStart );
        }
        else
        {
          $date_obj = util::get_datetime_object();
        }
        $date = $date_obj->format( 'Y-m-d' );

        // update the draw blood consent if it is provided
        if( array_key_exists( 'PCF_CSTSAMP_COM', $object_vars ) )
        {
          $db_participant->consent_to_draw_blood =
            1 == preg_match( '/y|yes|true|1/i', $consent_data->PCF_CSTSAMP_COM ) ? 'YES' : 'NO';
          $db_participant->save();
        }

        // see if this form already exists
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
            $args['form'] = $consent_data->pdfForm;
          $operation = lib::create( 'ui\push\consent_new', $args );
          $operation->process();
        }
      }
    }
  }
}
?>
