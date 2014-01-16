<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget participant list
 */
class participant_list extends \cenozo\ui\widget\site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
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

    $session = lib::create( 'business\session' );

    // determine if the parent is an assignment select widget
    if( !is_null( $this->parent ) )
    {
      if( 'home_assignment_select' == $this->parent->get_class_name() )
        $this->assignment_type = 'home';
      else if( 'site_assignment_select' == $this->parent->get_class_name() )
        $this->assignment_type = 'site';
    }
    $this->set_variable( 'assignment_type', $this->assignment_type );

    $this->add_column( 'uid', 'string', 'UID', true );
    $this->add_column( 'first_name', 'string', 'First', true );
    $this->add_column( 'last_name', 'string', 'Last', true );
    if( is_null( $this->assignment_type ) )
    {
      $this->add_column( 'active', 'boolean', 'Active', true );
      $this->add_column( 'source.name', 'string', 'Source', true );
      if( 1 != $session->get_service()->get_cohort_count() )
        $this->add_column( 'cohort.name', 'string', 'Cohort', true );
      $this->add_column( 'site', 'string', 'Site', false );
    }
    else if( 'home' == $this->assignment_type )
    {
      // When the list is parented by an assignment select widget the internal query
      // comes from the queue class, so every participant is linked to their first
      // address using table alias "first_address"
      $this->add_column(
        'ranked_participant_for_queue.first_address_address1', 'string', 'Address', true );
      $this->add_column(
        'ranked_participant_for_queue.first_address_city', 'string', 'City', true );
      $this->add_column(
        'ranked_participant_for_queue.first_address_postcode', 'string', 'Postcode', true );
    }
    else // site assignment
    {
      // show the date of when the home interview was completed
      $this->add_column( 'home_interview', 'date', 'Home Interview Completed', false );
    }

    $this->extended_site_selection = true;

    if( $this->allow_restrict_state )
    {
      $restrict_state_id = $this->get_argument( 'restrict_state_id', '' );
      if( $restrict_state_id )
        $this->set_heading(
          sprintf( '%s, restricted to %s',
                   $this->get_heading(),
                   lib::create( 'database\state', $restrict_state_id )->name ) );
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

    $state_class_name = lib::get_class_name( 'database\state' );
    $operation_class_name = lib::get_class_name( 'database\operation' );
    $session = lib::create( 'business\session' );

    foreach( $this->get_record_list() as $record )
    {
      $db_source = $record->get_source();
      $db_address = $record->get_first_address();
      $db_site = $record->get_effective_site();
      $columns = array(
        'uid' => $record->uid ? $record->uid : '(none)',
        'first_name' => $record->first_name,
        'last_name' => $record->last_name,
        // note count isn't a column, it's used for the note button
        'note_count' => $record->get_note_count() );

      if( is_null( $this->assignment_type ) )
      {
        $columns['active'] = $record->active;
        $columns['source.name'] = is_null( $db_source ) ? '(none)' : $db_source->name;
        if( 1 != $session->get_service()->get_cohort_count() )
          $columns['cohort.name'] = $record->get_cohort()->name;
        $columns['site'] = $db_site ? $db_site->name : '(none)';
      }
      else if( 'home' == $this->assignment_type )
      {
        $columns['ranked_participant_for_queue.first_address_address1'] =
                 is_null( $db_address ) ? '(none)' : $db_address->address1;
        $columns['ranked_participant_for_queue.first_address_city'] =
                 is_null( $db_address ) ? '(none)' : $db_address->city;
        $columns['ranked_participant_for_queue.first_address_postcode'] =
                 is_null( $db_address ) ? '(none)' : $db_address->postcode;
      }
      else // site assignment
      {
        $date = NULL;

        // get the last completed in-home appointment
        $appointment_mod = lib::create( 'database\modifier' );
        $appointment_mod->where( 'completed', '=', true );
        $appointment_mod->where( 'address_id', '!=', NULL );
        $appointment_mod->order_desc( 'datetime' );
        $appointment_mod->limit( 1 );
        $appointment_list = $record->get_appointment_list( $appointment_mod );

        if( 0 < count( $appointment_list ) )
        {
          $db_appointment = current( $appointment_list );
          $date = $db_appointment->datetime;
        }

        $columns['home_interview'] = $date;
      }

      $this->add_row( $record->id, $columns );
    }

    if( $this->allow_restrict_state )
    {
      $state_mod = lib::create( 'database\modifier' );
      $state_mod->order( 'rank' );
      $state_list = array();
      foreach( $state_class_name::select( $state_mod ) as $db_state )
        $state_list[$db_state->id] = $db_state->name;
      $this->set_variable( 'state_list', $state_list );
      $this->set_variable( 'restrict_state_id', $this->get_argument( 'restrict_state_id', '' ) );
    }
    
    // include the participant site reassign and search actions if the widget isn't parented
    if( is_null( $this->parent ) ) 
    {   
      $db_operation =
        $operation_class_name::get_operation( 'widget', 'participant', 'site_reassign' );
      if( $session->is_allowed( $db_operation ) ) 
        $this->add_action( 'reassign', 'Site Reassign', $db_operation,
          'Change the preferred site of multiple participants at once' );
      $db_operation =
        $operation_class_name::get_operation( 'widget', 'participant', 'search' );
      if( $session->is_allowed( $db_operation ) ) 
        $this->add_action( 'search', 'Search', $db_operation,
          'Search for participants based on partial information' );
    }   
  }

  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    $session = lib::create( 'business\session' );

    if( $this->allow_restrict_state )
    {
      $restrict_state_id = $this->get_argument( 'restrict_state_id', '' );
      if( $restrict_state_id )
      {
        if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'state_id', '=', $restrict_state_id );
      }
    }

    if( 'interviewer' == $session->get_role()->name )
    { // restrict interview lists to those they have unfinished appointments with
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'appointment.completed', '=', false );
      $modifier->where( 'appointment.user_id', '=', $session->get_user()->id );
    }

    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_record_list( $modifier = NULL )
  {
    $session = lib::create( 'business\session' );

    if( $this->allow_restrict_state )
    {
      $restrict_state_id = $this->get_argument( 'restrict_state_id', '' );
      if( $restrict_state_id )
      {
        if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'state_id', '=', $restrict_state_id );
      }
    }

    if( 'interviewer' == $session->get_role()->name )
    { // restrict interview lists to those they have unfinished appointments with
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'appointment.completed', '=', false );
      $modifier->where( 'appointment.user_id', '=', $session->get_user()->id );
    }

    return parent::determine_record_list( $modifier );
  }

  /**
   * Get whether to include a drop down to restrict the list by state
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_allow_restrict_state()
  {
    return $this->allow_restrict_state;
  }

  /**
   * Set whether to include a drop down to restrict the list by state
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_allow_restrict_state( $enable )
  {
    $this->allow_restrict_state = $enable;
  }

  /**
   * Whether to include a drop down to restrict the list by state
   * @var boolean
   * @access protected
   */
  protected $allow_restrict_state = true;

  /**
   * The type of assignment select, or null if the list is not being used to select an assignment
   * @var string assignment_type
   * @access @protected
   */
  protected $assignment_type = NULL;
}
