<?php
/**
 * interviewer_assignment.class.php
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
 * widget interviewer assignment
 * 
 * @package beartooth\ui
 */
class interviewer_assignment extends \beartooth\ui\widget
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
    parent::__construct( 'interviewer', 'assignment', $args );
    $this->set_heading( 'Current Assignment' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throw exception\notice
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $session = util::create( 'business\session' );
    $db_role = $session->get_role();
    $db_site = $session->get_site();

    // see if this user has an open assignment
    $db_assignment = $session->get_current_assignment();
    
    if( is_null( $db_assignment ) )
      throw util::create( 'exception\notice', 'No active assignment.', __METHOD__ );

    // fill out the participant's details
    $db_interview = $db_assignment->get_interview();
    $db_participant = $db_interview->get_participant();
    
    $name = sprintf( $db_participant->first_name.' '.$db_participant->last_name );

    $language = 'none';
    if( 'en' == $db_participant->language ) $language = 'english';
    else if( 'fr' == $db_participant->language ) $language = 'french';

    $consent = 'none';
    $db_consent = $db_participant->get_last_consent();
    if( !is_null( $db_consent ) ) $consent = $db_consent->event;
    
    $previous_call_list = array();
    $db_last_assignment = $db_participant->get_last_finished_assignment();
    if( !is_null( $db_last_assignment ) )
    {
      foreach( $db_last_assignment->get_phone_call_list() as $db_phone_call )
      {
        $db_phone = $db_phone_call->get_phone();
        $previous_call_list[] = sprintf( 'Called phone #%d (%s): %s',
          $db_phone->rank,
          $db_phone->type,
          $db_phone_call->status ? $db_phone_call->status : 'unknown' );
      }
    }

    $modifier = util::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $db_phone_list = $db_participant->get_phone_list( $modifier );
    
    $modifier = util::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '!=', NULL );
    $current_calls = $db_assignment->get_phone_call_count( $modifier );
    $on_call = !is_null( $session->get_current_phone_call() );

    if( 0 == count( $db_phone_list ) && 0 == $current_calls )
    {
      log::crit(
        sprintf( 'An interviewer has been assigned participant %d who has no callable phone numbers',
        $db_participant->id ) );
    }
    else
    {
      $phone_list = array();
      foreach( $db_phone_list as $db_phone )
        $phone_list[$db_phone->id] =
          sprintf( '%d. %s (%s)', $db_phone->rank, $db_phone->type, $db_phone->number );
      $this->set_variable( 'phone_list', $phone_list );
      $class_name = util::get_class_name( 'database\phone_call' );
      $this->set_variable( 'status_list', $class_name::get_enum_values( 'status' ) );
    }

    if( 0 == $current_calls && !$on_call && $db_interview->completed )
    {
      log::crit(
        sprintf( 'An interviewer has been assigned participant %d who\'s interview is complete '.
                 'but the interviewer has not made any calls.',
                 $db_participant->id ) );
    }

    $this->set_variable( 'assignment_id', $db_assignment->id );
    $this->set_variable( 'participant_id', $db_participant->id );
    $this->set_variable( 'participant_note_count', $db_participant->get_note_count() );
    $this->set_variable( 'participant_name', $name );
    $this->set_variable( 'participant_language', $language );
    $this->set_variable( 'participant_consent', $consent );
    
    if( !is_null( $db_last_assignment ) )
    {
      $this->set_variable( 'previous_assignment_id', $db_last_assignment->id );
      $this->set_variable( 'previous_assignment_note_count',
        $db_last_assignment->get_note_count() );
      $this->set_variable( 'previous_assignment_date',
        util::get_formatted_date( $db_last_assignment->start_datetime ) );
      $this->set_variable( 'previous_assignment_time',
        util::get_formatted_time( $db_last_assignment->start_datetime ) );
    }
    $this->set_variable( 'previous_call_list', $previous_call_list );
    $this->set_variable( 'interview_completed', $db_interview->completed );
    $this->set_variable( 'allow_call', $session->get_allow_call() );
    $this->set_variable( 'on_call', $on_call );
  }
}
?>
