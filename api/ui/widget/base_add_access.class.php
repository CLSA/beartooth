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
class base_add_access extends \cenozo\ui\push\base_add_access
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
    if( is_null( $modifier ) ) $modifier = new db\modifier();
    $modifier->where( 'name', '!=', 'onyx' );
    $modifier->where( 'tier', '<=', bus\session::self()->get_role()->tier );
    return db\role::count( $modifier );
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
    if( is_null( $modifier ) ) $modifier = new db\modifier();
    $modifier->where( 'name', '!=', 'onyx' );
    $modifier->where( 'tier', '<=', bus\session::self()->get_role()->tier );
    return db\role::select( $modifier );
  }
}
?>
