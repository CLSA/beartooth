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
    // make sure there is a maximum of 1 future appointment per interview
    if( !$this->completed )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview_id', '=', $this->interview_id );
      $modifier->where( 'datetime', '>', 'UTC_DATETIME()', false );
      if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
      $appointment_list = static::select( $modifier );
      if( 0 < count( $appointment_list ) )
      {
        $db_appointment = current( $appointment_list );
        throw lib::create( 'exception\notice',
          sprintf( 'Unable to add the appointment since the participant already has an upcomming '.
                   '%s appointment scheduled for %s.',
                   $db_appointment->get_interview()->get_qnaire()->type,
                   $db_appointment->datetime->format( 'g:i A, T' ) ),
          __METHOD__ );
      }
    }

    parent::save();
  }
  
  /**
   * Determines whether there are open slots available during this appointment's date/time.
   * The result will depend on whether the appointment has an address or not.  If not then
   * it is considered to be a site interview (and so it refers to openings to the site
   * calendar), otherwise it is considered to be a home interview (and so it refers to
   * openings to the home calendar).
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
    if( !is_null( $start_qnaire_date ) && $start_qnaire_date > util::get_datetime_object() )
      return false;

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
