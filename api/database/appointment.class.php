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
   * Overrides the parent load method.
   * @author Patrick Emond
   * @access public
   */
  public function load()
  {
    parent::load();

    // appointments are not to the second, so remove the :00 at the end of the datetime field
    $this->datetime = substr( $this->datetime, 0, -3 );
  }

  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    // make sure there is a maximum of 1 future home appointment and 1 future site appointment
    if( !$this->completed )
    {
      $now_datetime_obj = util::get_datetime_object();
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_id', '=', $this->participant_id );
      $modifier->where( 'datetime', '>', $now_datetime_obj->format( 'Y-m-d H:i:s' ) );
      $modifier->where( 'address_id', $this->address_id ? '!=' : '=', NULL );
      if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
      $appointment_list = static::select( $modifier );
      if( 0 < count( $appointment_list ) )
      {
        $db_appointment = current( $appointment_list );
        throw lib::create( 'exception\notice',
          sprintf( 'Unable to add the appointment since the participant already has an upcomming '.
                   '%s appointment scheduled for %s.',
                   is_null( $this->address_id ) ? 'site' : 'home',
                   util::get_formatted_datetime( $db_appointment->datetime ) ),
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
    // make sure the participant is ready for the appointment type (home/site)
    // (don't use $this->get_participant(), the record may not have been created yet)
    $db_participant = lib::create( 'database\participant', $this->participant_id );

    // check the qnaire start date
    $start_qnaire_date = $db_participant->get_start_qnaire_date();
    if( !is_null( $start_qnaire_date ) && $start_qnaire_date > util::get_datetime_object() )
      return false;

    // check the qnaire type
    $type = is_null( $this->address_id ) ? 'site' : 'home';
    $db_effective_qnaire = $db_participant->get_effective_qnaire();
    if( is_null( $db_effective_qnaire ) || $db_effective_qnaire->type != $type ) return false;
    
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
    $appointment = util::get_datetime_object( $this->datetime )->getTimestamp();

    return $now < $appointment ? 'upcoming' : 'passed';
  }
}

// define the join to the participant_site table
$participant_site_mod = lib::create( 'database\modifier' );
$participant_site_mod->where(
  'appointment.participant_id', '=', 'participant_site.participant_id', false );
appointment::customize_join( 'participant_site', $participant_site_mod );
