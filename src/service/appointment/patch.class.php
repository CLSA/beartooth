<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\appointment;
use cenozo\lib, cenozo\log, beartooth\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    $db_appointment = $this->get_leaf_record();
    if( $db_appointment->has_column_changed( 'datetime' ) )
    {
      // when changing an appointment's datetime create a new appointment and set the old one as rescheduled
      $db_new_appointment = lib::create( 'database\appointment' );
      $db_new_appointment->interview_id = $db_appointment->interview_id;
      $db_new_appointment->user_id = $db_appointment->user_id;
      $db_new_appointment->address_id = $db_appointment->address_id;
      $db_new_appointment->appointment_type_id = $db_appointment->appointment_type_id;
      $db_new_appointment->datetime = $db_appointment->datetime;
      $db_new_appointment->outcome = $db_appointment->outcome;
      $db_new_appointment->save();

      // now re-load the old appointment and only change its outcome
      $db_old_appointment = lib::create( 'database\appointment', $db_appointment->id );
      $db_old_appointment->outcome = 'rescheduled';
      $db_old_appointment->save();
    }
    else
    {
      parent::execute();
    }
  }
}
