<?php
/**
 * user_new.class.php
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
 * push: user new
 *
 * Create a new user.
 * @package beartooth\ui
 */
class user_new extends \cenozo\ui\push\user_new
{
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   * @throws exception\notice
   */
  public function finish()
  {
    parent::finish();

    // need this for mastodon, below
    $args = $this->arguments;

    if( !is_null( $this->site_id ) && !is_null( $this->role_id ) )
    { // add the initial role to the new user
      $db_site = util::create( 'database\site', $this->site_id );
      $db_role = util::create( 'database\role', $this->role_id );

      // add the site, cohort and role to the arguments for mastodon
      $args['noid']['site.name'] = $db_site->name;
      $args['noid']['site.cohort'] = 'comprehensive';
      $args['noid']['role.name'] = $db_role->name;
    }

    // now send the same request to mastodon
    $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
    $mastodon_manager->push( 'user', 'new', $args );
  }
}
?>
