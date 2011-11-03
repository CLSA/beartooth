<?php
/**
 * onyx_instance_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget onyx_instance add
 * 
 * @package beartooth\ui
 */
class onyx_instance_add extends base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx_instance', 'add', $args );
    
    // define all columns defining this record

    $type = 'administrator' == bus\session::self()->get_role()->name ? 'enum' : 'hidden';
    $this->add_item( 'username', 'string', 'Username' );
    $this->add_item( 'password', 'string', 'Password',
      'Passwords must be at least 6 characters long.' );
    $this->add_item( 'site_id', $type, 'Site' );
    $this->add_item( 'interviewer_user_id', 'enum', 'Instance',
      'Determines whether to link this instance to a site or an interviewer.' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    $session = bus\session::self();
    $is_administrator = 'administrator' == $session->get_role()->name;
    
    // create enum arrays
    if( $is_administrator )
    {
      $sites = array();
      foreach( db\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    }
    
    $db_site = $session->get_site();
    $db_role = db\role::get_unique_record( 'name', 'interviewer' );
    
    $user_mod = new db\modifier();
    $user_mod->where( 'site_id', '=', $db_site->id );
    $user_mod->where( 'role_id', '=', $db_role->id );
    $interviewers = array( 'NULL' => 'site' );
    foreach( db\user::select( $user_mod ) as $db_user )
      $interviewers[$db_user->id] = $db_user->name;

    // set the view's items
    $this->set_item( 'username', '' );
    $this->set_item( 'password', '' );
    $this->set_item(
      'site_id', $db_site->id, true, $is_administrator ? $sites : NULL );
    $this->set_item(
      'interviewer_user_id', key( $interviewers ), true, $interviewers, true );

    $this->finish_setting_items();
  }
}
?>
