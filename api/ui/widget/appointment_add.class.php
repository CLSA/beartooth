<?php
/**
 * appointment_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget appointment add
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
    $db_effective_qnaire = $this->parent->get_record()->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) )
      throw lib::create( 'exception\notice',
        'You cannot add an appointment for this participant since they do not currently have '.
        'any questionnaires to answer.',
        __METHOD__ );

    $this->select_address = 'home' == $db_effective_qnaire->type;
    
    // add items to the view
    $this->add_item( 'interview_id', 'hidden' );
    if( $this->select_address )
    {
      $this->add_item( 'user_id', 'enum', 'Interviewer' );
      $this->add_item( 'address_id', 'enum', 'Address' );
    }
    $this->add_item( 'datetime', 'datetime', 'Date' );
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
    
    $session = lib::create( 'business\session' );
    
    // create enum arrays
    $role_class_name = lib::get_class_name( 'database\role' );
    $user_class_name = lib::get_class_name( 'database\user' );

    $db_site = $this->db_participant->get_effective_site();
    $db_role = $role_class_name::get_unique_record( 'name', 'interviewer' );
    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'access.site_id', '=', $db_site->id );
    $user_mod->where( 'access.role_id', '=', $db_role->id );
    $interviewers = array();
    foreach( $user_class_name::select( $user_mod ) as $db_user )
      $interviewers[$db_user->id] = $db_user->name;

    if( $this->select_address )
    {
      $address_mod = lib::create( 'database\modifier' );
      $address_mod->where( 'active', '=', true );
      $address_mod->order( 'rank' );

      $address_list = array();
      foreach( $this->db_participant->get_address_list( $address_mod ) as $db_address )
        $address_list[$db_address->id] = sprintf(
          '%s, %s, %s, %s',
          $db_address->address2 ? $db_address->address1.', '.$db_address->address2
                                : $db_address->address1,
          $db_address->city,
          $db_address->get_region()->abbreviation,
          $db_address->postcode );

      $user_id = 'interviewer' == $session->get_role()->name
               ? $session->get_user()->id
               : current( $interviewers );
      $this->set_item( 'user_id', $user_id, true, $interviewers );
      $this->set_item( 'address_id', key( $address_list ), true, $address_list, true );
    }

    // create the min datetime array
    $start_qnaire_date = $this->db_participant->get_start_qnaire_date();
    $datetime_limits = !is_null( $start_qnaire_date )
                     ? array( 'min_date' => $start_qnaire_date->format( 'Y-m-d' ) )
                     : NULL;

    // set the view's items
    $this->set_item( 'interview_id', $this->db_interview->id );
    $this->set_item( 'datetime', '', true, $datetime_limits );
    
    $db_effective_qnaire = $this->db_participant->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) )
      throw lib::create( 'exception\notice',
        'You cannot add an appointment for this participant since they do not currently have '.
        'any questionnaires to answer.',
        __METHOD__ );

    $this->set_variable( 'current_qnaire_type', $db_effective_qnaire->type );
  }
  
  /**
   * Determines whether to allow the user to select an address for the appointment.
   * @var boolean $select_address
   * @access protected
   */
  protected $select_address = false;
}
