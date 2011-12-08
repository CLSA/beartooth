<?php
/**
 * voip_play.class.php
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
 * push: voip play
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package beartooth\ui
 */
class voip_play extends \beartooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'play', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    util::create( 'business\voip_manager' )->get_call()->play_sound(
      $this->get_argument( 'sound' ),
      $this->get_argument( 'volume' ) );
  }
}
?>
