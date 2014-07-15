<?php
/**
 * self_assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget self assignment
 */
class self_assignment extends \cenozo\ui\widget
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
    parent::__construct( 'self', 'assignment', $args );
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

    $this->set_heading( 'Current Assignment' );
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

    $callback_class_name = lib::get_class_name( 'database\callback' );
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );
    $operation_class_name = lib::get_class_name( 'database\operation' );

    $session = lib::create( 'business\session' );
    $setting_manager = lib::create( 'business\setting_manager' );
    $db_user = $session->get_user();
    $db_role = $session->get_role();
    $db_site = $session->get_site();
    $current_sid = lib::create( 'business\survey_manager' )->get_current_sid();

    // see if this user has an open assignment
    $db_current_assignment = $session->get_current_assignment();
    $db_current_phone_call = $session->get_current_phone_call();

    if( is_null( $db_current_assignment ) )
      throw lib::create( 'exception\notice', 'No active assignment.', __METHOD__ );

    // fill out the participant's details
    $db_interview = $db_current_assignment->get_interview();
    $db_participant = $db_interview->get_participant();
    $db_qnaire = $db_interview->get_qnaire();

    $db_last_consent = $db_participant->get_last_consent();
    $withdrawing = !is_null( $db_last_consent ) && false == $db_last_consent->accept;

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

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $db_phone_list = $db_participant->get_phone_list( $modifier );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '!=', NULL );
    $current_calls = $db_current_assignment->get_phone_call_count( $modifier );
    $on_call = !is_null( $db_current_phone_call );

    $phone_list = array();
    foreach( $db_phone_list as $db_phone )
      $phone_list[$db_phone->id] =
        sprintf( '%d. %s (%s)', $db_phone->rank, $db_phone->type, $db_phone->number );
    $this->set_variable( 'phone_list', $phone_list );
    $this->set_variable( 'status_list', $phone_call_class_name::get_enum_values( 'status' ) );

    if( 0 == $current_calls && !$on_call && $db_interview->completed )
    {
      log::crit(
        sprintf( 'User %s has been assigned participant %d who\'s interview is complete '.
                 'but the user has not made any calls.',
                 $db_user->name,
                 $db_participant->id ) );
    }

    $this->set_variable( 'assignment_id', $db_current_assignment->id );
    $this->set_variable( 'participant_id', $db_participant->id );
    $this->set_variable( 'interview_id', $db_interview->id );
    $this->set_variable( 'participant_note_count', $db_participant->get_note_count() );
    $this->set_variable( 'participant_name',
      sprintf( $db_participant->first_name.' '.$db_participant->last_name ) );
    $this->set_variable( 'participant_uid', $db_participant->uid );
    $db_language = $db_participant->get_language();
    $this->set_variable( 'participant_language',
      is_null( $db_language ) ? 'none' : $db_language->name );
    $this->set_variable( 'current_consent',
      is_null( $db_last_consent ) ? 'none' : $db_last_consent->to_string() );
    $this->set_variable( 'withdrawing', $withdrawing );
    $this->set_variable( 'allow_withdraw', !is_null( $db_qnaire->withdraw_sid ) );
    $this->set_variable( 'survey_complete', !$current_sid );

    // determine whether we want to show a warning before ending a call
    $warn_before_ending_call = false;
    if( $setting_manager->get_setting( 'calling', 'end call warning' ) && $current_sid )
    {
      $warn_before_ending_call = true;
      if( !$withdrawing )
      { // if we're not withdrawing then make sure we're not on a repeating survey
        $phase_mod = lib::create( 'database\modifier' );
        $phase_mod->where( 'sid', '=', $current_sid );
        $phase_mod->order( 'rank' );
        $phase_mod->limit( 1 );
        $db_phase = current( $db_qnaire->get_phase_list( $phase_mod ) );
        if( !$db_phase || $db_phase->repeated ) $warn_before_ending_call = false;
      }
    }
    $this->set_variable( 'warn_before_ending_call', $warn_before_ending_call );

    // get the callback associated with this assignment, if any
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', '=', $db_current_assignment->id );
    $callback_list = $callback_class_name::select( $modifier );
    $db_callback = 0 == count( $callback_list ) ? NULL : $callback_list[0];

    if( !is_null( $db_callback ) )
    {
      $this->set_variable( 'callback',
        util::get_formatted_time( $db_callback->datetime, false ) );

      if( !is_null( $db_callback->phone_id ) )
      {
        $db_phone = lib::create( 'database\phone', $db_callback->phone_id );
        $this->set_variable( 'phone_id', $db_callback->phone_id );
        $this->set_variable( 'phone_at',
          sprintf( '%d. %s (%s)', $db_phone->rank, $db_phone->type, $db_phone->number ) );
      }
      else
      {
        $this->set_variable( 'phone_id', false );
        $this->set_variable( 'phone_at', false );
      }
    }
    else
    {
      $this->set_variable( 'callback', false );
      $this->set_variable( 'phone_id', false );
      $this->set_variable( 'phone_at', false );
    }

    if( !is_null( $db_last_assignment ) )
    {
      $this->set_variable( 'previous_assignment_id', $db_last_assignment->id );
      $this->set_variable( 'previous_assignment_date',
        util::get_formatted_date( $db_last_assignment->start_datetime ) );
      $this->set_variable( 'previous_assignment_time',
        util::get_formatted_time( $db_last_assignment->start_datetime ) );
    }
    $this->set_variable( 'previous_call_list', $previous_call_list );
    $this->set_variable( 'interview_completed', $db_interview->completed );
    $this->set_variable( 'allow_call', $session->get_allow_call() );
    $this->set_variable( 'on_call', $on_call );
    if( !is_null( $db_current_phone_call ) )
    {
      $note = $db_current_phone_call->get_phone()->note;
      $this->set_variable( 'phone_note', is_null( $note ) ? false : $note );
    }
    else $this->set_variable( 'phone_note', false );

    $allow_secondary = false;
    $phone_mod = lib::create( 'database\modifier' );
    $phone_mod->where( 'active', '=', true );
    if( 0 == $db_participant->get_phone_count( $phone_mod ) )
    {
      $allow_secondary = true;
    }
    else
    {
      $max_failed_calls =
        lib::create( 'business\setting_manager' )->get_setting( 'calling', 'max failed calls' );
      if( $max_failed_calls <= $db_interview->get_failed_call_count() )
      {
        $db_operation =
          $operation_class_name::get_operation( 'widget', 'participant', 'secondary' );
        if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
          $allow_secondary = true;
      }
    }

    $this->set_variable( 'allow_secondary', $allow_secondary );
  }
}
