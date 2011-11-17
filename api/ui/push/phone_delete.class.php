<?php
/**
 * phone_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: phone delete
 * 
 * @package beartooth\ui
 */
class phone_delete extends base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the phone id with a unique key
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $this->get_record()->get_participant()->uid;
    $args['noid']['phone.rank'] = $this->get_record()->rank;
    
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
    $mastodon_manager->push( 'phone', 'delete', $args );
  }
}
?>
