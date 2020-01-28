<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
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
   * @access public
   */
  public function save()
  {
    // make sure there is a maximum of 1 unresolved appointment per interview
    if( is_null( $this->id ) && is_null( $this->outcome ) )
    {
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'outcome', '=', NULL );
      if( !is_null( $this->id ) ) $appointment_mod->where( 'id', '!=', $this->id );
      $appointment_mod->order( 'datetime' );

      // cancel any missed appointments
      foreach( $this->get_interview()->get_appointment_object_list( $appointment_mod ) as $db_appointment )
      {
        if( $db_appointment->datetime < util::get_datetime_object() )
        {
          $db_appointment->outcome = 'cancelled';
          $db_appointment->save();
        }
        else
        {
          throw lib::create( 'exception\notice',
            'Cannot have more than one incomplete appointment per interview.', __METHOD__ );
        }
      }
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

    // if the datetime is changed then update the mail
    $datetime_changed = $this->has_column_changed( 'datetime' );

    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed( array( 'outcome', 'datetime' ) );

    parent::save();

    if( $update_queue ) $this->get_interview()->get_participant()->repopulate_queue( true );

    if( $datetime_changed ) $this->update_mail();
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_interview()->get_participant();

    // remove email reinders
    $this->remove_mail();

    parent::delete();

    $db_participant->repopulate_queue( true );
  }
  
  /**
   * Determines whether all mandatory scripts required by this appointment have been completed
   * 
   * @return boolean
   * @access public
   */
  public function are_scripts_complete()
  {
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

    $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );

    $old_survey_sid = $survey_class_name::get_sid();
    $old_token_sid = $tokens_class_name::get_sid();

    $db_interview = $this->get_interview();
    $db_participant = $db_interview->get_participant();

    $script_sel = lib::create( 'database\select' );
    $script_sel->add_column( 'sid' );
    $script_sel->add_column( 'pine_qnaire_id' );
    $script_sel->add_column( 'repeated' );
    $completed = true;
    foreach( $db_interview->get_qnaire()->get_script_list( $script_sel ) as $row )
    {
      if( !is_null( $row['pine_qnaire_id'] ) )
      {
        $response = $cenozo_manager->get( sprintf(
          'qnaire/%d/response/participant_id=%d?no_activity=1&select={"column":["submitted"]}',
          $row['pine_qnaire_id'],
          $db_participant->id
        ) );
        if( !$response->submitted )
        {
          $completed = false;
          break;
        }
      }
      else if( !is_null( $row['sid'] ) )
      {
        $survey_class_name::set_sid( $row['sid'] );
        $survey_mod = lib::create( 'database\modifier' );
        $tokens_class_name::where_token( $survey_mod, $db_participant, $row['repeated'] );
        if( 0 == $survey_class_name::count( $survey_mod ) )
        {
          $completed = false;
          break;
        }
      }
    }

    $survey_class_name::set_sid( $old_survey_sid );
    $tokens_class_name::set_sid( $old_token_sid );

    return $completed;
  }

  /**
   * Determines whether an appointment's date is valid.
   * 
   * This function will make sure the participant's start qnaire date (from the queues) does
   * not come after the current date.  It will also ensure the participant has a valid qnaire.
   * @return boolean
   * @access public
   */
  public function validate_date()
  {
    // make sure the interview is ready for the appointment type (home/site)
    $db_interview = $this->get_interview();
    $db_participant = $db_interview->get_participant();

    // check the qnaire start date
    $start_qnaire_date = $db_participant->get_start_qnaire_date();
    if( !is_null( $start_qnaire_date ) && $start_qnaire_date > util::get_datetime_object() ) return false;

    // check the qnaire
    $db_effective_qnaire = $db_participant->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) ||
       $db_effective_qnaire->id != $db_interview->qnaire_id ) return false;
    
    return true;
  }

  /**
   * Get the state of the appointment as a string:
   *   completed: the appointment has been completed
   *   cancelled: the appointment has been cancelled
   *   upcoming: the appointment's date/time has not yet occurred
   *   passed: the appointment's date/time has passed and the interview is not done
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
    if( !is_null( $this->outcome ) ) return $this->outcome;

    $now = util::get_datetime_object()->getTimestamp();
    $appointment = $this->datetime->getTimestamp();

    return $now < $appointment ? 'upcoming' : 'passed';
  }

  /**
   * Adds email reminders for this appointment
   * @access public
   */
  public function add_mail()
  {
    $db_site = lib::create( 'business\session' )->get_site();
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'appointment_type_id', '=', $this->appointment_type_id );
    $modifier->where( 'language_id', '=', $this->get_interview()->get_participant()->language_id );
    foreach( $db_site->get_appointment_mail_object_list( $modifier ) as $db_appointment_mail )
      $db_appointment_mail->add_mail( $this );
  }

  /**
   * Removes email reminders for this appointment
   * @return The number of mail records deleted
   * @access public
   */
  public function remove_mail()
  {
    $count = 0;
    foreach( $this->get_mail_object_list() as $db_mail )
    {
      $db_mail->delete();
      $count++;
    }

    return $count;
  }

  /**
   * Changes any existing mail records associated with this appointment to the current start datetime
   * 
   * This should be called whenever the appointment's start vacancy (start datetime) is changed.  Note
   * that mail will only be updated if it already exists.  If there is no mail associated with the
   * appointment then no new mail will be created.
   * @access public
   */
  public function update_mail()
  {
    if( 0 < $this->remove_mail() ) $this->add_mail();
  }
}
