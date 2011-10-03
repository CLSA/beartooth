<?php
/**
 * participant_delete_address.class.php
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
 * push: participant delete_address
 * 
 * @package beartooth\ui
 */
class participant_delete_address extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'address', $args );
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

    // replace the participant id with a unique key
    $db_participant = new db\participant( $this->get_argument( 'id' ) );
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // replace the remove_id with a unique key
    $db_address = new db\address( $this->get_argument( 'remove_id' ) );
    unset( $args['remove_id'] );
    // this is only actually half of the key, the other half is provided by the participant above
    $args['noid']['address.rank'] = $db_address->rank;

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'participant', 'delete_address', $args );
  }
}
?>
