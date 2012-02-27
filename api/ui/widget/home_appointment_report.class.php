<?php
/**
 * home_appointment_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget home_appointment report
 * 
 * @package beartooth\ui
 */
class home_appointment_report extends base_report
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
    parent::__construct( 'home_appointment', $args );

    $this->set_variable( 'description',
      'This report provides a list of all incomplete appointments including the '.
      'date and time of the appointment, the participant\'s name, unique identifier, '.
      'address and phone number(s).' );
  }

  /**
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    $this->finish_setting_parameters();
  }
}
?>
