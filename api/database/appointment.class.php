<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\exception as exc;

/**
 * appointment: record
 *
 * @package beartooth\database
 */
class appointment extends record
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
   * Determines whether there are open slots available during this appointment's date/time
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @throws exception\runtime
   * @access public
   */
  public function validate_date()
  {
    if( is_null( $this->address_id ) )
      throw new exc\runtime(
        'Cannot validate appointment date, address id is not set.', __METHOD__ );

    $db_participant = new participant( $this->participant_id );
    $db_site = $db_participant->get_primary_site();
    if( is_null( $db_site ) )
      throw new exc\runtime(
        'Cannot validate an appointment date, participant has no primary address.', __METHOD__ );
    
    // determine the appointment interval
    $interval = sprintf( 'PT%dM',
                         bus\setting_manager::self()->get_setting( 'appointment', 'duration' ) );

    $start_datetime_obj = util::get_datetime_object( $this->datetime );
    $end_datetime_obj = clone $start_datetime_obj;
    $end_datetime_obj->add( new \DateInterval( $interval ) ); // appointments are one hour long

    // determine whether to test for shift templates on the appointment day
    $modifier = new modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'DATE( start_datetime )', '=', $start_datetime_obj->format( 'Y-m-d' ) );
    
    $diffs = array();

    // determine slots using shift template
    $modifier = new $modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'start_date', '<=', $start_datetime_obj->format( 'Y-m-d' ) );
    
    foreach( shift_template::select( $modifier ) as $db_shift_template )
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
    
    // and how many appointments are during this time?
    $modifier = new modifier();
    $modifier->where( 'DATE( datetime )', '=', $start_datetime_obj->format( 'Y-m-d' ) );
    if( !is_null( $this->id ) ) $modifier->where( 'appointment.id', '!=', $this->id );
    foreach( appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $state = $db_appointment->get_state();
      if( 'reached' != $state && 'not reached' != $state )
      { // incomplete appointments only
        $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
  
        $start_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
        // increment slot one hour later
        $appointment_datetime_obj->add( new \DateInterval( $interval ) );
        $end_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
  
        if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
        $diffs[ $start_time_as_int ]--;
        if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
        $diffs[ $end_time_as_int ]++;
      }
    }
    
    // if we have no diffs on this day, then we have no slots
    if( 0 == count( $diffs ) ) return false;

    // use the 'diff' arrays to define the 'times' array
    $times = array();
    ksort( $diffs );
    $num_openings = 0;
    foreach( $diffs as $time => $diff )
    {
      $num_openings += $diff;
      $times[$time] = $num_openings;
    }

    // end day with no openings (4800 is used because it is long after the end of the day)
    $times[4800] = 0;
    
    // Now search the times array for any 0's inside the appointment time
    // NOTE: we need to include the time immediately prior to the appointment start time
    $start_time_as_int = intval( $start_datetime_obj->format( 'Gi' ) );
    $end_time_as_int = intval( $end_datetime_obj->format( 'Gi' ) );
    $match = false;
    $last_slots = 0;
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
   * Identical to the parent's select method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the selection to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_site( $db_site, $modifier = NULL, $count = false )
  {
    // if there is no site restriction then just use the parent method
    if( is_null( $db_site ) ) return parent::select( $modifier, $count );
    
    $select_tables = 'appointment, address, participant';
    
    // straight join the tables
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'appointment.address_id', '=', 'address.id', false );
    $modifier->where( 'address.participant_id', '=', 'participant.id', false );

    $sql = sprintf( ( $count ? 'SELECT COUNT( %s.%s ) ' : 'SELECT %s.%s ' ).
                    'FROM %s '.
                    'WHERE ( participant.site_id = %d '.
                    '  OR address.region_id IN '.
                    '  ( SELECT id FROM region WHERE site_id = %d ) ) %s',
                    static::get_table_name(),
                    static::get_primary_key_name(),
                    $select_tables,
                    $db_site->id,
                    $db_site->id,
                    $modifier->get_sql( true ) );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
  }

  /**
   * Identical to the parent's count method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_site( $db_site, $modifier = NULL )
  {
    return static::select_for_site( $db_site, $modifier, true );
  }


  /**
   * Get the state of the appointment as a string:
   *   reached: the appointment was met and the participant was reached
   *   not reached: the appointment was met but the participant was not reached
   *   upcoming: the appointment's date/time has not yet occurred
   *   missed: the appointment was missed
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
    
    // if the appointment's reached column is set, nothing else matters
    if( !is_null( $this->reached ) ) return $this->reached ? 'reached' : 'not reached';

    $now = util::get_datetime_object()->getTimestamp();
    $appointment = util::get_datetime_object( $this->datetime )->getTimestamp();

    return $now < $appointment ? 'upcoming' : 'missed';
  }
}
?>
