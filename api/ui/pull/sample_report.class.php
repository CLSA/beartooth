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
                 // if not completed then the date of the next appointment, if it has an address
                 'IF( appointment.id IS NOT NULL AND appointment.address_id IS NOT NULL, '.
                     'DATE( CONVERT_TZ( appointment.datetime, "UTC", %s ) ), '.
                     '"none" '.
                 ') '.
             ') AS HomeInterviewDate, '.
             // the home interviewer is:
             'IF( home_interview.completed, '.
                 // if completed then the interview has been exported (no way to know the interviewer)
                 '"(exported)", '.
                 // if not completed then the user of the next appointment, if it has an address
                 'IF( appointment.id IS NOT NULL AND appointment.address_id IS NOT NULL, '.
                     'user.name, '.
                     '"n/a" '.
                 ') '.
             ') '.
             ' AS HomeInterviewer, '.
             'IF( home_interview.completed, "yes", "no" ) AS HomeCompleted, '.
             'IFNULL( site_assignment_count.total, 0 ) AS SiteAssignments, '.
             // the site interview date is the date of the next appointment,
             // if it does not have an address
             'IF( appointment.id IS NOT NULL AND appointment.address_id IS NULL, '.
                 'DATE( CONVERT_TZ( appointment.datetime, "UTC", %s ) ), '.
                 '"none" '.
             ') AS SiteInterviewDate, '.
             'IFNULL( DATE( CONVERT_TZ( callback.datetime, "UTC", %s ) ), "none" ) AS Callback '.

      // main table to select from
      'FROM participant '.
      // participants have to be released to this service
      'JOIN service_has_participant '.
      'ON participant.id = service_has_participant.participant_id '.
      'AND service_has_participant.service_id = %s '.
      // get the participant's state (if they have one)
      'LEFT JOIN state '.
      'ON participant.state_id = state.id '.
      // get the participant's site for this service
      'LEFT JOIN participant_site '.
      'ON participant.id = participant_site.participant_id '.
      'AND participant_site.service_id = %s '.
      'LEFT JOIN site '.
      'ON participant_site.site_id = site.id '.
      // get the participant's current consent status
      'JOIN participant_last_consent '.
      'ON participant.id = participant_last_consent.participant_id '.
      // get the event where the participant was first imported
      'LEFT JOIN event AS import_event '.
      'ON participant.id = import_event.participant_id '.
      'AND import_event.datetime = ( '.
        'SELECT MIN( datetime ) '.
        'FROM event AS first_event '.
        'WHERE import_event.participant_id = first_event.participant_id '.
      ') '.
      // get the participant's last appointment
      'LEFT JOIN participant_last_appointment '.
      'ON participant.id = participant_last_appointment.participant_id '.
      'LEFT JOIN appointment '.
      'ON participant_last_appointment.appointment_id = appointment.id '.
      // get the user from the last appointment
      'LEFT JOIN user '.
      'ON appointment.user_id = user.id '.
      // get the participant's last callback
      'LEFT JOIN callback '.
      'ON participant.id = callback.participant_id '.
      'AND callback.datetime = ( '.
        'SELECT MAX( datetime ) '.
        'FROM callback AS last_callback '.
        'WHERE callback.participant_id = last_callback.participant_id '.
      ') '.
      // get the participant's home interview
      'LEFT JOIN interview AS home_interview '.
      'ON participant.id = home_interview.participant_id '.
      'AND home_interview.qnaire_id = ( SELECT id FROM qnaire WHERE rank = 1 ) '.
      // get the event associated with the home interview complete
      'LEFT JOIN event AS home_event '.
      'ON participant.id = home_event.participant_id '.
      'AND home_event.event_type_id = ( SELECT id FROM event_type WHERE name = "completed (Baseline Home)" ) '.
      // get the total number of assignments in the home interview
      'LEFT JOIN ( '.
        'SELECT interview_id, COUNT(*) AS total '.
        'FROM assignment '.
        'JOIN interview ON assignment.interview_id = interview.id '.
        'AND interview.qnaire_id = 1 '.
        'GROUP BY interview_id '.
      ') AS home_assignment_count '.
      'ON home_interview.id = home_assignment_count.interview_id '.
      // get the participant's site interview
      'LEFT JOIN interview AS site_interview '.
      'ON participant.id = site_interview.participant_id '.
      'AND site_interview.qnaire_id = ( SELECT id FROM qnaire WHERE rank = 2 ) '.
      // get the total number of assignments in the site interview
      'LEFT JOIN ( '.
        'SELECT interview_id, COUNT(*) AS total '.
        'FROM assignment '.
        'JOIN interview ON assignment.interview_id = interview.id '.
        'AND interview.qnaire_id = 2 '.
        'GROUP BY interview_id '.
      ') AS site_assignment_count '.
      'ON site_interview.id = site_assignment_count.interview_id '.
      // get the participant's last interview
      'LEFT JOIN participant_last_interview '.
      'ON participant.id = participant_last_interview.participant_id '.
      'LEFT JOIN interview AS last_interview '.
      'ON participant_last_interview.interview_id = last_interview.id '.
      // get the last interview's qnaire
      'LEFT JOIN qnaire AS last_qnaire '.
      'ON last_interview.qnaire_id = last_qnaire.id '.

      // restrict to participants who have been released to this service
      'WHERE service_has_participant.datetime IS NOT NULL '.
      // restrict to the selected quota
      'AND gender = %s '.
      'AND age_group_id = %s '.
      // remove any participants who have negative consent
      'AND IFNULL( participant_last_consent.accept, true ) = true '.
      // restrict to partcipants who haven't completed their site (2nd) interview
      'AND ( '.
        'IFNULL( last_qnaire.rank, 1 ) = 1 OR '.
        '( last_qnaire.rank = "2" AND last_interview.completed = "0" ) '.
      ') ',
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $timezone ),
      $database_class_name::format_string( $service_id ),
      $database_class_name::format_string( $service_id ),
      $database_class_name::format_string( $db_quota->gender ),
      $database_class_name::format_string( $db_quota->age_group_id )
    );

    // restrict to a particular site, if needed
    if( !is_null( $db_site ) )
      $sql .= sprintf(
        'AND participant_site.site_id = %s ',
        $database_class_name::format_string( $db_site->id ) );

    $sql .= 'ORDER BY uid';

    // now run the query and build the table contents from its result
    $header = NULL;
    $content = array();
    foreach( $session->get_database()->get_all( $sql ) as $row )
    {
      if( is_null( $header ) ) $header = array_keys( $row );
      $content[] = array_values( $row );
    }
    
    $this->add_table( NULL, $header, $content, NULL );
  }
}
