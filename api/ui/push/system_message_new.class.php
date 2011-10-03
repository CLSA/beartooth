<?php
/**
 * system_message_new.class.php
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
 * push: system_message new
 *
 * Create a new system_message.
 * @package beartooth\ui
 */
class system_message_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'system_message', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    $columns = $this->get_argument( 'columns' );

    // make sure the title and note are not blank
    if( !array_key_exists( 'title', $columns ) || 0 == strlen( $columns['title'] ) )
      throw new exc\notice( 'The message\'s title cannot be left blank.', __METHOD__ );
    if( !array_key_exists( 'note', $columns ) || 0 == strlen( $columns['note'] ) )
      throw new exc\notice( 'The message\'s note cannot be left blank.', __METHOD__ );

    parent::finish();
  }
}
?>
