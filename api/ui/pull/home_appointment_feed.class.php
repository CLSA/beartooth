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
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );

    // create a list of home appointments between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join(
      'participant_site', 'interview.participant_id', 'participant_site.participant_id' );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );
    $modifier->where( 'qnaire.type', '=', 'home' );
    $modifier->where(
      'participant_site.site_id', '=', lib::create( 'business\session' )->get_site()->id );

    // do not include participants in the ineligible queue unless the appointment is complete
    $db_queue = $queue_class_name::get_unique_record( 'name', 'ineligible' );
    $modifier->where_bracket( true );
    $modifier->where(
      'interview.participant_id', 'NOT IN', $db_queue->get_participant_idlist() );
    $modifier->or_where( 'appointment.completed', '=', true );
    $modifier->where_bracket( false );

    if( 'coordinator' != $db_role->name )
    { // for interviews only show their personal appointments
      $modifier->where(
        'appointment.user_id', '=', lib::create( 'business\session' )->get_user()->id );
    }

    $this->data = array();
    foreach( $appointment_class_name::select( $modifier ) as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $end_datetime_obj->modify(
        sprintf( '+%d minute',
        $setting_manager->get_setting( 'appointment', 'home duration' ) ) );

      $db_participant = $db_appointment->get_interview()->get_participant();
      $title = 'coordinator' == $db_role->name
             ? sprintf( '%s (%s)',
                        $db_appointment->get_user()->name,
                        $db_appointment->get_participant()->uid )
             : $db_appointment->get_participant()->uid;
      $this->data[] = array(
        'id'      => $db_appointment->id,
        'title'   => $title,
        'allDay'  => false,
        'start'   => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'end'     => $end_datetime_obj->format( \DateTime::ISO8601 ) );
    }
  }
}
