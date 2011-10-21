<?php
/**
 * jurisdiction.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\exception as exc;

/**
 * jurisdiction: record
 *
 * @package beartooth\database
 */
class jurisdiction extends record
{
  /**
   * Identical to the parent's select method but restrict to a particular access.
   * This is usually a user's interview access to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param user $db_access The user's access to restrict the selection to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_access( $db_access, $modifier = NULL, $count = false )
  {
    // if there is no access restriction then just use the parent method
    if( is_null( $db_access ) ) return parent::select( $modifier, $count );

    $sql = sprintf(
      ( $count ? 'SELECT COUNT(*) ' : 'SELECT jurisdiction.id ' ).
      'FROM jurisdiction '.
      'WHERE jurisdiction.site_id = %s '.
      'AND ( ',
      database::format_string( $db_access->get_site()->id ) );

    // OR all access coverages making sure to AND NOT all other like coverages for the same site
    $first = true;
    $coverage_mod = new modifier();
    $coverage_mod->where( 'access_id', '=', $db_access->id );
    $coverage_mod->order( 'CHAR_LENGTH( postcode_mask )' );
    foreach( coverage::select( $coverage_mod ) as $db_coverage )
    {
      $sql .= sprintf( '%s ( jurisdiction.postcode LIKE %s ',
                       $first ? '' : 'OR',
                       database::format_string( $db_coverage->postcode_mask ) );
      $first = false;

      // now remove the like coverages
      $inner_coverage_mod = new modifier();
      $inner_coverage_mod->where( 'access_id', '!=', $db_access->id );
      $inner_coverage_mod->where( 'access.site_id', '=', $db_access->site_id );
      $inner_coverage_mod->where( 'postcode_mask', 'LIKE', $db_coverage->postcode_mask );
      foreach( coverage::select( $inner_coverage_mod ) as $db_inner_coverage )
      {
        $sql .= sprintf( 'AND jurisdiction.postcode NOT LIKE %s ',
                         database::format_string( $db_inner_coverage->postcode_mask ) );
      }
      $sql .= ') ';
    }

    // make sure to return an empty list if the access has no coverage
    $sql .= $first ? 'false )' : ') ';
    if( !is_null( $modifier ) ) $sql .= $modifier->get_sql( true );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
  }

  /**
   * Identical to the parent's count method but restrict to a particular access.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param access $db_access The access to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_access( $db_access, $modifier = NULL )
  {
    return static::select_for_access( $db_access, $modifier, true );
  }
}
?>
