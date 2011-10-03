<?php
/**
 * site_calendar.class.php
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
 * widget site calendar
 * 
 * @package beartooth\ui
 */
class site_calendar extends base_calendar
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
    $this->set_heading( 'Open appointment slots for '.bus\session::self()->get_site()->name );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    $this->set_variable( 'allow_all_day', false );
    $this->set_variable( 'editable', false );
  }
}
?>
