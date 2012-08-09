<?php
/**
 * self_status.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget self status
 */
class self_status extends \cenozo\ui\widget\self_status
{
  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $voip_manager = lib::create( 'business\voip_manager' );
    $this->set_variable( 'sip_enabled', $voip_manager->get_sip_enabled() );
    $this->set_variable( 'on_call', $voip_manager->get_call() );
  }
}
?>
