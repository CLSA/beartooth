<?php
/**
 * phone_call_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * push: phone_call end
 *
 * Assigns a participant to an phone_call.
 * @package beartooth\ui
 */
class phone_call_end extends \beartooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', 'end', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $session = lib::create( 'business\session' );
    $is_interviewer = 'interviewer' == $session->get_role()->name;

    // disconnect voip
    lib::create( 'business\voip_manager' )->get_call();
    if( !is_null( $voip_call ) ) $voip_call->hang_up();

    if( $is_interviewer )
    { // set the end time and status of the call
      $db_phone_call = $session->get_current_phone_call();
      if( !is_null( $db_phone_call ) )
      {
        $date_obj = util::get_datetime_object();
        $db_phone_call->end_datetime = $date_obj->format( 'Y-m-d H:i:s' );
        $db_phone_call->status = $this->get_argument( 'status' );
        $db_phone_call->save();

        // if the status is "disconnected" or "wrong number" deactivate the phone and make a note
        // that the number has been disconnected
        if( 'disconnected' == $db_phone_call->status ||
            'wrong number' == $db_phone_call->status )
        {
          $db_phone = lib::create( 'database\phone', $db_phone_call->phone_id );
          if( !is_null( $db_phone ) )
          {
            $note = sprintf( 'This phone number has been disabled because a call was made to it '.
                             'on %s at %s '.
                             'by interviewer id %d (%s) '.
                             'with the result of "%s".',
                             util::get_formatted_date( $db_phone_call->end_datetime ),
                             util::get_formatted_time( $db_phone_call->end_datetime ),
                             $session->get_user()->id,
                             $session->get_user()->name,
                             $db_phone_call->status );

            // keep the old note if there is one
            $note = is_null( $db_phone->note ) ? $note : $db_phone->note."\n\n".$note;

            // apply the change using an operation (so that Mastodon is also updated)
            $args = array(
              'id' => $db_phone->id,
              'columns' => array(
                'active' => false,
                'note' => $note ) );
            $operation = lib::create( 'ui\push\phone_edit', $args );
            $operation->finish();
          }
        }
      }
    }
  }
}
?>
