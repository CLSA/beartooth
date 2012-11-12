<?php
/**
 * site_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget site view
 */
class site_view extends \cenozo\ui\widget\site_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    // create an associative array with everything we want to display about the site
    $this->add_item( 'institution', 'string', 'Institution' );
    $this->add_item( 'phone_number', 'string', 'Phone Number' );
    $this->add_item( 'address1', 'string', 'Address1' );
    $this->add_item( 'address2', 'string', 'Address2' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'region_id', 'enum', 'Region' );
    $this->add_item( 'postcode', 'string', 'Postcode',
      'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.' );
    $this->add_item( 'voip_host', 'string', 'VoIP Host' );
    $this->add_item( 'voip_xor_key', 'string', 'VoIP XOR Key' );
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
    
    $regions = array();
    $class_name = lib::get_class_name( 'database\region' );
    foreach( $class_name::select() as $db_region )
      $regions[$db_region->id] = $db_region->name.', '.$db_region->country;
    reset( $regions );

    // set the view's items
    $this->set_item( 'institution', $this->get_record()->institution );
    $this->set_item( 'phone_number', $this->get_record()->phone_number );
    $this->set_item( 'address1', $this->get_record()->address1 );
    $this->set_item( 'address2', $this->get_record()->address2 );
    $this->set_item( 'city', $this->get_record()->city );
    $this->set_item( 'region_id', $this->get_record()->region_id, false, $regions );
    $this->set_item( 'postcode', $this->get_record()->postcode, true );
    $this->set_item( 'voip_host', $this->get_record()->voip_host );
    $this->set_item( 'voip_xor_key', $this->get_record()->voip_xor_key );
  }
}
?>
