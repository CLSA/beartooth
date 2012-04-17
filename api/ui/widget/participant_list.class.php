<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget participant list
 * 
 * @package beartooth\ui
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

    $this->add_column( 'uid', 'string', 'Unique ID', true );
    $this->add_column( 'first_name', 'string', 'First Name', true );
    $this->add_column( 'last_name', 'string', 'Last Name', true );
    $this->add_column( 'status', 'string', 'Condition', true );

    $this->extended_site_selection = true;
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'uid' => $record->uid ? $record->uid : '(none)',
               'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'status' => $record->status ? $record->status : '(none)',
               // note count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'sync' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $this->add_action( 'sync', 'Participant Sync', $db_operation,
        'Synchronize participants with Mastodon' );

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $session = lib::create( 'business\session' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    if( 'interviewer' == $session->get_role()->name )
    { // restrict interview lists to those they have appointments with
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
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
  protected function determine_record_list( $modifier = NULL )
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
}
?>
