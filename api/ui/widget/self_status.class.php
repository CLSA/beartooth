<?php
/**
 * self_status.class.php
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
 * widget self status
 * 
 * @package beartooth\ui
 */
class self_status extends \beartooth\ui\widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'status', $args );
    $this->show_heading( false );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $this->set_variable( 'sip_enabled', bus\voip_manager::self()->get_sip_enabled() );
    $this->set_variable( 'on_call', !is_null( bus\voip_manager::self()->get_call() ) );
    
    $datetime_obj = util::get_datetime_object();
    $this->set_variable( 'timezone_name', $datetime_obj->format( 'T' ) );
    $this->set_variable( 'timezone_offset',
      util::get_timezone_object()->getOffset( $datetime_obj ) );
  }
}
?>
