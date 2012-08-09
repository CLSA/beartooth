<?php
/**
 * onyx_participants.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
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
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $participant_class_name = lib::create( 'database\participant' );
    $interview_class_name = lib::create( 'database\interview' );
    $qnaire_class_name = lib::create( 'database\qnaire' );

    // get the body of the request
    $body = http_get_request_body();
    $data = util::json_decode( $body );

    if( !is_object( $data ) )
      throw lib::create( 'exception\runtime',
        'Unable to decode request body, received: '.print_r( $body, true ), __METHOD__ );

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
            $mastodon_columns['first_name'] = $value;
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
            $mastodon_columns['last_name'] = $value;
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

        $method = 'Admin.Interview.status';
        if( !array_key_exists( $method, $object_vars ) )
          throw lib::create( 'exception\argument',
            $method, NULL, __METHOD__ );
        $interview_status = strtolower( $participant_data->$method );

        $method = 'Admin.ApplicationConfiguration.siteCode';
        if( !array_key_exists( $method, $object_vars ) )
          throw lib::create( 'exception\argument',
            $method, NULL, __METHOD__ );
        if( preg_match( '/dcs|site/i', $participant_data->$method ) ) $interview_type = 'site';
        else if( preg_match( '/home/i', $participant_data->$method ) ) $interview_type = 'home';
        else $interview_type = false;

        $method = 'Admin.Participant.nextOfKin.firstName';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_first_name ) )
          {
            $db_participant->next_of_kin_first_name = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.lastName';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_last_name ) )
          {
            $db_participant->next_of_kin_last_name = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.gender';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_gender ) )
          {
            $db_participant->next_of_kin_gender = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.phone';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_phone ) )
          {
            $db_participant->next_of_kin_phone = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.street';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_street ) )
          {
            $db_participant->next_of_kin_street = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.city';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_city ) )
          {
            $db_participant->next_of_kin_city = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.province';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_province ) )
          {
            $db_participant->next_of_kin_province = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.postalCode';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_participant->next_of_kin_postal_code ) )
          {
            $db_participant->next_of_kin_postal_code = $value;
            $participant_changed = true;
          }
        }

        // now update the participant, appointment and interview, then pass data to mastodon
        if( $participant_changed ) $db_participant->save();

        // complete all appointments in the past
        $appointment_mod = lib::create( 'database\modifier' );
        $appointment_mod->where( 'completed', '=', false );
        if( 'home' == $interview_type ) $appointment_mod->where( 'address_id', '!=', NULL );
        else if( 'site' == $interview_type ) $appointment_mod->where( 'address_id', '=', NULL );
        foreach( $db_participant->get_appointment_list( $appointment_mod ) as $db_appointment )
        {
          $db_appointment->completed = true;
          $db_appointment->save();
        }
        
        if( 'completed' == $interview_status )
        {
          // get the most recent interview of the appropriate type
          $interview_mod = lib::create( 'database\modifier' );
          $interview_mod->where( 'participant_id', '=', $db_participant->id );
          if( $interview_type ) $interview_mod->where( 'qnaire.type', '=', $interview_type );
          $interview_mod->order_desc( 'qnaire.rank' );
          $interview_list = $interview_class_name::select( $interview_mod );
          
          // make sure the interview exists
          if( 0 == count( $interview_list ) )
            throw lib::create( 'exception\runtime',
              sprintf( 'Trying to export %s interview for participant %s but the '.
                       'interview doesn\'t exist.',
                       $interview_type,
                       $db_participant->uid ),
              __METHOD__ );
          
          // mark the interview as completed
          $db_interview = current( $interview_list );
          $db_interview->completed = true;
          $db_interview->save();
        }

        if( 0 < count( $mastodon_columns ) )
        {
          $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
          $args = array();
          $args['columns'] = $mastodon_columns;
          $args['noid']['participant']['uid'] = $db_participant->uid;
          $mastodon_manager->push( 'participant', 'edit', $args );
        }
      }
    }
  }
}
?>
