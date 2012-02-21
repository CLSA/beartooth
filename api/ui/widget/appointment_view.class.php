<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

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
    
    // only interviewers should select addresses
    $this->select_address = 'interviewer' == lib::create( 'business\session' )->get_role()->name;
    
    // add items to the view
    if( $this->select_address ) $this->add_item( 'address_id', 'enum', 'Address' );
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

    $db_participant = lib::create( 'database\participant', $this->get_record()->participant_id );
  
    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );

    // don't allow users to change the type (home/site) of appointment
    if( $this->select_address )
    {
      foreach( $db_participant->get_address_list( $modifier ) as $db_address )
        $address_list[$db_address->id] = sprintf(
          '%s, %s, %s, %s',
          $db_address->address2 ? $db_address->address1.', '.$db_address->address2
                                : $db_address->address1,
          $db_address->city,
          $db_address->get_region()->abbreviation,
          $db_address->postcode );
      $this->set_item( 'address_id', $address, true, $address_list, true );
    }
    
    // set the view's items
    $this->set_item( 'datetime', $this->get_record()->datetime, true );
    $this->set_item( 'state', $this->get_record()->get_state(), false );

    $this->finish_setting_items();

    // hide the calendar if requested to
    $this->set_variable( 'hide_calendar', $this->get_argument( 'hide_calendar', false ) );
  }
  
  /**
   * Determines whether to allow the user to select an address for the appointment.
   * @var boolean $select_address
   * @access protected
   */
  protected $select_address = false;
}
?>
