<?php
/**
 * voip_begin_monitor.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: voip begin_monitor
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package beartooth\ui
 */
class voip_begin_monitor extends \beartooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'begin_monitor', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    lib::create( 'business\voip_manager' )->get_call()->start_monitoring(
      lib::create( 'business\session' )->get_current_assignment()->get_current_token() );
  }
}
?>
