<?php
/**
 * site_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: site new
 *
 * Create a new site.
 * @package beartooth\ui
 */
class site_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // make sure the name column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'name', $columns ) || 0 == strlen( $columns['name'] ) )
      throw new exc\notice( 'The site name cannot be left blank.', __METHOD__ );

    // validate the postcode
    $postcode = $columns['postcode'];
    if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $postcode ) && // postal code
        !preg_match( '/^[0-9]{5}$/', $postcode ) )  // zip code
      throw new exc\notice(
        'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );

    parent::finish();
  }
}
?>
