<?php
/**
 * site_appointment_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * pull: site appointment feed
 */
class site_appointment_feed extends \cenozo\ui\pull\base_feed
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
    parent::__construct( 'site_appointment', $args );
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
    $db_site = lib::create( 'business\session' )->get_site();

    // create a list of site appointments between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_site.site_id', '=', $db_site->id );
    $modifier->where( 'appointment.address_id', '=', NULL );
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
        $setting_manager->get_setting( 'appointment', 'site duration' ) ) );

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

    $this->data = $event_list;
  }
}
?>
