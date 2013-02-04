<?php
/**
 * phone_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget phone list
 */
class phone_list extends \cenozo\ui\widget\phone_list
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
    
    // only allow higher than first tier roles to make direct calls
    $this->set_variable( 'allow_connect',
                         1 < lib::create( 'business\session' )->get_role()->tier );
    $this->set_variable( 'sip_enabled',
      lib::create( 'business\voip_manager' )->get_sip_enabled() );
  }
}
