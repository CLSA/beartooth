<?php
/**
 * consent_new.class.php
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
 * push: consent new
 *
 * Create a new consent.
 * @package beartooth\ui
 */
class consent_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'consent', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // make sure the date column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'date', $columns ) || 0 == strlen( $columns['date'] ) )
      throw util::create( 'exception\notice', 'The date cannot be left blank.', __METHOD__ );

    $args = $this->arguments;
    unset( $args['columns']['participant_id'] );

    // replace the participant id with a unique key
    $db_participant = util::create( 'database\participant', $columns['participant_id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // no errors, go ahead and make the change
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
    $mastodon_manager->push( 'consent', 'new', $args );
  }
}
?>
