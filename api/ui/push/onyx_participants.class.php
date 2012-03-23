<?php
/**
 * onyx_participants.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx participants
 * 
 * Allows Onyx to update participant and interview details
 * NOTE: this class breaks the non-plural words naming convension in order to play
 *       nicely with Onyx
 * @package beartooth\ui
 */
class onyx_participants extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx', 'participants', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $participant_class_name = lib::create( 'database\participant' );

    // get the body of the request
    $data = json_decode( http_get_request_body() );

    // loop through the participants array
    foreach( $data->Participants as $participant_list )
    {
      foreach( get_object_vars( $participant_list ) as $uid => $participant_data )
      {
        $object_vars = get_object_vars( $participant_data );

        $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
        if( is_null( $db_participant ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );

        $method = 'Admin.Interview.status';
        if( !array_key_exists( $method, $object_vars ) )
          throw lib::create( 'exception\argument',
            'Admin.Interview.status', NULL, __METHOD__ );
        $interview_status = strtolower( $participant_data->$method );

        if( 'completed' == $interview_status )
        {
          $participant_changed = false;
          $mastodon_columns = array();

          // process fields which we want to update
          $method = 'Admin.Participant.firstName';
          if( array_key_exists( $method, $object_vars ) )
          {
            $value = $participant_data->$method;
            if( 0 != strcasecmp( $value, $db_participant->first_name ) )
            {
              $db_participant->first_name = $value;
              $participant_changed = true;
            }
          }

          $method = 'Admin.Participant.lastName';
          if( array_key_exists( $method, $object_vars ) )
          {
            $value = $participant_data->$method;
            if( 0 != strcasecmp( $value, $db_participant->last_name ) )
            {
              $db_participant->last_name = $value;
              $participant_changed = true;
            }
          }

          $method = 'Admin.Participant.consentToDrawBlood';
          if( array_key_exists( $method, $object_vars ) )
          {
            $value = $participant_data->$method;
            if( $value != $db_participant->consent_to_draw_blood )
            {
              $db_participant->consent_to_draw_blood = $value;
              $participant_changed = true;
            }
          }

          $method = 'Admin.Participant.gender';
          if( array_key_exists( $method, $object_vars ) )
            $mastodon_columns['gender'] =
              0 == strcasecmp( 'f', substr( $participant_data->$method, 0, 1 ) )
              ? 'female' : 'male';

          $method = 'Admin.Participant.birthDate';
          if( array_key_exists( $method, $object_vars ) )
            $mastodon_columns['date_of_birth'] =
              util::get_datetime_object(
                $participant_data->$method )->format( 'Y-m-d' );

          if( $participant_changed ) $db_participant->save();
          if( 0 < count( $mastodon_columns ) )
          {
            $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
            $args = array(
              'columns' => $mastodon_columns,
              'noid' => array(
                'participant.uid' => $db_participant->uid ) );
            $mastodon_manager->push( 'participant', 'edit', $args );
          }
        }
        else if( 'cancelled' == $interview_status || 'closed' == $interview_status )
        {
          $method = 'Admin.Interview.endDate';
          $date = util::get_datetime_object(
            array_key_exists( $method, $object_vars ) ?
              $participant_data->$method : NULL )->format( 'Y-m-d' );

          // cancelled means the participant has retracted, closed means they have withdrawn
          $event = 'cancelled' == $interview_status ? 'retract' : 'withdraw';

          $columns = array( 'participant_id' => $db_participant->id,
                            'date' => $date,
                            'event' => $event,
                            'note' => 'Onyx interview was cancelled.' );
          $args = array( 'columns' => $columns );
          $operation = lib::create( 'ui\push\consent_new', $args );
          $operation->finish();
        }
        else
        {
          throw lib::create( 'exception\argument',
            'Admin.Interview.status', $interview_status, __METHOD__ );
        }
      }
    }
  }
}
?>
