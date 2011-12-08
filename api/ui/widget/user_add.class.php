<?php
/**
 * user_add.class.php
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
 * widget user add
 * 
 * @package beartooth\ui
 */
class user_add extends \cenozo\ui\widget\user_add
{
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $session = util::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;

    // create enum arrays
    $modifier = util::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'onyx' );
    $modifier->where( 'tier', '<=', $session->get_role()->tier );
    $roles = array();
    $class_name = util::get_class_name( 'database\role' );
    foreach( $class_name::select( $modifier ) as $db_role ) $roles[$db_role->id] = $db_role->name;
    
    // set the view's items
    $this->set_item( 'role_id', array_search( 'interviewer', $roles ), true, $roles );

    $this->finish_setting_items();
  }
}
?>
