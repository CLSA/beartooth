<?php
/**
 * home_appointment_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * pull: home appointment feed
 */
class home_appointment_feed extends \cenozo\ui\pull\base_feed
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
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $setting_manager = lib::create( 'business\setting_manager' );
    $db_role = lib::create( 'business\session' )->get_role();

    // if we are a coordinator than show all appointments belonging to this site's interviewers
    $user_id_list = array();
    if( 'coordinator' == $db_role->name )
    {
      $db_site = lib::create( 'business\session' )->get_site();
      foreach( $db_site->get_user_list() as $db_user ) $user_id_list[] = $db_user->id;
    }
    else
    {
      $db_user = lib::create( 'business\session' )->get_user();
      $user_id_list[] = $db_user->id;
    }

    // create a list of home appointments between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'appointment.user_id', 'IN', $user_id_list );
    $modifier->where( 'appointment.address_id', '!=', NULL );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );

    $event_list = array();
    $class_name = lib::get_class_name( 'database\appointment' );
    foreach( $class_name::select( $modifier ) as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $end_datetime_obj->modify(
        sprintf( '+%d minute',
        $setting_manager->get_setting( 'appointment', 'home duration' ) ) );

      $db_participant = $db_appointment->get_participant();
      $title = 'coordinator' == $db_role->name
             ? sprintf( '%s (%s)',
                        $db_appointment->get_user()->name,
                        $db_appointment->get_participant()->uid )
             : $db_appointment->get_participant()->uid;
      $event_list[] = array(
        'id'      => $db_appointment->id,
        'title'   => $title,
        'allDay'  => false,
        'start'   => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'end'     => $end_datetime_obj->format( \DateTime::ISO8601 ) );
    }

    $this->data = $event_list;
  }
}
?>
