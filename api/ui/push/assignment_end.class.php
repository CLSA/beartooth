<?php
/**
 * assignment_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: assignment end
 *
 * Ends the user's current assignment.
 */
class assignment_end extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'end', $args );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure the interviewer isn't on call
    if( !is_null( lib::create( 'business\session' )->get_current_phone_call() ) )
      throw lib::create( 'exception\notice',
        'An assignment cannot be ended while in a call.', __METHOD__ );
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

    $session = lib::create( 'business\session' );
    $db_assignment = $session->get_current_assignment();
    if( !is_null( $db_assignment ) )
    {
      // if no call was made then delete the assignment
      if( 0 == $db_assignment->get_phone_call_count() )
      {
        // un-associate any callbacks associated with this assignment
        foreach( $db_assignment->get_callback_list() as $db_callback )
        {
          $db_callback->assignment_id = NULL;
          $db_callback->save();
        }

        $db_assignment->delete();
      }
      else
      {
        // if there is a callback associated with this assignment, set the status
        $callback_list = $db_assignment->get_callback_list();
        if( 0 < count( $callback_list ) )
        {
          // there should always only be one callback per assignment
          if( 1 < count( $callback_list ) )
            log::crit(
              sprintf( 'Assignment %d has more than one associated callback!',
                       $db_assignment->id ) );

          $db_callback = current( $callback_list );

          // set the callback status based on whether any calls reached the participant
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'status', '=', 'contacted' );
          $db_callback->reached = 0 < $db_assignment->get_phone_call_count( $modifier );
          $db_callback->save();
        }

        // save the assignment's end time
        $date_obj = util::get_datetime_object();
        $db_assignment->end_datetime = $date_obj->format( 'Y-m-d H:i:s' );
        $db_assignment->save();
      }

      // update this participant's queue status
      $db_assignment->get_interview()->get_participant()->update_queue_status();
    }
  }
}
