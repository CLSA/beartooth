<?php
/**
 * coverage_view.class.php
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
 * widget coverage view
 * 
 * @package beartooth\ui
 */
class coverage_view extends base_view
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
    parent::__construct( 'coverage', 'view', $args );

    // create an associative array with everything we want to display about the coverage
    $this->add_item( 'user_id', 'enum', 'User' );
    $this->add_item( 'postcode_mask', 'string', 'Postal Code',
      'Postal codes shorter than 6 letters/numbers long will assume the missing letters/numbers '.
      'are wild cards.' );
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
    
    $db_site = util::create( 'business\session' )->get_site();
    $db_role = db\role::get_unique_record( 'name', 'interviewer' );

    // create enum arrays
    $user_list = array();
    $modifier = util::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    foreach( db\user::select( $modifier ) as $db_user ) $user_list[$db_user->id] = $db_user->name;
    
    $postcode_mask = str_replace( '%', '', $this->get_record()->postcode_mask );

    // set the view's items
    $this->set_item( 'user_id', $this->get_record()->get_access()->user_id, true, $user_list );
    $this->set_item( 'postcode_mask', $postcode_mask, true );
    
    $this->finish_setting_items();
  }
}
?>
