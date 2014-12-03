<?php
/**
 * site_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * pull: site feed
 */
class site_feed extends \cenozo\ui\pull\base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    $db_site = lib::create( 'business\session' )->get_site();

    // determine the appointment interval
    $interval = sprintf(
      'PT%dM',
      lib::create( 'business\setting_manager' )->get_setting( 'appointment', 'site duration' ) );

    // start by creating an array with one element per day in the time span
    $start_datetime_obj = util::get_datetime_object( $this->start_datetime );
    $end_datetime_obj = util::get_datetime_object( $this->end_datetime );
    
    $days = array();
    $current_datetime_obj = clone $start_datetime_obj;
    while( !$current_datetime_obj->diff( $end_datetime_obj )->invert )
    {
      $days[ $current_datetime_obj->format( 'Y-m-d' ) ] = array(
        'template' => false,
        'diffs' => array(),
        'times' => array() );
      $current_datetime_obj->add( new \DateInterval( 'P1D' ) );
    }
    
    // fill in the appointments which have not been complete
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join(
      'participant_site', 'interview.participant_id', 'participant_site.participant_id' );
    $modifier->where( 'participant_site.site_id', '=', $db_site->id );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );
    $modifier->where( 'appointment.address_id', '=', NULL );
    $modifier->order( 'datetime' );
    foreach( $appointment_class_name::select( $modifier ) as $db_appointment )
    {
      if( !$db_appointment->completed )
      { // incomplete appointments only
        $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
        $diffs = &$days[ $appointment_datetime_obj->format( 'Y-m-d' ) ]['diffs'];
  
        $start_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
        // increment slot one interval later
        $appointment_datetime_obj->add( new \DateInterval( $interval ) );
        $end_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
  
        if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
        $diffs[ $start_time_as_int ]--;
        if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
        $diffs[ $end_time_as_int ]++;
  
        // unset diffs since it is a reference
        unset( $diffs );
      }
    }
    
    // use the 'diff' arrays to define the 'times' array
    foreach( $days as $date => $day )
    {
      $num_available = 0;
      $diffs = &$days[$date]['diffs'];
      $times = &$days[$date]['times'];
      
      if( 0 < count( $diffs ) )
      {
        // sort the diff array by key (time) to make the following for-loop nice and simple
        ksort( $diffs );
  
        foreach( $diffs as $time => $diff )
        {
          $num_available += $diff;
          $times[$time] = $num_available;
        }
      }

      // unset times since it is a reference
      unset( $times );
    }

    // finally, construct the list using the 'times' array
    $start_time = false;
    $available = 0;
    $this->data = array();
    foreach( $days as $date => $day )
    {
      foreach( $day['times'] as $time => $number )
      {
        if( $number == $available ) continue;

        $minutes = $time % 100;
        $hours = ( $time - $minutes ) / 100;
        $time_string = sprintf( '%02d:%02d', $hours, $minutes );
        if( $start_time )
        {
          $end_time = $time_string;
          
          if( $available )
          {
            $end_time_for_title =
              sprintf( '%s%s%s',
                       $hours > 12 ? $hours - 12 : $hours,
                       $minutes ? ':'.sprintf( '%02d', $minutes ) : '',
                       $hours >= 12 ? 'p' : 'a' );
            $this->data[] = array(
              'title' => sprintf( ' to %s: %d slots', $end_time_for_title, $available ),
              'allDay' => false,
              'start' => $date.' '.$start_time,
              'end' => $date.' '.$end_time );
          }
        }

        // only use this time as the next start time if the available number is not 0
        $start_time = 0 < $number ? $time_string : false;
        $available = $number;
      }
    }
  }
}
