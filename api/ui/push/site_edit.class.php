<?php
/**
 * site_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: site edit
 *
 * Edit a site.
 */
class site_edit extends \cenozo\ui\push\site_edit
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // validate the postcode
    if( array_key_exists( 'postcode', $columns ) )
    {
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $columns['postcode'] ) &&
          !preg_match( '/^[0-9]{5}$/', $columns['postcode'] ) )
        throw lib::create( 'exception\notice',
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );

      $postcode_class_name = lib::get_class_name( 'database\postcode' );
      $db_postcode = $postcode_class_name::get_match( $columns['postcode'] );
      if( is_null( $db_postcode ) )
        throw lib::create( 'exception\notice',
          'The postcode is invalid and cannot be used.', __METHOD__ );
    }
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $columns = $this->get_argument( 'columns' );

    // only send a machine request if editing the name or time zone
    $this->set_machine_request_enabled(
      array_key_exists( 'name', $columns ) || array_key_exists( 'timezone', $columns ) );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $columns = $this->get_argument( 'columns' );

    if( array_key_exists( 'postcode', $columns ) )
    {
      // source the postcode to determine the region
      $db_address = lib::create( 'database\address' );
      $db_address->postcode = $columns['postcode'];
      $db_address->source_postcode();
      $this->get_record()->region_id = $db_address->region_id;
      $this->get_record()->save();
    }
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
    $args['noid']['site']['cohort'] =
      lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );

    return $args;
  }
}
?>
