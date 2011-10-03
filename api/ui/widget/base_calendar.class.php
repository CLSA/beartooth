<?php
/**
 * base_calendar.class.php
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
 * base widget for all calendars
 * 
 * @package beartooth\ui
 */
abstract class base_calendar extends \beartooth\ui\widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the base calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The calendar's subject.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'calendar', $args );
  }
}
?>
