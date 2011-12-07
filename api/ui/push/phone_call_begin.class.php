<?php
/**
 * phone_call_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: phone_call begin
 *
 * Assigns a participant to a phone call.
 * @package beartooth\ui
 */
class phone_call_begin extends \beartooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', 'begin', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $session = util::create( 'business\session' );
    $is_interviewer = 'interviewer' == $session->get_role()->name;
    
    $db_phone = util::create( 'database\phone', $this->get_argument( 'phone_id' ) );
    $db_assignment = NULL;

    if( $is_interviewer )
    { // make sure that interviewers are calling their current assignment only
      $db_assignment = $session->get_current_assignment();
  
      if( is_null( $db_assignment ) )
        throw util::create( 'exception\runtime',
          'Interviewer tried to make call without an assignment.', __METHOD__ );

      if( $db_phone->participant_id != $db_assignment->get_interview()->participant_id )
        throw util::create( 'exception\runtime',
          'Interviewer tried to make call to participant who is not currently assigned.', __METHOD__ );
    }
    
    // connect voip to phone
    bus\voip_manager::self()->call( $db_phone );

    if( $is_interviewer )
    { // create a record of the phone call
      $db_phone_call = util::create( 'database\phone_call' );
      $db_phone_call->assignment_id = $db_assignment->id;
      $db_phone_call->phone_id = $db_phone->id;
      $db_phone_call->save();
    }
  }
}
?>
