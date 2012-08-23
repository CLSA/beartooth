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
    $start_qnaire_date = $db_participant->start_qnaire_date;
    if( !is_null( $start_qnaire_date ) &&
        util::get_datetime_object( $start_qnaire_date ) > util::get_datetime_object() )
      return false;

    // check the qnaire type
    $type = is_null( $this->address_id ) ? 'site' : 'home';
    if( $db_participant->current_qnaire_type != $type ) return false;
    
    // TODO: need requirements for shift templates and appointment restricting before the
    //       rest of this method can be implemented
    return true;

    $db_participant = lib::create( 'database\participant', $this->participant_id );
    $db_site = $db_participant->get_primary_site();
    if( is_null( $db_site ) )
      throw lib::create( 'exception\runtime',
        'Cannot validate an appointment date, participant has no primary address.', __METHOD__ );

    // determine the appointment interval
    $interval = sprintf( 'PT%dM',
                         lib::create( 'business\setting_manager' )->get_setting(
                           'appointment',
                           'home' == $type ? 'home duration' : 'site duration',
                           $db_site ) );

    $start_datetime_obj = util::get_datetime_object( $this->datetime );
    $end_datetime_obj = clone $start_datetime_obj;
    $end_datetime_obj->add( new \DateInterval( $interval ) );

    $diffs = array();
    
    // and how many appointments are during this time?
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->where( 'DATE( datetime )', '=', $start_datetime_obj->format( 'Y-m-d' ) );
    if( !is_null( $this->id ) ) $appointment_mod->where( 'appointment.id', '!=', $this->id );
    
    if( 'site' == $type )
    {
      // link to the participant's site id
      $appointment_mod->where( 'participant_site.site_id', '=', $db_site->id );

      // determine site slots using shift template
      $shift_template_mod = lib::create( 'database\modifier' );
      $shift_template_mod->where( 'site_id', '=', $db_site->id );
      $shift_template_mod->where( 'start_date', '<=', $start_datetime_obj->format( 'Y-m-d' ) );
      $shift_template_class_name = lib::get_class_name( 'database\shift_template' );
      foreach( $shift_template_class_name::select( $shift_template_mod ) as $db_shift_template )
      {
        if( $db_shift_template->match_date( $start_datetime_obj->format( 'Y-m-d' ) ) )
        {
          $start_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->start_time, 0, -3 ) ) );
          if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[$start_time_as_int] = 0;
          $diffs[$start_time_as_int] += 1;
  
          $end_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->end_time, 0, -3 ) ) );
          if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[$end_time_as_int] = 0;
          $diffs[$end_time_as_int] -= 1;
        }
      }
    }
    // home appointments, restrict to the current user
    else
    {
      $appointment_mod->where(
        'appointment.user_id', '=', lib::create( 'business\session' )->get_user()->id );
    }

    $appointment_list = static::select( $appointment_mod );
    foreach( $appointment_list as $db_appointment )
    {
      if( !$db_appointment->completed )
      { // incomplete appointments only
        $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
  
        $start_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
        $appointment_datetime_obj->add( new \DateInterval( $interval ) );
        $end_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
  
        if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
        $diffs[ $start_time_as_int ]--;
        if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
        $diffs[ $end_time_as_int ]++;
      }
    }
    
    // if we have no diffs on this day, then the site has no slots and home has 1 slot
    if( 0 == count( $diffs ) ) return 'home' == $type ? true : false;

    // use the 'diff' arrays to define the 'times' array
    $times = array();
    ksort( $diffs );
    $num_openings = 'home' == $type ? 1 : 0;
    foreach( $diffs as $time => $diff )
    {
      $num_openings += $diff;
      $times[$time] = $num_openings;
    }

    // end day with no openings (4800 is used because it is long after the end of the day)
    $times[4800] = 'home' == $type ? 1 : 0;
    
    // Now search the times array for any 0's inside the appointment time
    // NOTE: we need to include the time immediately prior to the appointment start time
    $start_time_as_int = intval( $start_datetime_obj->format( 'Gi' ) );
    $end_time_as_int = intval( $end_datetime_obj->format( 'Gi' ) );
    $match = false;
    $last_slots = 'home' == $type ? 1 : 0;
    $last_time = 0;

    foreach( $times as $time => $slots )
    {
      // check the start time
      if( $last_time <= $start_time_as_int &&
          $time > $start_time_as_int &&
          1 > $last_slots ) return false;

      // check the end time
      if( $last_time < $end_time_as_int &&
          $time >= $end_time_as_int &&
          1 > $last_slots ) return false;

      $last_slots = $slots;
      $last_time = $time;
    }
    
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
?>
