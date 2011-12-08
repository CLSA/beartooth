<?php
/**
 * appointment_calendar.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget appointment calendar
 * 
 * @package beartooth\ui
 */
class appointment_calendar extends base_calendar
{
  /**
   * Constructor
   * 
   * Defines all variables required by the appointment calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
    $this->set_heading( 'Appointments for '.lib::create( 'business\session' )->get_site()->name );
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
    $this->set_variable( 'editable', true );
  }
}
?>
