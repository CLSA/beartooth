<?php
/**
 * push.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * The base class of all push operations
 * 
 * @package beartooth\ui
 */
abstract class push extends operation
{
  /**
   * Constructor
   * 
   * Defines all variables available in push operations
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject of the operation.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the push operation.
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( 'push', $subject, $name, $args );
  }
}
?>
