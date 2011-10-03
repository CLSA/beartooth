<?php
/**
 * participant_status_report.class.php
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
class participant_status_report extends base_report
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
    parent::__construct( 'participant_status', $args );

    $this->add_restriction( 'qnaire' );

    $this->set_variable( 'description',
      'This report provides totals of various status types.  Currently, only an overall '.
      'report can be generated.  In the near future reports for a single day or date range can be '.
      'generated.  Populations are broken down by province and various call, participant and '.
      'consent statuses.' );
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
