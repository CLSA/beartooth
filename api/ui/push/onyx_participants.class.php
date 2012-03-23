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

    // loop through the participants array
    foreach( $this->get_argument( 'Participants' ) as $uid => $participant_data )
    {
      $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
      if( is_null( $db_participant ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );

      if( !array_key_exists( 'Admin.Interview.status', $participant_data ) )
        throw lib::create( 'exception\argument',
          'Admin.Interview.status', NULL, __METHOD__ );
      $interview_status = strtolower( $participant_data['Admin.Interview.status'] );

      if( 'completed' == $interview_status )
      {
        $participant_changed = false;
        $mastodon_columns = array();

        // process fields which we want to update
        if( array_key_exists( 'Admin.Participant.firstName', $participant_data ) )
        {
          $value = $participant_data['Admin.Participant.firstName'];
          if( 0 != strcasecmp( $value, $db_participant->first_name ) )
          {
            $db_participant->first_name = $value;
            $participant_changed = true;
          }
        }

        if( array_key_exists( 'Admin.Participant.lastName', $participant_data ) )
        {
          $value = $participant_data['Admin.Participant.lastName'];
          if( 0 != strcasecmp( $value, $db_participant->last_name ) )
          {
            $db_participant->last_name = $value;
            $participant_changed = true;
          }
        }

        if( array_key_exists( 'Admin.Participant.consentToDrawBlood', $participant_data ) )
        {
          $value = $participant_data['Admin.Participant.consentToDrawBlood'];
          if( $value != $db_participant->consent_to_draw_blood )
          {
            $db_participant->consent_to_draw_blood = $value;
            $participant_changed = true;
          }
        }

        if( array_key_exists( 'Admin.Participant.gender', $participant_data ) )
          $mastodon_columns['gender'] =
            0 == strcasecmp( 'f', substr( $participant_data['Admin.Participant.gender'], 0, 1 ) )
            ? 'female' : 'male';

        if( array_key_exists( 'Admin.Participant.birthDate', $participant_data ) )
          $mastodon_columns['date_of_birth'] =
            util::get_datetime_object(
              $participant_data['Admin.Participant.birthDate'] )->format( 'Y-m-d' );

        if( $participant_changed ) $db_participant->save();
        if( 0 < count( $mastodon_columns ) )
        {
          $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
          $args = array( 'uid' => $db_participant->uid,
                         'columns' => $columns );
          $mastodon_manager->push( 'participant', 'edit', $args );
        }
      }
      else if( 'cancelled' == $interview_status || 'closed' == $interview_status )
      {
        $date = util::get_datetime_object(
          array_key_exists( 'Admin.Interview.endDate', $participant_data ) ?
            $participant_data['Admin.Interview.endDate'] : NULL )->format( 'Y-m-d' );

        // cancelled means the participant has retracted, closed means they have withdrawn
        $event = 'cancelled' == $interview_status ? 'retract' : 'withdraw';

        $columns = array( 'date' => $date,
                          'event' => $event,
                          'note' => 'Onyx interview was cancelled.' );
        $args = array( 'id' => $db_participant->id,
                       'columns' => $columns );
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
?>
