<?php
/**
 * onyx_instance_edit.class.php
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
 * push: onyx_instance edit
 *
 * Edit a onyx_instance.
 * @package beartooth\ui
 */
class onyx_instance_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx_instance', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure that only admins can edit onyx instances not belonging to the current site
    $session = bus\session::self();
    $is_administrator = 'administrator' == $session->get_role()->name;

    if( !$is_administrator && $session->get_site()->id != $this->get_record()->site_id )
    {
      throw new exc\notice(
        'You do not have access to edit this onyx instance.', __METHOD__ );
    }

    // make that at least one of columns is not null
    $columns = $this->get_argument( 'columns' );
    if( ( ( array_key_exists( 'site_id', $columns ) && !$columns['site_id'] ) ||
          is_null( $this->get_record()->site_id ) ) &&
        ( ( array_key_exists( 'city', $columns ) && !$columns['city'] ) ||
          is_null( $this->get_record()->city ) ) &&
        ( ( array_key_exists( 'region_id', $columns ) && !$columns['region_id'] ) ||
          is_null( $this->get_record()->region_id ) ) &&
        ( ( array_key_exists( 'postcode', $columns ) && !$columns['postcode'] ) ||
          is_null( $this->get_record()->postcode ) ) )
    {
      throw new exc\notice( 'At least one item must be specified.', __METHOD__ );
    }

    // make sure the postcode is valid
    if( array_key_exists( 'postcode', $columns ) )
    {
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $columns['postcode'] ) &&
          !preg_match( '/^[0-9]{5}$/', $columns['postcode'] ) )
        throw new exc\notice(
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );
    }

    parent::finish();
  }
}
?>
