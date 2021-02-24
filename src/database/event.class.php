<?php
/**
 * event.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * event: record
 */
class event extends \cenozo\database\event
{
  /**
   * Extend parent method
   * 
   * If the event's user is set to an interviewing instance then change it instead to that instance's
   * interview_user_id
   */
  public function __set( $column_name, $value )
  {
    if( 'user_id' == $column_name && !is_null( $value ) )
    {
      // see if the user is an interviewing instance and change the value to the interview user_id instead
      $interviewing_instance_class_name = lib::get_class_name( 'database\interviewing_instance' );
      $db_interviewing_instance = $interviewing_instance_class_name::get_unique_record( 'user_id', $value );
      if( !is_null( $db_interviewing_instance ) && !is_null( $db_interviewing_instance->interviewer_user_id ) )
        $value = $db_interviewing_instance->interviewer_user_id;
    }

    parent::__set( $column_name, $value );
  }
}
