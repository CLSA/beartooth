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
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    // make sure there is a maximum of 1 future home appointment and 1 future site appointment
    $now_datetime_obj = util::get_datetime_object();
    $modifier = util::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->participant_id );
    $modifier->where( 'datetime', '>', $now_datetime_obj->format( 'Y-m-d H:i:s' ) );
    $modifier->where( 'address_id', $this->address_id ? '!=' : '=', NULL );
    if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
    $appointment_list = static::select( $modifier );
    if( 0 < count( $appointment_list ) )
    {
      $db_appointment = current( $appointment_list );
      throw util::create( 'exception\notice',
        sprintf( 'Unable to add the appointment since the participant already has an upcomming '.
                 '%s appointment scheduled for %s.',
                 is_null( $this->address_id ) ? 'site' : 'home',
                 util::get_formatted_datetime( $db_appointment->datetime ) ),
        __METHOD__ );
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
    $db_participant = util::create( 'database\participant', $this->participant_id );
    $home = (bool) $this->address_id;

    // determine the appointment interval
    $interval = sprintf( 'PT%dM',
                         bus\setting_manager::self()->get_setting(
                           'appointment',
                           $home ? 'home duration' : 'site duration' ) );

    $start_datetime_obj = util::get_datetime_object( $this->datetime );
    $end_datetime_obj = clone $start_datetime_obj;
    $end_datetime_obj->add( new \DateInterval( $interval ) );

    $diffs = array();
    
    $db_access = NULL;
    if( !$home )
    {
      $db_site = $db_participant->get_primary_site();
      if( is_null( $db_site ) )
        throw util::create( 'exception\runtime',
          'Cannot validate an appointment date, participant has no primary address.', __METHOD__ );

      // determine site slots using shift template
      $modifier = util::create( 'database\modifier' );
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
    }
    else
    { // determine this user's access
      $db_user = util::create( 'business\session' )->get_user();
      $db_site = util::create( 'business\session' )->get_site();
      $db_role = util::create( 'business\session' )->get_role();
      $db_access = access::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $db_user->id, $db_site->id, $db_role->id ) );
    }

    // and how many appointments are during this time?
    $modifier = util::create( 'database\modifier' );
    $modifier->where( 'DATE( datetime )', '=', $start_datetime_obj->format( 'Y-m-d' ) );
    if( !is_null( $this->id ) ) $modifier->where( 'appointment.id', '!=', $this->id );
    
    $appointment_list = $home
                      ? static::select_for_access( $db_access, $modifier )
                      : static::select_for_site( $db_site, $modifier );

    foreach( $appointment_list as $db_appointment )
    {
      $state = $db_appointment->get_state();
      if( 'reached' != $state && 'not reached' != $state )
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
    if( 0 == count( $diffs ) ) return $home ? true : false;

    // use the 'diff' arrays to define the 'times' array
    $times = array();
    ksort( $diffs );
    $num_openings = $home ? 1 : 0;
    foreach( $diffs as $time => $diff )
    {
      $num_openings += $diff;
      $times[$time] = $num_openings;
    }

    // end day with no openings (4800 is used because it is long after the end of the day)
    $times[4800] = $home ? 1 : 0;
    
    // Now search the times array for any 0's inside the appointment time
    // NOTE: we need to include the time immediately prior to the appointment start time
    $start_time_as_int = intval( $start_datetime_obj->format( 'Gi' ) );
    $end_time_as_int = intval( $end_datetime_obj->format( 'Gi' ) );
    $match = false;
    $last_slots = $home ? 1 : 0;
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
    // straight join the tables
    if( is_null( $modifier ) ) $modifier = util::create( 'database\modifier' );
    $modifier->where(
      'appointment.participant_id', '=', 'participant_primary_address.participant_id', false );
    $modifier->where( 'participant_primary_address.address_id', '=', 'address.id', false );
    $modifier->where( 'address.postcode', '=', 'jurisdiction.postcode', false );
    $modifier->where( 'appointment.address_id', '=', NULL );
    $modifier->where( 'jurisdiction.site_id', '=', $db_site->id );
    $sql = sprintf(
      ( $count ? 'SELECT COUNT(*) ' : 'SELECT appointment.id ' ).
      'FROM appointment, participant_primary_address, address, jurisdiction %s',
      $modifier->get_sql() );

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
   * Identical to the parent's select method but restrict to the current user's participants.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param access $db_access The access to restrict the count to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_access( $db_access, $modifier = NULL, $count = false )
  {
    // if there is no access restriction then just use the parent method
    if( is_null( $db_access ) ) return parent::select( $modifier, $count );

    $sql = sprintf(
      ( $count ? 'SELECT COUNT(*) ' : 'SELECT appointment.id ' ).
      'FROM appointment, address, jurisdiction '.
      'WHERE appointment.address_id = address.id '.
      'AND address.postcode = jurisdiction.postcode '.
      'AND jurisdiction.site_id = %s '.
      'AND ( ',
      database::format_string( $db_access->get_site()->id ) );
    
    // OR all access coverages making sure to AND NOT all other like coverages for the same site
    $first = true;
    $coverage_mod = util::create( 'database\modifier' );
    $coverage_mod->where( 'access_id', '=', $db_access->id );
    $coverage_mod->order( 'CHAR_LENGTH( postcode_mask )' );
    foreach( coverage::select( $coverage_mod ) as $db_coverage )
    {
      $sql .= sprintf( '%s ( address.postcode LIKE %s ',
                       $first ? '' : 'OR',
                       database::format_string( $db_coverage->postcode_mask ) );
      $first = false;

      // now remove the like coverages
      $inner_coverage_mod = util::create( 'database\modifier' );
      $inner_coverage_mod->where( 'access_id', '!=', $db_access->id );
      $inner_coverage_mod->where( 'access.site_id', '=', $db_access->site_id );
      $inner_coverage_mod->where( 'postcode_mask', 'LIKE', $db_coverage->postcode_mask );
      foreach( coverage::select( $inner_coverage_mod ) as $db_inner_coverage )
      {
        $sql .= sprintf( 'AND address.postcode NOT LIKE %s ',
                         database::format_string( $db_inner_coverage->postcode_mask ) );
      }
      $sql .= ') ';
    }

    // make sure to return an empty list if the access has no coverage
    $sql .= $first ? 'false )' : ') ';
    if( !is_null( $modifier ) ) $sql .= $modifier->get_sql( true );

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
   * Identical to the parent's count method but restrict to the current user's participants.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param access $db_access The access to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_access( $db_access, $modifier = NULL )
  {
    return static::select_for_access( $db_access, $modifier, true );
  }

  /**
   * Get the state of the appointment as a string:
   *   reached: the appointment was met and the participant was reached
   *   not reached: the appointment was met but the participant was not reached
   *   upcoming: the appointment's date/time has not yet occurred
   *   passed: the appointment's date/time has passed
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

    return $now < $appointment ? 'upcoming' : 'passed';
  }
}
?>
