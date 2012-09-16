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
class participant_list extends site_restricted_list
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
      $this->add_column( 'source.name', 'string', 'Source', true );
      $this->add_column( 'primary_site', 'string', 'Site', false );
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

    foreach( $this->get_record_list() as $record )
    {
      $db_source = $record->get_source();
      $db_address = $record->get_first_address();
      $source_name = is_null( $db_source ) ? '(none)' : $db_source->name;
      $this->add_row( $record->id,
        is_null( $this->assignment_type ) ?
        array( 'uid' => $record->uid ? $record->uid : '(none)',
               'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'source.name' => $source_name,
               'primary_site' => $record->get_primary_site()->name,
               // note count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) :
        array( 'uid' => $record->uid ? $record->uid : '(none)',
               'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'ranked_participant_for_queue.first_address_address1' => $db_address->address1,
               'ranked_participant_for_queue.first_address_city' => $db_address->city,
               'ranked_participant_for_queue.first_address_postcode' => $db_address->postcode,
               // note count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }

    // include the sync action if the widget isn't parented
    if( is_null( $this->parent ) )
    {
      $operation_class_name = lib::get_class_name( 'database\operation' );
      $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'sync' );
      if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
        $this->add_action( 'sync', 'Participant Sync', $db_operation,
          'Synchronize participants with Mastodon' );
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
    $participant_class_name = lib::get_class_name( 'database\participant' );

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
    $participant_class_name = lib::get_class_name( 'database\participant' );

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
?>
