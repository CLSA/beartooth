<?php
/**
 * note_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Extends the parent class to send machine requests.
 * @package beartooth\ui
 */
class note_new extends \cenozo\ui\push\note_new
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

    // only send machine requests for participant notes
    if( 'participant' == $this->get_argument( 'category' ) )
    {
      $this->set_machine_request_enabled( true );
      $this->set_machine_request_url( MASTODON_URL );
    }
  }
}
?>