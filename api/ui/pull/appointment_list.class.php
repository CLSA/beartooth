<?php
/**
 * appointment_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\pull;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * Class for appointment list pull operations.
 * 
 * @abstract
 * @package beartooth\ui
 */
class appointment_list extends base_list
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  

    $now_datetime_obj = util::get_datetime_object();
    $interval = bus\setting_manager::self()->get_setting( 
        'appointment', 'update interval' );

    if( $interval == 'M' )
    {
      $timeStamp = mktime( 0, 0, 0, date( 'm' ), 1, date( 'Y' ) );
      $firstDay = date( 'Y:m:d h:i:s', $timeStamp );
      $this->start_datetime = new DateTime( $firstDay );
      $this->end_datetime = clone $this->start_datetime;
      $this->end_datetime->add( new DateInterval( 'P1M' ) );
    }
    else if( $interval == 'W' )
    {
      $timeStamp = mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'w' ), date( 'Y' ) );
      $firstDay = date( 'Y:m:d', $timeStamp ) . ' 00:00:00';
      $this->start_datetime = new DateTime( $firstDay );
      $this->end_datetime = clone $this->start_datetime;
      $this->end_datetime->add( new DateInterval( 'P1W' ) );
    }
    else if( $interval == 'D' )
    {
      $this->start_datetime = clone $now_datetime_obj;
      $this->start_datetime->setTime(0,0);
      $this->end_datetime = clone $now_datetime_obj;
      $this->end_datetime->setTime(23,59,59);
    }
    else
    {
      throw new exc\notice( 
        'Invalid appointment list interval (must be either M, W or D): '.$interval, __METHOD__ );
    }
  }

  /**
   * Returns the data provided by this appointment list.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    // TODO: need to figure out which onyx instance is requesting the list
    // eg, a laptop instance of onyx for in home interviews or a DCS instance


    // create a list of appointments between the start and end time
    $modifier = new db\modifier();
    $modifier->where( 'datetime', '>=', util::get_formatted_date($this->start_datetime) );
    $modifier->where( 'datetime', '<', util::get_formatted_date($this->end_datetime) );

    $event_list = array();
    $db_site = bus\session::self()->get_site();
    db\appointment::select_for_site( $db_site, $modifier ) ;//as $db_appointment )
    die('im dead');
    foreach( db\appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $end_datetime_obj->modify(
        sprintf( '+%d minute',
        bus\setting_manager::self()->get_setting( 'appointment', 'home duration' ) ) );

      $db_participant = $db_appointment->get_address()->get_participant();

      $mastodon_manager = bus\mastodon_manager::self();
      $participant_obj = $mastodon_manager->pull( 'participant', 'primary',
        array( 'uid' => $db_participant->uid ) );

      $event_list[] = array(
        'clsa_id'   => $db_participant->uid,
        'firstName' => $db_participant->first_name,
        'lastName'  => $db_participant->last_name,
        'dob'       => $participant_obj->data->date_of_birth,
        'gender'    => $participant_obj->data->gender,
        'start'     => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'end'       => $end_datetime_obj->format( \DateTime::ISO8601 ) );
    }

    return $event_list;
  }

  /**
   * The start date/time of the appointment list
   * @var string
   * @access protected
   */
  protected $start_datetime = NULL;
  
  /**
   * The end date/time of the appointment list
   * @var string
   * @access protected
   */
  protected $end_datetime = NULL;
}
?>
