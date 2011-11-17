<?php
/**
 * access_delete.class.php
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
 * push: access delete
 * 
 * @package beartooth\ui
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

    // replace the access id with a unique key
    $db_access = $this->get_record();
    unset( $args['id'] );
    $args['noid']['user.name'] = $db_access->get_user()->name;
    $args['noid']['role.name'] = $db_access->get_role()->name;
    $args['noid']['site.name'] = $db_access->get_site()->name;
    $args['noid']['site.cohort'] = 'comprehensive';

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
    $mastodon_manager->push( 'access', 'delete', $args );
  }
}
?>
