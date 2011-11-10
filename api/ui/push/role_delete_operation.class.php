<?php
/**
 * role_delete_operation.class.php
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
 * push: role delete_operation
 * 
 * @package beartooth\ui
 */
class role_delete_operation extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', 'operation', $args );
  }

  /**
   * Overrides the parent method since no operation_delete method exists.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $this->get_record()->remove_operation( $this->get_argument( 'remove_id' ) );
  }
}
?>
