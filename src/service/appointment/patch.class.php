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
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'add_mail';

    parent::prepare();
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    $db_appointment = $this->get_leaf_record();
    if( $db_appointment->has_column_changed( 'datetime' ) )
    {
      $timezone_obj = lib::create( 'business\session' )->get_user()->get_timezone_object();

      // load the old appointment to reference the original datetime
      $db_old_appointment = lib::create( 'database\appointment', $db_appointment->id );

      $old_datetime = clone $db_old_appointment->datetime;
      $old_datetime->setTimezone( $timezone_obj );
      $new_datetime = clone $db_appointment->datetime;
      $new_datetime->setTimezone( $timezone_obj );
      if( $old_datetime->format( "Y-m-d" ) == $new_datetime->format( "Y-m-d" ) )
      {
        // if the new time is on the same day then edit the existing appointment
        parent::execute();
      }
      else
      {
        // if the new time is on a different day then create a new appointment and set the old one as rescheduled
        $db_new_appointment = lib::create( 'database\appointment' );
        $db_new_appointment->interview_id = $db_appointment->interview_id;
        $db_new_appointment->user_id = $db_appointment->user_id;
        $db_new_appointment->address_id = $db_appointment->address_id;
        $db_new_appointment->appointment_type_id = $db_appointment->appointment_type_id;
        $db_new_appointment->datetime = $db_appointment->datetime;
        $db_new_appointment->save();

        // now check if the old appointment had any email reminders
        if( 0 < $db_old_appointment->get_mail_count() ) $db_old_appointment->remove_mail();

        // report back the new appointment's ID
        $this->status->set_code( 201 );
        $this->set_data( (int)$db_new_appointment->id );
      }
    }
    else
    {
      parent::execute();

      // add mail if requested to
      if( $this->get_argument( 'add_mail', false ) ) $db_appointment->add_mail();
    }
  }
}
