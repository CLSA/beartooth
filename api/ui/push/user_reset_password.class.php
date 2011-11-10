<?php
/**
 * user_reset_password.class.php
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
 * Resets a user's password.
 * 
 * @package beartooth\ui
 */
class user_reset_password extends base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The widget's subject.
   * @param array $args Push arguments
   * @throws exception\argument
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'reset_password', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $db_user = $this->get_record();
    $ldap_manager = bus\ldap_manager::self();
    $ldap_manager->set_user_password( $db_user->name, 'password' );
  }
}
?>
