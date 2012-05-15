<?php
/**
 * site_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: site new
 *
 * Create a new site.
 * @package beartooth\ui
 */
class site_new extends \cenozo\ui\push\site_new
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @abstract
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }

  // TODO: document
  public function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // validate the postcode
    if( array_key_exists( 'postcode', $columns ) )
    {
      $postcode = $columns['postcode'];
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $postcode ) && // postal code
          !preg_match( '/^[0-9]{5}$/', $postcode ) )  // zip code
        throw lib::create( 'exception\notice',
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );
    }
  }

  /**
   * Overrides the parent method to make sure the postcode is valid.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function execute()
  {
    parent::execute();
    
    // source the postcode to determine the region
    $db_address = lib::create( 'database\address' );
    $db_address->postcode = $this->get_record()->postcode;
    $db_address->source_postcode();
    $this->get_record()->region_id = $db_address->region_id;
    $this->get_record()->save();
  }

  /**
   * Converts primary keys to unique keys in operation arguments.
   * All converted arguments will appear in the array under a 'noid' key.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An argument list, usually those passed to the push operation.
   * @return array
   * @access protected
   */
  protected function convert_to_noid( $args )
  {
    // remove additional columns which are not required
    unset( $args['columns']['institution'] );
    unset( $args['columns']['phone_number'] );
    unset( $args['columns']['address1'] );
    unset( $args['columns']['address2'] );
    unset( $args['columns']['city'] );
    unset( $args['columns']['postcode'] );

    $args = parent::convert_to_noid( $args );

    // add in the site's cohort
    $args['columns']['cohort'] = 'comprehensive';

    return $args;
  }
}
?>
