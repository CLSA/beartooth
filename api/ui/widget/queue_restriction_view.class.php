<?php
/**
 * queue_restriction_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget queue_restriction view
 * 
 * @package beartooth\ui
 */
class queue_restriction_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'queue_restriction', 'view', $args );

    // define all columns defining this record

    $type = 'administrator' == lib::create( 'business\session' )->get_role()->name
          ? 'enum'
          : 'hidden';
    $this->add_item( 'site_id', $type, 'Site' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'region_id', 'enum', 'Region' );
    $this->add_item( 'postcode', 'string', 'Postcode' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    $session = lib::create( 'business\session' );
    $is_administrator = 'administrator' == $session->get_role()->name;

    // create enum arrays
    if( $is_administrator )
    {
      $sites = array();
      $class_name = lib::get_class_name( 'database\site' );
      foreach( $class_name::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    }
    $regions = array();
    $class_name = lib::get_class_name( 'database\region' );
    foreach( $class_name::select() as $db_region ) $regions[$db_region->id] = $db_region->name;

    // set the view's items
    $this->set_item(
      'site_id', $this->get_record()->site_id, false, $is_administrator ? $sites : NULL );
    $this->set_item( 'city', $this->get_record()->city, false );
    $this->set_item( 'region_id', $this->get_record()->region_id, false, $regions );
    $this->set_item( 'postcode', $this->get_record()->postcode, false );

    $this->finish_setting_items();
  }
}
?>
