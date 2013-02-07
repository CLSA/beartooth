<?php
/**
 * site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * site: record
 */
class site extends \cenozo\database\site
{
  /**
   * Gives a complete name for the site in the form of "name"
   * This method overrides the parent class to remove the (service) part of the name
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @access public
   */
  public function get_full_name()
  {
    return $this->name;
  }

  public static function select( $modifier = NULL, $count = false )
  {
    // make sure to only include sites belonging to this application
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'service_id', '=', lib::create( 'business\session' )->get_service()->id );
    return parent::select( $modifier, $count );
  }
}

site::add_extending_table( 'voip' );
