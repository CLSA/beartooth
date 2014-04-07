<?php
/**
 * sample.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * pull sample report
 * 
 * @abstract
 */
class sample_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'sample', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::create( 'database\participant' );
    $qnaire_class_name = lib::create( 'database\qnaire' );

    $site_id = $this->get_argument( 'restrict_site_id' );
    $db_site = $site_id ? lib::create( 'database\site', $site_id ) : NULL;
    $db_quota = lib::create( 'database\quota', $this->get_argument( 'quota_id' ) );

    $this->add_title(
      sprintf( 'For %s (%s, %s)',
               is_null( $db_site ) ? 'All sites' : $db_site->name,
               $db_quota->gender,
               $db_quota->get_age_group()->to_string() ) );

    $participant_mod = lib::create( 'database\modifier' );
    $participant_mod->where( 'gender', '=', $db_quota->gender );
    $participant_mod->where( 'age_group_id', '=', $db_quota->age_group_id );
    if( !is_null( $db_site ) )
      $participant_mod->where( 'participant_site.site_id', '=', $db_site->id );
    $participant_mod->where( 'IFNULL( participant_last_consent.accept, true )', '=', true );
    $participant_mod->where( 'interview.qnaire_id', '=', 'qnaire.id', false );
    $participant_mod->where_bracket( true );
    $participant_mod->where( 'qnaire.rank', '=', 1 );
    $participant_mod->where_bracket( true, true ); // or
    $participant_mod->where( 'qnaire.rank', '=', 2 );
    $participant_mod->where( 'interview.completed', '=', 0 );
    $participant_mod->where_bracket( false );
    $participant_mod->where_bracket( false );

    foreach( $participant_class_name::select( $participant_mod ) as $db_participant )
    {
      // determine the date the participant was imported
      $import_date = 'unknown';
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where(
        'event_type.name', 'IN', array( 'consent to contact signed', 'imported by rdd' ) );
      $event_list = $db_participant->get_event_list( $event_mod );
      if( 0 < count( $event_list ) )
      {
        $db_event = current( $event_list );
        $import_date = util::get_datetime_object( $db_event->datetime )->format( 'Y-m-d' );
      }

      // get the home and site interview records (if they exist)
      $db_home_interview = NULL;
      $db_site_interview = NULL;
      $interview_list = $db_participant->get_interview_list();
      foreach( $interview_list as $db_interview )
      {
        if( 1 == $db_interview->get_qnaire()->rank ) $db_home_interview = $db_interview;
        else if( 2 == $db_interview->get_qnaire()->rank ) $db_site_interview = $db_interview;
      }

      // determine the number of home and site assignment counts and complete dates
      $home_interview_date = 'none';
      $home_interviewer = 'n/a';
      $site_interview_date = 'none';
      if( is_null( $db_home_interview ) || !$db_home_interview->completed )
      { // home interview isn't complete, the interview date will be the last appointment
        $appointment_mod = lib::create( 'database\modifier' );
        $appointment_mod->order_desc( 'datetime' );
        $appointment_mod->limit( 1 );
        $appointment_list = $db_participant->get_appointment_list();
        if( 0 < count( $appointment_list ) )
        {
          $db_appointment = current( $appointment_list );
          $db_user = $db_appointment->get_user();
          $home_interview_date =
            util::get_datetime_object( $db_appointment->datetime )->format( 'Y-m-d' );
          $home_interviewer = is_null( $db_user ) ? 'unset' : $db_user->name;
        }
      }
      else // the home interview is complete, get it's complete time from events
      {
        $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', 1 );

        $event_mod = lib::create( 'database\modifier' );
        $event_mod->where( 'event_type_id', '=', $db_qnaire->get_completed_event_type()->id );
        $event_list = $db_participant->get_event_list( $event_mod );
        if( 0 < count( $event_list ) )
        {
          $db_event = current( $event_list );
          $home_interview_date = 
            util::get_datetime_object( $db_event->datetime )->format( 'Y-m-d' );
          $home_interviewer = '(exported)';
        }
        else // the interview is complete yet there is no event
        {
          $home_interview_date = 'unknown';
          $home_interviewer = 'unknown';
        }

        // if the home interview is complete then the site interview may be as well
        if( is_null( $db_site_interview ) || !$db_site_interview->completed )
        { // site interview isn't complete, the interview date will be the last appointment
          $appointment_mod = lib::create( 'database\modifier' );
          $appointment_mod->order_desc( 'datetime' );
          $appointment_mod->limit( 1 );
          $appointment_list = $db_participant->get_appointment_list( $appointment_mod );
          if( 0 < count( $appointment_list ) )
          {
            $db_appointment = current( $appointment_list );
            if( is_null( $db_appointment->address_id ) )
              $site_interview_date =
                util::get_datetime_object( $db_appointment->datetime )->format( 'Y-m-d' );
          }
        }
        else // the site interview is complete, get it's complete time from events
        {
          $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', 2 );

          $event_mod = lib::create( 'database\modifier' );
          $event_mod->where( 'event_type_id', '=', $db_qnaire->get_completed_event_type()->id );
          $event_list = $db_participant->get_event_list( $event_mod );
          if( 0 < count( $event_list ) )
          {
            $db_event = current( $event_list );
            $site_interview_date = 
              util::get_datetime_object( $db_event->datetime )->format( 'Y-m-d' );
          }
          else // the interview is complete yet there is no event
          {
            $site_interview_date = 'unknown';
          }
        }
      }

      // get the last callback
      $callback_mod = lib::create( 'database\modifier' );
      $callback_mod->order_desc( 'datetime' );
      $callback_mod->limit( 1 );
      $callback_list = $db_participant->get_callback_list();
      $db_callback = 0 < count( $callback_list ) ? current( $callback_list ) : NULL;
      $db_effective_site = $db_participant->get_effective_site();
      $db_state = $db_participant->get_state();

      $import_datetime_object = 
      $content[] = array(
        $db_participant->uid,
        is_null( $db_effective_site ) ? 'none' : $db_participant->get_effective_site()->name,
        $db_participant->active ? 'yes' : 'no',
        is_null( $db_state ) ? 'none' : $db_state->name,
        $import_date,
        $db_participant->get_release_date()->format( 'Y-m-d' ),
        is_null( $db_participant->email ) ? 'no' : 'yes',
        is_null( $db_home_interview ) ? 0 : $db_home_interview->get_assignment_count(),
        $home_interview_date,
        $home_interviewer,
        is_null( $db_home_interview ) ? 'no' : ( $db_home_interview->completed ? 'yes' : 'no' ),
        is_null( $db_site_interview ) ? 0 : $db_site_interview->get_assignment_count(),
        $site_interview_date,
        is_null( $db_callback ) ? 'none' : $db_callback->datetime );
    }

    $header = array(
      'UID',
      'Site',
      'Active',
      'Condition',
      'Imported',
      'Released',
      'Has Email',
      '# Home Assignments',
      'Home Interview Date',
      'Home Interviewer',
      'Home Completed',
      '# Site Assignments',
      'Site Interview Date',
      'Callback' );

    $this->add_table( NULL, $header, $content, NULL );
  }
}
