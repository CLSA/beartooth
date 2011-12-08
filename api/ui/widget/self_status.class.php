<?php
/**
 * self_status.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget self status
 * 
 * @package beartooth\ui
 */
class self_status extends \cenozo\ui\widget\self_status
{
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $this->set_variable(
      'sip_enabled', lib::create( 'business\voip_manager' )->get_sip_enabled() );
    $this->set_variable(
      'on_call', !is_null( lib::create( 'business\voip_manager' )->get_call() ) );
  }
}
?>
