<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * interview: record
 */
class interview extends \cenozo\database\interview
{
  /**
   * Extends parent method
   */
  public function complete( $db_credit_site = NULL, $datetime = NULL )
  {
    parent::complete( $db_credit_site );

    if( is_null( $this->end_datetime ) )
    {
      // record the qnaire as complete
      $db_participant = $this->get_participant();
      $db_completed_event_type = $this->get_qnaire()->get_completed_event_type();
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'event_type_id', '=', $db_completed_event_type->id );
      if( 0 == $db_participant->get_event_count( $modifier ) )
      {
        $session = lib::create( 'business\session' );
        $db_site = $session->get_site();
        $db_user = $session->get_user();
        $db_event = lib::create( 'database\event' );
        $db_event->participant_id = $db_participant->id;
        $db_event->event_type_id = $db_completed_event_type->id;
        $db_event->site_id = $db_site->id;
        $db_event->user_id = $db_user->id;
        $db_event->datetime = $datetime;
        $db_event->save();
      }
    }

    // fill in the outcome of all appointments with no outcome
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->where( 'outcome', '=', NULL );
    foreach( $this->get_appointment_object_list( $appointment_mod ) as $db_appointment )
    {
      $db_appointment->outcome = 'completed';
      $db_appointment->save();
    }
  }
}
