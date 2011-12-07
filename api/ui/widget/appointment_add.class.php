<?php
/**
 * appointment_add.class.php
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
 * widget appointment add
 * 
 * @package beartooth\ui
 */
class appointment_add extends base_appointment_view
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
    parent::__construct( 'add', $args );
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'address_id', 'enum', 'Address',
      'For site interviews select "site", otherwise select which address the home interview '.
      'will take place at.' );
    $this->add_item( 'datetime', 'datetime', 'Date' );
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
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw new exc\runtime(
        'Appointment widget must have a parent with participant as the subject.', __METHOD__ );

    $db_participant = util::create( 'database\participant', $this->parent->get_record()->id );
    
    // create enum arrays
    $modifier = new db\modifier();
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $address_list = array( 'NULL' => 'site' );
    foreach( $db_participant->get_address_list( $modifier ) as $db_address )
      $address_list[$db_address->id] = sprintf(
        '%s, %s, %s, %s',
        $db_address->address2 ? $db_address->address1.', '.$db_address->address2
                              : $db_address->address1,
        $db_address->city,
        $db_address->get_region()->abbreviation,
        $db_address->postcode );
    
    // create the min datetime array
    $start_qnaire_date = $this->parent->get_record()->start_qnaire_date;
    $datetime_limits = !is_null( $start_qnaire_date )
                     ? array( 'min_date' => substr( $start_qnaire_date, 0, -9 ) )
                     : NULL;

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'address_id', '', true, $address_list, true );
    $this->set_item( 'datetime', '', true, $datetime_limits );

    $this->set_variable( 
      'is_mid_tier',
      2 == bus\session::self()->get_role()->tier );

    $this->finish_setting_items();
  }
}
?>
