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
    else
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

    $this->extended_site_selection = true;
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

    $participant_class_name = lib::get_class_name( 'database\participant' );
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
      else
      {
        $columns['ranked_participant_for_queue.first_address_address1'] =
                 is_null( $db_address ) ? '(none)' : $db_address->address1;
        $columns['ranked_participant_for_queue.first_address_city'] =
                 is_null( $db_address ) ? '(none)' : $db_address->city;
        $columns['ranked_participant_for_queue.first_address_postcode'] =
                 is_null( $db_address ) ? '(none)' : $db_address->postcode;
      }

      $this->add_row( $record->id, $columns );
    }

    $this->set_variable( 'conditions', $participant_class_name::get_enum_values( 'status' ) );
    $this->set_variable( 'restrict_condition', $this->get_argument( 'restrict_condition', '' ) );
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
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $session = lib::create( 'business\session' );
    $restrict_condition = $this->get_argument( 'restrict_condition', '' );

    if( $restrict_condition )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'status', '=', $restrict_condition );
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
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $session = lib::create( 'business\session' );
    $restrict_condition = $this->get_argument( 'restrict_condition', '' );

    if( $restrict_condition )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'status', '=', $restrict_condition );
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
   * The type of assignment select, or null if the list is not being used to select an assignment
   * @var string assignment_type
   * @access @protected
   */
  protected $assignment_type = NULL;
}
