<?php
/**
 * home_appointment_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * pull: home appointment feed
 * 
 * @package beartooth\ui
 */
class home_appointment_feed extends base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the home appointment feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'home_appointment', $args );
  }
  
  /**
   * Returns the data provided by this feed.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    // figure out this user's interview access
    $db_user = lib::create( 'business\session' )->get_user();
    $db_site = lib::create( 'business\session' )->get_site();
    $db_role = lib::create( 'business\session' )->get_role();
    $class_name = lib::get_class_name( 'database\access' );
    $db_access = $class_name::get_unique_record(
      array( 'user_id', 'site_id', 'role_id' ),
      array( $db_user->id, $db_site->id, $db_role->id ) );

    // create a list of home appointments between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'appointment.address_id', '!=', NULL );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );

    $event_list = array();
    $class_name = lib::get_class_name( 'database\appointment' );
    foreach( $class_name::select_for_access( $db_access, $modifier ) as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $end_datetime_obj->modify(
        sprintf( '+%d minute',
        lib::create( 'business\setting_manager' )->get_setting( 'appointment', 'home duration' ) ) );

      $db_participant = $db_appointment->get_participant();
      $event_list[] = array(
        'id'      => $db_appointment->id,
        'title'   => is_null( $db_participant->uid ) || 0 == strlen( $db_participant->uid ) ?
                      $db_participant->first_name.' '.$db_participant->last_name :
                      $db_participant->uid,
        'allDay'  => false,
        'start'   => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'end'     => $end_datetime_obj->format( \DateTime::ISO8601 ) );
    }

    return $event_list;
  }
}
?>
