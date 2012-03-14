<?php
/**
 * onyx_consent.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx consent
 * 
 * Allows Onyx to update consent and interview details
 * @package beartooth\ui
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
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $participant_class_name = lib::create( 'database\participant' );

    // loop through the participants array
    foreach( $this->get_argument( 'Consent' ) as $consent_data )
    {
      if( !array_key_exists( 'Admin.Participant.enrollmentId', $consent_data ) )
        throw lib::create( 'exception\argument',
          'Admin.Participant.enrollmentId', NULL, __METHOD__ );
      $uid = $consent_data['Admin.Participant.enrollmentId'];

      $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
      if( is_null( $db_participant ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );

      if( !array_key_exists( 'accepted', $consent_data ) )
        throw lib::create( 'exception\argument',
          'accepted', NULL, __METHOD__ );
      $event = $consent_data['accepted'] ? 'written accept' : 'written deny';

      if( !array_key_exists( 'timeEnd', $consent_data ) )
        throw lib::create( 'exception\argument',
          'timeEnd', NULL, __METHOD__ );
      $date = util::get_datetime_object( $consent_data['timeEnd'] )->format( 'Y-m-d' );

      // see if this form already exists
      $consent_mod = lib::create( 'database\modifier' );
      $consent_mod->where( 'event', '=', $event );
      $consent_mod->where( 'date', '=', $date );
      if( 0 == $db_participant->get_consent_count( $consent_mod ) )
      {
        $columns = array( 'date' => $date,
                          'event' => $event,
                          'note' => 'Provided by Onyx.' );
        $args = array( 'id' => $db_participant->id,
                       'columns' => $columns );
        if( array_key_exists( 'pdfForm', $consent_data ) )
          $args['form'] = $participant_data['pdfForm'];
        $operation = lib::create( 'ui\push\consent_new', $args );
        $operation->finish();
      }
    }
  }
}
?>
