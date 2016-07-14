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
class interview extends \cenozo\database\record
{
  /**
   * Get the interview's last (most recent) assignment.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_assignment()
  {
    // check the last key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query interview with no primary key.' );
      return NULL;
    }

    $select = lib::create( 'database\select' );
    $select->from( 'interview_last_assignment' );
    $select->add_column( 'assignment_id' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview_id', '=', $this->id );

    $assignment_id = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return $assignment_id ? lib::create( 'database\assignment', $assignment_id ) : NULL;
  }

  /**
   * Performes all necessary steps when completing an interview.
   * 
   * This method encapsulates all processing required when an interview is completed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_credit_site If null then the session's site is credited
   * @param DateTime $datetime When the interview was completed, or now will be used if null
   * @access public
   */
  public function complete( $db_credit_site = NULL, $datetime = NULL )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to complete interview with no primary key.' );
      return;
    }

    if( !is_null( $this->end_datetime ) )
    {
      log::warning( sprintf( 'Tried to complete interview id %d which already has an end_datetime.', $this->id ) );
    }
    else
    {
      if( is_null( $datetime ) ) $datetime = util::get_datetime_object();
      if( is_null( $db_credit_site ) ) $db_credit_site = lib::create( 'business\session' )->get_site();

      // update the record
      $this->end_datetime = $datetime;
      $this->site_id = $db_credit_site->id;
      $this->save();

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
