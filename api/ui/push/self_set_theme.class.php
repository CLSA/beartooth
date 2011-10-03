<?php
/**
 * self_set_theme.class.php
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
 * push: self set_theme
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package beartooth\ui
 */
class self_set_theme extends \beartooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'set_theme', $args );
    $this->theme_name = $this->get_argument( 'theme' ); // must exist
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $session = bus\session::self();
    $session->get_user()->theme = $this->theme_name;
    $session->get_user()->save();
  }

  /**
   * The name of the theme to set.
   * @var string
   * @access protected
   */
  protected $theme_name = NULL;
}
?>
