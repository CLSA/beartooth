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
    $event_type_class_name = lib::create( 'database\event_type' );

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

        $db_next_of_kin = $db_participant->get_next_of_kin();
        if( is_null( $db_next_of_kin ) )
        {
          $db_next_of_kin = lib::create( 'database\next_of_kin' );
          $db_next_of_kin->participant_id = $db_participant->id;
        }

        $db_data_collection = $db_participant->get_data_collection();
        if( is_null( $db_data_collection ) )
        {
          $db_data_collection = lib::create( 'database\data_collection' );
          $db_data_collection->participant_id = $db_participant->id;
        }

        $participant_changed = false;
        $next_of_kin_changed = false;
        $data_collection_changed = false;

        // process fields which we want to update
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
          if( is_string( $value ) ) $value = preg_match( '/y|yes|true|1/i', $value ) ? 1 : 0;
          else $value = $value ? 1 : 0;
          if( is_null( $db_data_collection->draw_blood ) ||
              $value != $db_data_collection->draw_blood )
          {
            $db_data_collection->draw_blood = $value;
            $data_collection_changed = true;
          }
        }

        $method = 'Admin.Participant.gender';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value =
            0 == strcasecmp( 'f', substr( $participant_data->$method, 0, 1 ) )
            ? 'female' : 'male';
          if( $value != $db_participant->gender )
          {
            $db_participant->gender = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.birthDate';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = util::get_datetime_object( $participant_data->$method )->format( 'Y-m-d' );
          if( $value != $db_participant->date_of_birth )
          {
            $db_participant->date_of_birth = $value;
            $participant_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.firstName';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->first_name ) )
          {
            $db_next_of_kin->first_name = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.lastName';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->last_name ) )
          {
            $db_next_of_kin->last_name = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.gender';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->gender ) )
          {
            $db_next_of_kin->gender = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.phone';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->phone ) )
          {
            $db_next_of_kin->phone = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.street';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->street ) )
          {
            $db_next_of_kin->street = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.city';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->city ) )
          {
            $db_next_of_kin->city = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.province';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->province ) )
          {
            $db_next_of_kin->province = $value;
            $next_of_kin_changed = true;
          }
        }

        $method = 'Admin.Participant.nextOfKin.postalCode';
        if( array_key_exists( $method, $object_vars ) )
        {
          $value = $participant_data->$method;
          if( 0 != strcasecmp( $value, $db_next_of_kin->postal_code ) )
          {
            $db_next_of_kin->postal_code = $value;
            $next_of_kin_changed = true;
          }
        }

        // now update the database
        if( $participant_changed ) $db_participant->save();
        if( $next_of_kin_changed ) $db_next_of_kin->save();
        if( $data_collection_changed ) $db_data_collection->save();

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

          // record the event (if one exists)
          $event_type_name = sprintf( 'completed (%s)', $db_interview->get_qnaire()->name );
          $db_event_type = $event_type_class_name::get_unique_record( 'name', $event_type_name );
          if( !is_null( $db_event_type ) )
          {
            $db_event = lib::create( 'database\event' );
            $db_event->participant_id = $db_participant->id;
            $db_event->event_type_id = $db_event_type->id;
            $db_event->datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
            $db_event->save();
          }
        }
      }
    }
  }
}
