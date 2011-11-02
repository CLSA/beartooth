<?php
/**
 * onyx_instance_new.class.php
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
 * push: onyx_instance new
 *
 * Create a new onyx_instance.
 * @package beartooth\ui
 */
class onyx_instance_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx_instance', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure that the username is not empty
    $columns = $this->get_argument( 'columns' );
    if( !$columns['username'] )
      throw new exc\notice( 'The onyx instance\'s user name cannot be left blank.', __METHOD__ );
    
    $db_user = $columns['interviewer_user_id']
             ? new db\user( $columns['interviewer_user_id'] )
             : NULL;
    $db_site = $columns['site_id'];

    // create the user
    $db_user = new db\user();
    $db_user->name = $columns['username'];
    $db_user->first_name = 'onyx instance';
    $db_user->last_name = sprintf( '%s@%s', $db_user ? $db_user->name : 'site' , $db_site->name );
    $db_user->active = true;
    $db_user->save();
    
    // replace the username argument with the newly created user id for the new onyx instance
    unset( $this->arguments['username'] );
    $this->arguments['user_id'] = $db_user->id;

    parent::finish();
  }
}
?>
