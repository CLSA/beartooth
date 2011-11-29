<?php
/**
 * main.class.php
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
 * Class that manages variables in main user interface template.
 * 
 * @package beartooth\ui
 */
class main extends \cenozo\ui\main
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public static function get_variables()
  {
    $session = bus\session::self();
    $variables = parent::get_variables();
    $variables['show_menu'] = 'interviewer' != $session->get_role()->name ||
                              is_null( $session->get_current_assignment() );
    return $variables;
  }
}
?>
