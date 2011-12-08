<?php
/**
 * onyx_instance_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log;

/**
 * push: onyx_instance delete
 * 
 * @package beartooth\ui
 */
class onyx_instance_delete extends base_delete
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
    // make sure that only admins can remove onyx instances not belonging to the current site
    $session = lib::create( 'business\session' );
    $is_administrator = 'administrator' == $session->get_role()->name;

    if( !$is_administrator && $session->get_site()->id != $this->get_record()->site_id )
      throw lib::create( 'exception\notice',
        'You do not have access to remove this onyx instance.', __METHOD__ );
    
    $db_user = $this->get_record()->get_user();

    if( 1 < count( $db_user->get_access_count() ) )
      throw lib::create( 'exception\notice',
        'Cannot delete the onyx instance since it holds more than one role.', __METHOD__ );

    parent::finish();

    // now delete the user and access
    $db_access = current( $db_user->get_access_list() );
    if( $db_access )
    {
      $operation = lib::create( 'ui\push\access_delete', array( 'id' => $db_access->id ) );
      $operation->finish();
    }
    $operation = lib::create( 'ui\push\user_delete', array( 'id' => $db_user->id ) );
    $operation->finish();
  }
}
?>
