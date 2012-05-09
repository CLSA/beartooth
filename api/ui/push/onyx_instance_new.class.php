<?php
/**
 * onyx_instance_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx_instance new
 *
 * Create a new onyx_instance.
 * @package beartooth\ui
 */
class onyx_instance_new extends \cenozo\ui\push\base_new
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
      throw lib::create( 'exception\notice',
        'The onyx instance\'s user name cannot be left blank.', __METHOD__ );
    else if( !$columns['password'] )
      throw lib::create( 'exception\notice',
        'You must provide a password at least 6 characters long.', __METHOD__ );
    else if( 6 > strlen( $columns['password'] ) )
      throw lib::create( 'exception\notice',
        'Passwords must be at least 6 characters long.', __METHOD__ );
    else if( 'password' == $columns['password'] )
      throw lib::create( 'exception\notice',
        'You cannot choose "password" as a password.', __METHOD__ );
    
    $db_interviewer_user = $columns['interviewer_user_id']
             ? lib::create( 'database\user', $columns['interviewer_user_id'] )
             : NULL;
    $db_site = lib::create( 'database\site', $columns['site_id'] );
    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'onyx' );

    // now create the user and add onyx access to it
    $args = array( 'columns' =>
              array(
                'name' => $columns['username'],
                'first_name' => $db_site->name.' onyx instance',
                'last_name' => $columns['username'],
                'active' => true,
                'role_id' => $db_role->id,
                'site_id' => $db_site->id ) );
    $operation = lib::create( 'ui\push\user_new', $args );
    
    $operation->finish();
    
    // replace the username argument with the newly created user id for the new onyx instance
    $class_name = lib::get_class_name( 'database\user' );
    $db_user = $class_name::get_unique_record( 'name', $columns['username'] );
    unset( $this->arguments['columns']['username'] );
    unset( $this->arguments['columns']['password'] );
    $this->arguments['columns']['user_id'] = $db_user->id;

    // set user's password
    lib::create( 'business\ldap_manager' )->set_user_password(
      $db_user->name, $columns['password'] );

    parent::finish();
  }
}
?>
