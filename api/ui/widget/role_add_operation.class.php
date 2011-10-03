<?php
/**
 * role_add_operation.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget role add_operation
 * 
 * @package beartooth\ui
 */
class role_add_operation extends base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', 'operation', $args );
  }

  /**
   * Overrides the operation list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_operation_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'restricted', '=', true );
    return $this->get_record()->get_operation_count_inverted( $modifier );
  }

  /**
   * Overrides the operation list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_operation_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'restricted', '=', true );
    return $this->get_record()->get_operation_list_inverted( $modifier );
  }
}
?>
