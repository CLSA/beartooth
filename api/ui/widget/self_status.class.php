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
class self_status extends \cenozo\ui\push\self_status
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

    $this->set_variable( 'sip_enabled', bus\voip_manager::self()->get_sip_enabled() );
    $this->set_variable( 'on_call', !is_null( bus\voip_manager::self()->get_call() ) );
  }
}
?>
