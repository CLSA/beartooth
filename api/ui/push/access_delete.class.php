<?php
/**
 * access_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * push: access delete
 * 
 * @package sabretooth\ui
 */
class access_delete extends base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'access', $args );
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

    // replace the user's id with their name
    $db_access = new db\access( $this->get_argument('id') );
    unset( $args['id'] );
    $args['user'] = $db_access->get_user()->name;
    $args['role'] = $db_access->get_role()->name;
    $args['site'] = $db_access->get_site()->name;
    $args['cohort'] = 'tracking';

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'access', 'delete', $args );
  }
}
?>
