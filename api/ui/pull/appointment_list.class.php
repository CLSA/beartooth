<?php
/**
 * appointment_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Class for appointment list pull operations.
 * 
 * @abstract
 * @package beartooth\ui
 */
class appointment_list extends \cenozo\ui\pull\base_list
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
    $interval = lib::create( 'business\setting_manager' )->get_setting( 
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
      throw lib::create( 'exception\notice', 
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

    $onyx_instance_class_name = lib::get_class_name( 'database\onyx_instance' );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );

    // create a list of appointments between the start and end time
    $db_user = lib::create( 'business\session' )->get_user();
    $db_onyx = $onyx_instance_class_name::get_unique_record( 'user_id' , $db_user->id );
    
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'datetime', '>=', $this->start_datetime->format( 'Y-m-d H:i:s' ) );
    $modifier->where( 'datetime', '<', $this->end_datetime->format( 'Y-m-d H:i:s' ) );

    // determine whether this is a site instance of onyx or an interviewer's laptop
    if( is_null( $db_onyx->interviewer_user_id ) )
    { // restrict by site
      $modifier->where( 'participant_site.site_id', '=', $db_onyx->get_site()->id );
    }
    else
    { // restrict the the onyx instance's interviewer
      $modifier->where( 'appointment.user_id', '=', $db_onyx->interviewer_user_id );
    }

    $appointment_list = $appointment_class_name::select( $modifier );
    if( is_null( $appointment_list ) )
      throw lib::create( 'exception\runtime', 
        'Cannot get an appointment list for onyx', __METHOD__ );

    foreach( $appointment_list as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $db_participant = $db_appointment->get_participant();

      $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
      $participant_obj = new \stdClass();
      if( $mastodon_manager->is_enabled() )
      {
        $participant_obj = $mastodon_manager->pull( 'participant', 'primary',
                             array( 'uid' => $db_participant->uid ) );
      }
      else
      {
        throw lib::create( 'exception\runtime', 
          'Onyx requires populated dob and gender data from Mastodon', __METHOD__ );
      }

      $db_address = $db_participant->get_primary_address();

      $event = array(
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
        'postcode'  => is_null( $db_address ) ? 'NA' : $db_address->postcode );

      if( !is_null( $db_participant->next_of_kin_first_name ) )
        $event['nextOfKin.firstName'] = $db_participant->next_of_kin_first_name;
      if( !is_null( $db_participant->next_of_kin_last_name ) )
        $event['nextOfKin.lastName'] = $db_participant->next_of_kin_last_name;
      if( !is_null( $db_participant->next_of_kin_gender ) )
        $event['nextOfKin.gender'] = $db_participant->next_of_kin_gender;
      if( !is_null( $db_participant->next_of_kin_phone ) )
        $event['nextOfKin.phone'] = $db_participant->next_of_kin_phone;
      if( !is_null( $db_participant->next_of_kin_street ) )
        $event['nextOfKin.street'] = $db_participant->next_of_kin_street;
      if( !is_null( $db_participant->next_of_kin_city ) )
        $event['nextOfKin.city'] = $db_participant->next_of_kin_city;
      if( !is_null( $db_participant->next_of_kin_province ) )
        $event['nextOfKin.province'] = $db_participant->next_of_kin_province;
      if( !is_null( $db_participant->next_of_kin_postal_code ) )
        $event['nextOfKin.postalCode'] = $db_participant->next_of_kin_postal_code;

      // include consent to draw blood if this is a site appointment
      if( is_null( $db_onyx->interviewer_user_id ) )
        $event['consent_to_draw_blood'] = $db_participant->consent_to_draw_blood;

      $event_list[] = $event;
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
