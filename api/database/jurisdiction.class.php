<?php
/**
 * jurisdiction.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * jurisdiction: record
 *
 * @package beartooth\database
 */
class jurisdiction extends \cenozo\database\record
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

    $coverage_class_name = lib::get_class_name( 'database\coverage' );

    // left join the participant_primary_address and address tables
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'jurisdiction.site_id', '=', $db_access->get_site()->id );
    $modifier =
      $coverage_class_name::get_access_modifier( 'jurisdiction.postcode', $db_access, $modifier );

    $sql = sprintf(
      ( $count ? 'SELECT COUNT(*) ' : 'SELECT jurisdiction.id ' ).
      'FROM jurisdiction %s',
      $modifier->get_sql() );

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
