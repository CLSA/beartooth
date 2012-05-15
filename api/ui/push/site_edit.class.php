<?php
/**
 * site_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: site edit
 *
 * Edit a site.
 * @package beartooth\ui
 */
class site_edit extends \cenozo\ui\push\site_edit
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
  protected function validate()
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

  protected function setup()
  {
    parent::setup();

    $columns = $this->get_argument( 'columns' );

    // only send a machine request if editing the name or time zone
    $this->set_machine_request_enabled(
      array_key_exists( 'name', $columns ) || array_key_exists( 'timezone', $columns ) );
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
    $args = parent::convert_to_noid( $args );

    // add in the site's cohort
    $args['noid']['site']['cohort'] = 'comprehensive';

    return $args;
  }
}
?>
