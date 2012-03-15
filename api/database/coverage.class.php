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

  /**
   * Restricts a modifier to those postcodes belonging to the given access record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $postcode_column The name of the postcode column to use in the modifier
   * @param database\access $db_access The access to restrict to.
   * @param database\modifier $modifier The modifier to add the restriction to (may be null)
   * @return database\modifier
   * @static
   * @access public
   */
  public static function get_access_modifier( $postcode_column, $db_access, $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where_bracket( true );

    $first = true;
    if( !is_null( $db_access ) && $db_access->id )
    {
      // OR all access coverages making sure to AND NOT all other like coverages for the same site
      $coverage_mod = lib::create( 'database\modifier' );
      $coverage_mod->where( 'access_id', '=', $db_access->id );
      $coverage_mod->order( 'CHAR_LENGTH( postcode_mask )' );
      foreach( static::select( $coverage_mod ) as $db_coverage )
      { 
        $modifier->where_bracket( true, true );
        $modifier->where( $postcode_column, 'LIKE', $db_coverage->postcode_mask );
        
        // now remove the like coverages
        $inner_coverage_mod = lib::create( 'database\modifier' );
        $inner_coverage_mod->where( 'access_id', '!=', $db_access->id );
        $inner_coverage_mod->where( 'access.site_id', '=', $db_access->site_id );
        $inner_coverage_mod->where( 'postcode_mask', 'LIKE', $db_coverage->postcode_mask );
        foreach( static::select( $inner_coverage_mod ) as $db_inner_coverage )
          $modifier->where( $postcode_column, 'NOT LIKE', $db_inner_coverage->postcode_mask );
        $modifier->where_bracket( false );
        
        $first = false;
      } 
    }

    // make sure to return an empty list if the access has no coverage
    if( $first ) $modifier->where( $postcode_column, '=', true );
    $modifier->where_bracket( false );
    return $modifier;
  }
}
?>
