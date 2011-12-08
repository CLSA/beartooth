<?php
/**
 * address_edit.class.php
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
 * push: address edit
 *
 * Edit a address.
 * @package beartooth\ui
 */
class address_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'address', $args );
  }

  /**
   * Overrides the parent method to make sure the postcode is valid.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    $columns = $this->get_argument( 'columns' );

    // validate the postcode
    if( array_key_exists( 'postcode', $columns ) )
    {
      $postcode = $columns['postcode'];
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $postcode ) && // postal code
          !preg_match( '/^[0-9]{5}$/', $postcode ) )  // zip code
        throw util::create( 'exception\notice',
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );
    }

    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the address id with a unique key
    $db_address = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_address->get_participant()->uid;
    $args['noid']['address.rank'] = $db_address->rank;
    
    // if set, replace the region id with a unique key
    if( array_key_exists( 'region_id', $columns ) && $columns['region_id'] )
    {
      $db_region = util::create( 'database\region', $columns['region_id'] );
      unset( $args['region_id'] );
      // we only include half of the unique key since the other half is added above
      $args['noid']['region.abbreviation'] = $db_region->abbreviation;
    }

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = util::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'address', 'edit', $args );
  }
}
?>
