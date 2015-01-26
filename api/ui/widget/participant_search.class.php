<?php
/**
 * participant_search.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget participant search
 */
class participant_search extends \cenozo\ui\widget\participant_search
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // restrict to the appointment's participants
    $db_appointment = lib::create( 'business\session' )->get_appointment();
    if( $db_appointment->release_based )
    { // make sure the participant has been released
      $this->modifier->join(
        'appointment_has_participant',
        'participant.id',
        'appointment_has_participant.participant_id' );
      $this->modifier->where( 'appointment_has_participant.datetime', '!=', NULL );
      $this->modifier->where( 'appointment_has_participant.appointment_id', '=', $db_appointment->id );
    }
    else
    { // make sure the participant is in one of the appointment's cohorts
      $this->modifier->join(
        'appointment_has_cohort',
        'participant.cohort_id',
        'appointment_has_cohort.cohort_id' );
      $this->modifier->where( 'appointment_has_cohort.appointment_id', '=', $db_appointment->id );
    }
  }
}
