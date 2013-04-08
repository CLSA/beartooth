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
    // replace the parent class to get a specific record list
    $this->data = array();

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
      $db_next_of_kin = $db_participant->get_next_of_kin();
      $db_address = $db_participant->get_primary_address();

      $dob = $db_participant->date_of_birth
           ? util::get_datetime_object( $db_participant->date_of_birth )->format( 'Y-m-d' )
           : '';
      $data = array(
        'uid'        => $db_participant->uid,
        'first_name' => $db_participant->first_name,
        'last_name'  => $db_participant->last_name,
        'dob'        => $dob,
        'gender'    => $db_participant->gender,
        'datetime'  => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'street'    => is_null( $db_address ) ? 'NA' : $db_address->address1,
        'city'      => is_null( $db_address ) ? 'NA' : $db_address->city,
        'province'  => is_null( $db_address ) ? 'NA' : $db_address->get_region()->name,
        'postcode'  => is_null( $db_address ) ? 'NA' : $db_address->postcode );

      if( !is_null( $db_next_of_kin ) )
      {
        if( !is_null( $db_next_of_kin->first_name ) )
          $data['nextOfKin.firstName'] = $db_next_of_kin->first_name;
        if( !is_null( $db_next_of_kin->last_name ) )
          $data['nextOfKin.lastName'] = $db_next_of_kin->last_name;
        if( !is_null( $db_next_of_kin->gender ) )
          $data['nextOfKin.gender'] = $db_next_of_kin->gender;
        if( !is_null( $db_next_of_kin->phone ) )
          $data['nextOfKin.phone'] = $db_next_of_kin->phone;
        if( !is_null( $db_next_of_kin->street ) )
          $data['nextOfKin.street'] = $db_next_of_kin->street;
        if( !is_null( $db_next_of_kin->city ) )
          $data['nextOfKin.city'] = $db_next_of_kin->city;
        if( !is_null( $db_next_of_kin->province ) )
          $data['nextOfKin.province'] = $db_next_of_kin->province;
        if( !is_null( $db_next_of_kin->postal_code ) )
          $data['nextOfKin.postalCode'] = $db_next_of_kin->postal_code;
      }

      // include consent to draw blood if this is a site appointment
      if( 'site' == $interview_type )
      {
        $db_data_collection = $db_participant->get_data_collection();
        if( !is_null( $db_data_collection ) )
        {
          $db_data_collection = $db_participant->get_data_collection();
          $data['consent_to_draw_blood'] = is_null( $db_data_collection )
                                         ? NULL
                                         : $db_data_collection->consent_to_draw_blood;
        }
      }

      $this->data[] = $data;

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
