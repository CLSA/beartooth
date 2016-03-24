<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * appointment: record
 */
class appointment extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    $callback_class_name = lib::get_class_name( 'database\callback' );

    // make sure there is a maximum of 1 incomplete appointment or callback per interview
    if( is_null( $this->id ) && !$this->completed )
    {
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'interview_id', '=', $this->interview_id );
      $appointment_mod->where( 'completed', '=', false );
      if( !is_null( $this->id ) ) $appointment_mod->where( 'id', '!=', $this->id );

      $callback_mod = lib::create( 'database\modifier' );
      $callback_mod->where( 'interview_id', '=', $this->interview_id );
      $callback_mod->where( 'assignment_id', '=', NULL );

      if( 0 < static::count( $appointment_mod ) || 0 < $callback_class_name::count( $callback_mod ) )
        throw lib::create( 'exception\notice',
          'Cannot have more than one unassigned appointment or callback per interview.', __METHOD__ );
    }

    // make sure home appointments have an address and user, and site appointments do not
    if( 'home' == $this->get_interview()->get_qnaire()->type )
    {
      if( is_null( $this->address_id ) )
        throw lib::create( 'exception\notice',
          'You must specify an address for home appointments.', __METHOD__ );
      if( is_null( $this->user_id ) )
        throw lib::create( 'exception\notice',
          'You must specify an interviewer for home appointments.', __METHOD__ );
    }
    else // site appointment
    {
      if( !is_null( $this->address_id ) )
        throw lib::create( 'exception\notice',
          'You cannot specify an address for site appointments.', __METHOD__ );
      if( !is_null( $this->user_id ) )
        throw lib::create( 'exception\notice',
          'You cannot specify an interviewer for site appointments.', __METHOD__ );
    }

    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed( array( 'completed', 'datetime' ) );
    parent::save();
    if( $update_queue ) $this->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_interview()->get_participant();
    parent::delete();
    $db_participant->repopulate_queue( true );
  }
  
  /**
   * Determines whether an appointment's date is valid.
   * 
   * This function will make sure the participant's start qnaire date (from the queues) does
   * not come after the current date.  It will also ensure the participant has a valid qnaire.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @throws exception\runtime
   * @access public
   */
  public function validate_date()
  {
    // make sure the interview is ready for the appointment type (home/site)
    // (don't use $this->get_interview(), the record may not have been created yet)
    $db_interview = lib::create( 'database\interview', $this->interview_id );
    $db_participant = $db_interview->get_participant();

    // check the qnaire start date
    $start_qnaire_date = $db_participant->get_start_qnaire_date();
    if( !is_null( $start_qnaire_date ) && $start_qnaire_date > util::get_datetime_object() ) return false;

    // check the qnaire
    $db_effective_qnaire = $db_participant->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) ||
       $db_effective_qnaire->id != $db_interview->get_qnaire()->id ) return false;
    
    return true;
  }

  /**
   * Get the state of the appointment as a string:
   *   completed: the appointment has been completed and the interview is done
   *   upcoming: the appointment's date/time has not yet occurred
   *   passed: the appointment's date/time has passed and the interview is not done
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state( $ignore_assignments = false )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for appointment with no id.' );
      return NULL;
    } 
    
    // first see if the appointment is complete
    if( $this->completed ) return 'completed';

    $now = util::get_datetime_object()->getTimestamp();
    $appointment = $this->datetime->getTimestamp();

    return $now < $appointment ? 'upcoming' : 'passed';
  }
}
