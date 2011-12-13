<?php
/**
 * coverage.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * coverage: record
 *
 * @package beartooth\database
 */
class coverage extends \cenozo\database\record
{
  /**
   * Returns the distance (in km) between the given site and the closest postcode included
   * in the coverage.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site with which to determine the distance from.
   * @return float
   * @access public
   */
  public function get_nearest_distance( $db_site )
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'postcode', 'LIKE', $this->postcode_mask );
    return static::db()->get_one(
      sprintf( 'SELECT MIN( distance ) FROM jurisdiction %s',
               $modifier->get_sql() ) );
  }

  /**
   * Returns the distance (in km) between the given site and the furthest postcode included
   * in the coverage.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site with which to determine the distance from.
   * @return float
   * @access public
   */
  public function get_furthest_distance( $db_site )
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'postcode', 'LIKE', $this->postcode_mask );
    return static::db()->get_one(
      sprintf( 'SELECT MAX( distance ) FROM jurisdiction %s',
               $modifier->get_sql() ) );
  }
}
?>
