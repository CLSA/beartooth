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
      $firstDay = date( 'Y:m:d H:i:s', $timeStamp );
      $this->start_datetime = new \DateTime( $firstDay );
      $this->end_datetime = clone $this->start_datetime;
      $this->end_datetime->add( new \DateInterval( 'P1M' ) );
    }
    else if( $interval == 'W' )
    {
      $timeStamp = mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'w' ), date( 'Y' ) );
      $firstDay = date( 'Y:m:d', $timeStamp ) . ' 00:00:00';
      $this->start_datetime = new \DateTime( $firstDay );
      $this->end_datetime = clone $this->start_datetime;
      $this->end_datetime->add( new \DateInterval( 'P1W' ) );
    }
    else if( $interval == 'D' )
    {
      $this->start_datetime = clone $now_datetime_obj;
      $this->start_datetime->setTime(0,0);
      $this->end_datetime = clone( $this->start_datetime );
      $this->end_datetime->add( new \DateInterval( 'P1D' ) );
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
    $event_list = array();

    // create a list of appointments between the start and end time
    $db_user = bus\session::self()->get_user();
    $db_onyx = db\onyx_instance::get_unique_record(
      'user_id' , $db_user->id );
    
    $modifier = new db\modifier();
    $modifier->where( 'datetime', '>=', $this->start_datetime->format( 'Y-m-d H:i:s' ) );
    $modifier->where( 'datetime', '<', $this->end_datetime->format( 'Y-m-d H:i:s' ) );

    // determine whether this is a site instance of onyx or an interviewer's laptop
    $appointment_list = NULL;
    if( is_null( $db_onyx->interviewer_user_id ) )
    {
      $appointment_list = db\appointment::select_for_site( $db_onyx->get_site(), $modifier );
    }
    else
    {
      $db_role = db\role::get_unique_record( 'name', 'interviewer' );
      $db_access = db\access::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $db_onyx->interviewer_user_id, $db_onyx->site_id, $db_role->id ) );
      $appointment_list = db\appointment::select_for_access( $db_access, $modifier );
    }

    if( is_null( $appointment_list ) )
      throw new exc\runtime( 
        'Cannot get an appointment list for onyx', __METHOD__ );

    foreach( $appointment_list as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $db_participant = $db_appointment->get_participant();

      $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
      $participant_obj = new \stdClass();
      if( $mastodon_manager->is_enabled() )
      {
        $participant_obj = $mastodon_manager->pull( 'participant', 'primary',
                             array( 'uid' => $db_participant->uid ) );
      }
      else
      {
        throw new exc\runtime( 
          'Onyx requires populated dob and gender data from Mastodon', __METHOD__ );
      }

      $db_address = $db_participant->get_primary_address();

      $event_list[] = array(
        'uid'        => $db_participant->uid,
        'first_name' => $db_participant->first_name,
        'last_name'  => $db_participant->last_name,
        'dob'        => is_null( $participant_obj->data->date_of_birth )
                      ? ''
                      : util::get_datetime_object( 
                          $participant_obj->data->date_of_birth )->format( 
                            'Y-m-d' ),
        'gender'    => $participant_obj->data->gender,
        'datetime'  => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'street'    => is_null( $db_address ) ? 'NA' : $db_address->address1,
        'city'      => is_null( $db_address ) ? 'NA' : $db_address->city,
        'province'  => is_null( $db_address ) ? 'NA' : $db_address->get_region()->name,
        'postcode'  => is_null( $db_address ) ? 'NA' : $db_address->postcode,
        'consent_to_draw_blood' =>  false ); // TODO: implement this field in mastodon
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
