<?php
/**
 * base_add_access.class.php
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
 * Base class for adding access to sites and users.
 * 
 * @package beartooth\ui
 */
class base_add_access extends \cenozo\ui\widget\base_add_access
{
  /**
   * Overrides the role list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_role_count( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = util::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'onyx' );
    $modifier->where( 'tier', '<=', util::create( 'business\session' )->get_role()->tier );
    $class_name = util::get_class_name( 'database\role' );
    return $class_name::count( $modifier );
  }

  /**
   * Overrides the role list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_role_list( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = util::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'onyx' );
    $modifier->where( 'tier', '<=', util::create( 'business\session' )->get_role()->tier );
    $class_name = util::get_class_name( 'database\role' );
    return $class_name::select( $modifier );
  }
}
?>
