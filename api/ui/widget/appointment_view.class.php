<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget appointment view
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
    
    // only interviewers should select addresses
    $this->select_address = !is_null( $this->get_record()->address_id );
    
    $this->add_item( 'type', 'constant', 'Type' );
    $this->add_item( 'uid', 'constant', 'UID' );

    // add items to the view
    if( $this->select_address )
    {
      $this->add_item( 'user_id', 'enum', 'Interviewer' );
      $this->add_item( 'address_id', 'enum', 'Address' );
    }
    $this->add_item( 'datetime', 'datetime', 'Date' );
    $this->add_item( 'state', 'constant', 'State', '(One of complete, upcoming or passed)' );

    $this->set_editable(
      util::get_datetime_object() <= util::get_datetime_object( $this->get_record()->datetime ) );
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

    $operation_class_name = lib::get_class_name( 'database\operation' );

    $db_participant = lib::create( 'database\participant', $this->get_record()->participant_id );
  
    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );

    // don't allow users to change the type (home/site) of appointment
    if( $this->select_address )
    {
      $role_class_name = lib::get_class_name( 'database\role' );
      $user_class_name = lib::get_class_name( 'database\user' );

      $db_site = $this->get_record()->get_participant()->get_primary_site();
      $db_role = $role_class_name::get_unique_record( 'name', 'interviewer' );
      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'site_id', '=', $db_site->id );
      $user_mod->where( 'role_id', '=', $db_role->id );
      $interviewers = array();
      foreach( $user_class_name::select( $user_mod ) as $db_user )
        $interviewers[$db_user->id] = $db_user->name;

      foreach( $db_participant->get_address_list( $modifier ) as $db_address )
        $address_list[$db_address->id] = sprintf(
          '%s, %s, %s, %s',
          $db_address->address2 ? $db_address->address1.', '.$db_address->address2
                                : $db_address->address1,
          $db_address->city,
          $db_address->get_region()->abbreviation,
          $db_address->postcode );

      $this->set_item(
        'user_id', $this->get_record()->user_id, true, $interviewers );
      $this->set_item(
        'address_id', $this->get_record()->get_address()->id, true, $address_list, true );
    }
    
    // set the view's items
    $this->set_item( 'type', $this->select_address ? 'home' : 'site' );
    $this->set_item( 'uid', $db_participant->uid );
    $this->set_item( 'datetime', $this->get_record()->datetime, true );
    $this->set_item( 'state', $this->get_record()->get_state(), false );

    // hide the calendar if requested to
    $this->set_variable( 'select_address', $this->select_address );
    $this->set_variable( 'hide_calendar', $this->get_argument( 'hide_calendar', false ) );
    $this->set_variable( 'participant_id', $db_participant->id );

    // add an action to view the participant's details
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'view' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $this->add_action( 'view_details', 'View Details', NULL, 'View the participant\'s details' );
  }
  
  /**
   * Determines whether to allow the user to select an address for the appointment.
   * @var boolean $select_address
   * @access protected
   */
  protected $select_address = false;
}
?>
