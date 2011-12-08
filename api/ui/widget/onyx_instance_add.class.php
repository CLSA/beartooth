<?php
/**
 * onyx_instance_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

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

    $type = 3 == lib::create( 'business\session' )->get_role()->tier ? 'enum' : 'hidden';
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
    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;
    
    // create enum arrays
    if( $is_top_tier )
    {
      $sites = array();
      $class_name = lib::get_class_name( 'database\site' );
      foreach( $class_name::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    }
    
    $db_site = $session->get_site();
    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'interviewer' );
    
    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'site_id', '=', $db_site->id );
    $user_mod->where( 'role_id', '=', $db_role->id );
    $interviewers = array( 'NULL' => 'site' );
    $class_name = lib::get_class_name( 'database\user' );
    foreach( $class_name::select( $user_mod ) as $db_user )
      $interviewers[$db_user->id] = $db_user->name;

    // set the view's items
    $this->set_item( 'username', '' );
    $this->set_item( 'password', '' );
    $this->set_item(
      'site_id', $db_site->id, true, $is_top_tier ? $sites : NULL );
    $this->set_item(
      'interviewer_user_id', key( $interviewers ), true, $interviewers, true );

    $this->finish_setting_items();
  }
}
?>
