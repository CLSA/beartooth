<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget appointment view
 * 
 * @package beartooth\ui
 */
class appointment_view extends base_appointment_view
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
    parent::__construct( 'view', $args );
    
    // add items to the view
    $this->add_item( 'address_id', 'enum', 'Address',
      'For site interviews select "site", otherwise select which address the home interview '.
      'will take place at.' );
    $this->add_item( 'datetime', 'datetime', 'Date' );
    $this->add_item( 'state', 'constant', 'State',
      '(One of reached, not reached, upcoming or passed)' );
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

    $db_participant = new db\participant( $this->get_record()->participant_id );
  
    // create enum arrays
    $modifier = new db\modifier();
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );

    // don't allow users to change the type (home/site) of appointment
    if( is_null( $this->get_record()->address_id ) )
    {
      $address_list = array( 'NULL' => 'site' );
    }
    else
    {
      foreach( $db_participant->get_address_list( $modifier ) as $db_address )
        $address_list[$db_address->id] = sprintf(
          '%s, %s, %s, %s',
          $db_address->address2 ? $db_address->address1.', '.$db_address->address2
                                : $db_address->address1,
          $db_address->city,
          $db_address->get_region()->abbreviation,
          $db_address->postcode );
    }
    
    // set the view's items
    $this->set_item(
      'address_id', $this->get_record()->address_id, true, $address_list, true );
    $this->set_item( 'datetime', $this->get_record()->datetime, true );
    $this->set_item( 'state', $this->get_record()->get_state(), false );

    $this->finish_setting_items();
  }
}
?>
