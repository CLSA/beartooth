<?php
/**
 * voip_call.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\business;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * The details of a voip call.
 */
class voip_call extends \cenozo\business\voip_call
{
  /**
   * Starts recording (monitoring) the call.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $filename The file name the recorded call is to be saved under.
   * @access public
   */
  public function start_monitoring( $filename )
  {
    // prepend the assigned participant's uid/, or if not assigned then the user's name/datetime
    $session = lib::create( 'business\session' );
    $db_assignment = $session->get_current_assignment();

    if( !is_null( $db_assignment ) )
    {
      $filename = sprintf( '%s/%s', $db_assignment->get_interview()->get_participant()->uid, $filename );
    }
    else
    {
      $filename = sprintf( '%s/%s/%s',
                           $session->get_user()->name,
                           util::get_datetime_object()->format( 'Y-m-d' ),
                           $filename );
    }
    
    parent::start_monitoring( $filename );
  }
}
