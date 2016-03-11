<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\record
{
  // TODO: document
  function process_callbacks()
  {
    $db_queue = $this->get_queue();

    // set the assignment and reached columns in callbacks
    if( $db_queue->from_callback() )
    {
      $db_interview = $this->get_interview();
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', '=', NULL );
      $callback_list = $db_interview->get_callback_object_list( $modifier );
      if( count( $callback_list ) )
      {
        $db_callback = current( $callback_list );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'status', '=', 'contacted' );
        $db_callback->reached = 0 < $record->get_phone_call_count( $modifier );
        $db_callback->assignment_id = $db_assignment->id;
        $db_callback->save();
      }
    }
  }
}
