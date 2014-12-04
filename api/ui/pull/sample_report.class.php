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
    $database_class_name = lib::get_class_name( 'database\database' );
    $session = lib::create( 'business\session' );
    $timezone = $session->get_site()->timezone;

    $site_id = $this->get_argument( 'restrict_site_id' );
    $db_site = $site_id ? lib::create( 'database\site', $site_id ) : NULL;
    $db_quota = lib::create( 'database\quota', $this->get_argument( 'quota_id' ) );

    $this->add_title(
      sprintf( 'For %s (%s, %s)',
               is_null( $db_site ) ? 'All sites' : $db_site->name,
               $db_quota->gender,
               $db_quota->get_age_group()->to_string() ) );

    $service_id = $session->get_service()->id;
    $sql = sprintf(
      'SELECT participant.uid AS UID, '.
             'site.name AS Site, '.
             'IF( participant.active, "yes", "no" ) AS Active, '.
             'low_education AS LowEd, '.
             'IFNULL( state.name, "none" ) AS State, '.
             'IFNULL( DATE( CONVERT_TZ( import_event.datetime, "UTC", %s ) ), "unknown" ) AS Imported, '.
             'DATE( CONVERT_TZ( service_has_participant.datetime, "UTC", %s ) ) AS Released, '.
             'IF( participant.email IS NULL, "no", "yes" ) AS HasEmail, '.
             'IFNULL( home_assignment_count.total, 0 ) AS HomeAssignments, '.
             // the home interview date is:
             'IF( home_interview.completed, '.
                 // if completed, the date of the event the interview was completed
                 'IF( home_event.datetime IS NULL, '.
                     '"unknown", '.
                     'DATE( CONVERT_TZ( home_event.datetime, "UTC", %s ) ) '.
                 '), '.
                 // if not completed then the date of the next home appointment
                 'IF( home_appointment.id IS NOT NULL, '.
                     'DATE( CONVERT_TZ( home_appointment.datetime, "UTC", %s ) ), '.
                     '"none" '.
                 ') '.
             ') AS HomeInterviewDate, '.
             // the home interviewer is:
             'IF( home_interview.completed, '.
                 // if completed then the interview has been exported (no way to know the interviewer)
                 '"(exported)", '.
                 // if not completed then the user of the next home appointment
                 'IF( user.id IS NOT NULL, user.name, "n/a" ) '.
             ') '.
             ' AS HomeInterviewer, '.
             'IF( home_interview.completed, "yes", "no" ) AS HomeCompleted, '.
             'IFNULL( site_assignment_count.total, 0 ) AS SiteAssignments, '.
             // the site interview date is the date of the next site appointment,
             // if it does not have an address
             'IF( site_appointment.id IS NOT NULL, '.
                 'DATE( CONVERT_TZ( site_appointment.datetime, "UTC", %s ) ), '.
                 '"none" '.
             ') AS SiteInterviewDate, '.
             'IFNULL( DATE( CONVERT_TZ( callback.datetime, "UTC", %s ) ), "none" ) AS Callback '.
    // main table to select from
      'FROM participant ',
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ) );

    // now create the modifier for the query
    $modifier = lib::create( 'database\modifier' );

    // participants have to be released to this service
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'service_has_participant.participant_id', false );
    $join_mod->where( 'service_has_participant.service_id', '=', $service_id );
    $modifier->join_modifier( 'service_has_participant', $join_mod );

    // get the participant's state (if they have one)
    $modifier->left_join( 'state', 'participant.state_id', 'state.id' );
    
    // get the participant's site for this service
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
    $join_mod->where( 'participant_site.service_id', '=', $service_id );
    $modifier->left_join_modifier( 'participant_site', $join_mod );
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );
    
    // get the participant's current consent status
    $modifier->join( 'participant_last_consent',
      'participant.id', 'participant_last_consent.participant_id' );
    
    // get the event where the participant was first imported
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'import_event.participant_id', false );
    $join_mod->where( 'import_event.datetime', '=', '( '.
      'SELECT MIN( datetime ) '.
      'FROM event AS first_event '.
      'WHERE import_event.participant_id = first_event.participant_id )', false );
    $modifier->left_join_modifier( 'event AS import_event', $join_mod );
    
    // get the participant's last callback
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'callback.participant_id' );
    $join_mod->where( 'callback.datetime', '=', '( '.
        'SELECT MAX( datetime ) '.
        'FROM callback AS last_callback '.
        'WHERE callback.participant_id = last_callback.participant_id '.
      ') ' );
    $modifier->left_join_modifier( 'callback', $join_mod );
    
    // get the participant's home interview
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'home_interview.participant_id', false );
    $join_mod->where(
      'home_interview.qnaire_id', '=', '( SELECT id FROM qnaire WHERE rank = 1 )', false );
    $modifier->left_join_modifier( 'interview AS home_interview', $join_mod );
    
    // get the home interview's last appointment
    $modifier->left_join( 'interview_last_appointment AS interview_last_home_appointment',
      'home_interview.id', 'interview_last_home_appointment.interview_id' );
    $modifier->left_join( 'home_appointment',
      'interview_last_home_appointment.appointment_id', 'home_appointment.id' );
    
    // get the user from the last appointment
    $modifier->left_join( 'user', 'home_appointment.user_id', 'user.id' );
    
    // get the event associated with the home interview complete
    $modifier->left_join( 'qnaire AS home_qnaire',
      'home_interview.qnaire_id', 'home_qnaire.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'home_event.participant_id', false );
    $join_mod->where(
      'home_event.event_type_id', '=', 'home_qnaire.completed_event_type_id', false );
    $modifier->left_join_modifier( 'event AS home_event', $join_mod );
    
    // get the total number of assignments in the home interview
    $modifier->left_join( '( '.
        'SELECT interview_id, COUNT(*) AS total '.
        'FROM assignment '.
        'JOIN interview ON assignment.interview_id = interview.id AND interview.qnaire_id = 1 '.
        'GROUP BY interview_id '.
      ') AS home_assignment_count',
      'home_interview.id', 'home_assignment_count.interview_id' );
    
    // get the participant's site interview
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'site_interview.participant_id', false );
    $join_mod->where(
      'site_interview.qnaire_id', '=', '( SELECT id FROM qnaire WHERE rank = 2 )', false );
    $modifier->left_join_modifier( 'interview AS site_interview', $join_mod );
    
    // get the site interview's last appointment
    $modifier->left_join( 'interview_last_appointment AS interview_last_site_appointment',
      'site_interview.id', 'interview_last_site_appointment.interview_id' );
    $modifier->left_join( 'site_appointment',
      'interview_last_site_appointment.appointment_id', 'site_appointment.id' );
    
    // get the total number of assignments in the site interview
    $modifier->left_join( '( '.
        'SELECT interview_id, COUNT(*) AS total '.
        'FROM assignment '.
        'JOIN interview ON assignment.interview_id = interview.id AND interview.qnaire_id = 2 '.
        'GROUP BY interview_id '.
      ') AS site_assignment_count',
      'site_interview.id', 'site_assignment_count.interview_id' );
    
    // get the participant's last interview
    $modifier->left_join( 'participant_last_interview',
      'participant.id', 'participant_last_interview.participant_id' );
    $modifier->left_join( 'interview AS last_interview',
      'participant_last_interview.interview_id', 'last_interview.id' );
    
    // get the last interview's qnaire
    $modifier->left_join( 'qnaire AS last_qnaire',
      'last_interview.qnaire_id', 'last_qnaire.id' );

    // restrict to participants who have been released to this service
    $modifier->where( 'service_has_participant.datetime', '!=', 'NULL' );
    
    // restrict to the selected quota
    $modifier->where( 'gender', '=', $db_quota->gender );
    $modifier->where( 'age_group_id', '=', $db_quota->age_group_id );
    
    // remove any participants who have negative consent
    $modifier->where( 'IFNULL( participant_last_consent.accept, true )', '=', true );
    
    // restrict to partcipants who haven't completed their site (2nd) interview
    $modifier->where_bracket( true );
    $modifier->where( 'IFNULL( last_qnaire.rank, 1 )', '=', 1 );
    $modifier->where_bracket( true, true ); // or
    $modifier->where( 'last_qnaire.rank', '=', 2 );
    $modifier->where( 'last_interview.completed', '=', 0 );
    $modifier->where_bracket( true );
    $modifier->where_bracket( true );

    // restrict to a particular site, if needed
    if( !is_null( $db_site ) ) $modifier->where( 'participant_site.site_id', '=', $db_site->id );

    $modifier->order( 'uid' );

    // now run the query and build the table contents from its result
    $header = NULL;
    $content = array();
    foreach( $session->get_database()->get_all( $sql.$modifier->get_sql() ) as $row )
    {
      if( is_null( $header ) ) $header = array_keys( $row );
      $content[] = array_values( $row );
    }
    
    $this->add_table( NULL, $header, $content, NULL );
  }
}
