<?php
/**
 * voip_end_monitor.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: voip end_monitor
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package beartooth\ui
 */
class voip_end_monitor extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'end_monitor', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    lib::create( 'business\voip_manager' )->get_call()->stop_monitoring();
  }
}
?>
