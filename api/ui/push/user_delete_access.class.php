<?php
/**
 * user_delete_access.class.php
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
 * push: user delete_access
 * 
 * @package beartooth\ui
 */
class user_delete_access extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'access', $args );
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

    // replace the user id with a unique key
    $db_user = new db\user( $this->get_argument('id') );
    unset( $args['id'] );
    $args['noid']['user.name'] = $db_user->name;
    
    // replace the access id with identifying names of the unique key
    $db_access = new db\access( $this->get_argument('remove_id') );
    unset( $args['remove_id'] );
    $args['noid']['role.name'] = $db_access->get_role()->name;
    $args['noid']['site.name'] = $db_access->get_site()->name;
    $args['noid']['site.cohort'] = 'comprehensive';
    
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'user', 'delete_access', $args );
  }
}
?>
