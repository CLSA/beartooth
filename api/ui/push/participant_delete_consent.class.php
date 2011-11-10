<?php
/**
 * participant_delete_consent.class.php
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
 * push: participant delete_consent
 * 
 * @package beartooth\ui
 */
class participant_delete_consent extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'consent', $args );
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
    $db_participant = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // replace the remove_id with a unique key
    $db_consent = new db\consent( $this->get_argument( 'remove_id' ) );
    unset( $args['remove_id'] );
    // this is only actually half of the key, the other half is provided by the participant above
    $args['noid']['consent.event'] = $db_consent->event;
    $args['noid']['consent.date'] = $db_consent->date;

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'participant', 'delete_consent', $args );
  }
}
?>
