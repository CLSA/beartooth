<?php
/**
 * user_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: user delete
 * 
 * @package beartooth\ui
 */
class user_delete extends \cenozo\ui\push\user_delete
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );

    // don't call the parent method if the request came from mastodon
    if( 'mastodon' != $this->get_machine_application_name() ) $this->set_validate_access( false );
  }
}
?>
