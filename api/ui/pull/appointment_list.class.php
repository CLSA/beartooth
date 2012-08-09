<?php
/**
 * appointment_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Class for appointment list pull operations.
 * 
 * @abstract
 */
class appointment_list extends \cenozo\ui\pull\base_list
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $span_in_days = lib::create( 'business\setting_manager' )->get_setting( 
        'appointment', 'update span' );

    $this->start_datetime = util::get_datetime_object();
    $this->start_datetime->setTime( 0, 0 );
    $this->end_datetime = clone( $this->start_datetime );
    $this->end_datetime->add( new \DateInterval( sprintf( 'P%dD', $span_in_days ) ) );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $event_list = array();

    $onyx_instance_class_name = lib::get_class_name( 'database\onyx_instance' );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    $interview_class_name = lib::get_class_name( 'database\interview' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // create a list of appointments between the start and end time
    $db_user = lib::create( 'business\session' )->get_user();
    $db_onyx = $onyx_instance_class_name::get_unique_record( 'user_id' , $db_user->id );
    
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'datetime', '>=', $this->start_datetime->format( 'Y-m-d H:i:s' ) );
    $modifier->where( 'datetime', '<', $this->end_datetime->format( 'Y-m-d H:i:s' ) );

    // determine whether this is a site instance of onyx or an interviewer's laptop
    $interview_type = is_null( $db_onyx->interviewer_user_id ) ? 'site' : 'home';

    if( 'site' == $interview_type )
    { // restrict by site
      $modifier->where( 'participant_site.site_id', '=', $db_onyx->get_site()->id );
      $modifier->where( 'appointment.address_id', '=', NULL );
    }
    else
    { // restrict the the onyx instance's interviewer
      $modifier->where( 'appointment.user_id', '=', $db_onyx->interviewer_user_id );
      $modifier->where( 'appointment.address_id', '!=', NULL );
    }

    $appointment_list = $appointment_class_name::select( $modifier );
    if( is_null( $appointment_list ) )
      throw lib::create( 'exception\runtime', 
        'Cannot get an appointment list for onyx', __METHOD__ );

    foreach( $appointment_list as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $db_participant = $db_appointment->get_participant();

      $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
      $participant_obj = new \stdClass();
      if( $mastodon_manager->is_enabled() )
      {
        $participant_obj = $mastodon_manager->pull( 'participant', 'primary',
                             array( 'uid' => $db_participant->uid ) );
      }
      else
      {
        throw lib::create( 'exception\runtime', 
          'Onyx requires populated dob and gender data from Mastodon', __METHOD__ );
      }

      $db_address = $db_participant->get_primary_address();

      $event = array(
        'uid'        => $db_participant->uid,
        'first_name' => $db_participant->first_name,
        'last_name'  => $db_participant->last_name,
        'dob'        => is_null( $participant_obj->data->date_of_birth )
                      ? ''
                      : util::get_datetime_object( 
                          $participant_obj->data->date_of_birth )->format( 
                            'Y-m-d' ),
        'gender'    => $participant_obj->data->gender,
        'datetime'  => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'street'    => is_null( $db_address ) ? 'NA' : $db_address->address1,
        'city'      => is_null( $db_address ) ? 'NA' : $db_address->city,
        'province'  => is_null( $db_address ) ? 'NA' : $db_address->get_region()->name,
        'postcode'  => is_null( $db_address ) ? 'NA' : $db_address->postcode );

      if( !is_null( $db_participant->next_of_kin_first_name ) )
        $event['nextOfKin.firstName'] = $db_participant->next_of_kin_first_name;
      if( !is_null( $db_participant->next_of_kin_last_name ) )
        $event['nextOfKin.lastName'] = $db_participant->next_of_kin_last_name;
      if( !is_null( $db_participant->next_of_kin_gender ) )
        $event['nextOfKin.gender'] = $db_participant->next_of_kin_gender;
      if( !is_null( $db_participant->next_of_kin_phone ) )
        $event['nextOfKin.phone'] = $db_participant->next_of_kin_phone;
      if( !is_null( $db_participant->next_of_kin_street ) )
        $event['nextOfKin.street'] = $db_participant->next_of_kin_street;
      if( !is_null( $db_participant->next_of_kin_city ) )
        $event['nextOfKin.city'] = $db_participant->next_of_kin_city;
      if( !is_null( $db_participant->next_of_kin_province ) )
        $event['nextOfKin.province'] = $db_participant->next_of_kin_province;
      if( !is_null( $db_participant->next_of_kin_postal_code ) )
        $event['nextOfKin.postalCode'] = $db_participant->next_of_kin_postal_code;

      // include consent to draw blood if this is a site appointment (value is a string: YES or NO)
      if( 'site' == $interview_type && $db_participant->consent_to_draw_blood )
        $event['consent_to_draw_blood'] = (bool) $db_participant->consent_to_draw_blood;

      $event_list[] = $event;

      if( !$db_appointment->completed )
      {
        // now make sure that there is an incomplete interview of the same type as the appointment
        $interview_mod = lib::create( 'database\modifier' );
        $interview_mod->where( 'qnaire.type', '=', $interview_type );
        $interview_mod->where( 'completed', '=', false );
        $interview_list = $db_participant->get_interview_list( $interview_mod );
        if( 0 == count( $interview_list ) )
        { // there is no incomplete interview for this type, so create it now
          // get the next qnaire of the next interview by seeing which was last completed
          $last_interview_mod = lib::create( 'database\modifier' );
          $last_interview_mod->where( 'participant_id', '=', $db_participant->id );
          $last_interview_mod->where( 'completed', '=', true );
          $last_interview_mod->order_desc( 'qnaire.rank' );
          $last_interview_mod->limit( 1 );
          $last_interview_list = $interview_class_name::select( $last_interview_mod );
          $rank = 1;
          if( 0 < count( $last_interview_list ) ) 
          {   
            $db_last_interview = current( $last_interview_list );
            $rank = $db_last_interview->get_qnaire()->rank + 1;
          }   
          $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', $rank );

          // check if the qnaire exists at all
          if( is_null( $db_qnaire ) )
          {
            throw lib::create( 'exception\runtime',
              sprintf( 'Tried to provide %s appointment for participant %s but participant has '.
                       'completed all interviews.',
                       $interview_type,
                       $db_participant->uid ),
              __METHOD__ );
          }
          else
          {
            // now make sure that the qnaire is of the same type as the appointment
            if( $db_qnaire->type != $interview_type )
              throw lib::create( 'exception\runtime',
                sprintf( 'Tried to provide %s appointment for participant %s but participant\'s '.
                         'next interview type is %s and rank %d.',
                         $interview_type,
                         $db_participant->uid,
                         $db_qnaire->type,
                         $db_qnaire->rank ),
                __METHOD__ );

            $db_interview = lib::create( 'database\interview' );
            $db_interview->qnaire_id = $db_qnaire->id;
            $db_interview->participant_id = $db_participant->id;
            $db_interview->completed = false;
            $db_interview->save();
          }
        }
      }
    }

    $this->data = $event_list;
  }

  /**
   * The start date/time of the appointment list
   * @var string
   * @access protected
   */
  protected $start_datetime = NULL;
  
  /**
   * The end date/time of the appointment list
   * @var string
   * @access protected
   */
  protected $end_datetime = NULL;
}
?>
