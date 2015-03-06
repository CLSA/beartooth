<?php
/**
 * appointment_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Home appointment report.
 * 
 * @abstract
 */
class appointment_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'appointment', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $session = lib::create( 'business\session' );
    $db = $session->get_database();
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );

    $user_id = $this->get_argument( 'user_id' );
    $db_user = $user_id ? lib::create( 'database\user', $user_id ) : NULL;
    $db_site = $session->get_site();
    $db_service = $session->get_service();
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $db_prev_qnaire = $db_qnaire->get_prev_qnaire();
    $db_qnaire_queue = $queue_class_name::get_unique_record( 'name', 'qnaire' );
    $db_finished_queue = $queue_class_name::get_unique_record( 'name', 'finished' );
    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $completed = $this->get_argument( 'completed' );

    $list_for = 'site' == $db_qnaire->type
              ? $db_site->name
              : ( is_null( $db_user )
                ? $db_site->name
                : sprintf( '%s %s', $db_user->first_name, $db_user->last_name ) );
    $this->set_heading( sprintf(
      '%s appointment list for %s',
      $db_qnaire->name,
      $list_for ) );

    $modifier = lib::create( 'database\modifier' );
    $modifier->group( 'appointment.id' );
    $modifier->order( 'appointment.datetime' );
    $modifier->where( 'participant_site.site_id', '=', $db_site->id );

    if( 'home' == $db_qnaire->type && !is_null( $db_user ) )
      $modifier->where( 'appointment.user_id', '=', $db_user->id );

    $start_datetime_obj = $restrict_start_date
                        ? util::get_datetime_object( $restrict_start_date )
                        : NULL;
    $end_datetime_obj = $restrict_end_date
                        ? util::get_datetime_object( $restrict_end_date )
                        : NULL;

    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    if( $restrict_start_date )
      $modifier->where(
        sprintf( 'CONVERT_TZ( appointment.datetime, "UTC", %s )',
                 $db->format_string( $db_site->timezone ) ),
        '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
    if( $restrict_end_date )
      $modifier->where(
        sprintf( 'CONVERT_TZ( appointment.datetime, "UTC", %s )',
                 $db->format_string( $db_site->timezone ) ),
        '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );

    if( is_null( $completed ) )
    {
      $modifier->where_bracket( true );
      // the participant has completed all interviews
      $modifier->where( 'queue_has_participant.queue_id', '=', $db_finished_queue->id );
      $modifier->where_bracket( true, true );
      // or, the participant is in the qnaire queue
      $modifier->where( 'queue_has_participant.queue_id', '=', $db_qnaire_queue->id );
      $modifier->where_bracket( false );
      $modifier->where_bracket( false );
    }
    else
    {
      $modifier->where( 'appointment.completed', '=', $completed );
      if( $completed )
      {
        $modifier->where_bracket( true );
        // the participant has completed all interviews
        $modifier->where( 'queue_has_participant.queue_id', '=', $db_finished_queue->id );
        $modifier->where_bracket( true, true );
        // or, the participant is no longer on the requested qnaire
        $modifier->where( 'queue_has_participant.qnaire_id', '!=', $db_qnaire->id );
        $modifier->where( 'queue_has_participant.queue_id', '=', $db_qnaire_queue->id );
        $modifier->where_bracket( false );
        $modifier->where_bracket( false );
      }
      else // appointment not completed
      {
        $modifier->where( 'queue_has_participant.qnaire_id', '=', $db_qnaire->id );
        $modifier->where( 'queue_has_participant.queue_id', '=', $db_qnaire_queue->id );
      }
    }

    $timezone = $session->get_site()->timezone;
    $sql = sprintf(
      'SELECT CONCAT( participant.first_name, " ", participant.last_name ) AS Name, '.
      'participant.uid AS UID, '.
      'DATE_FORMAT( CONVERT_TZ( appointment.datetime, "UTC", %s ), "%%W, %%M %%D" ) AS Date, '.
      'DATE_FORMAT( CONVERT_TZ( appointment.datetime, "UTC", %s ), "%%l:%%i %%p" ) AS Time, '.
      'YEAR( FROM_DAYS( DATEDIFF( NOW(), date_of_birth ) ) ) AS Age, ',
      $db->format_string( $db_site->timezone ),
      $db->format_string( $db_site->timezone ) );

    // add extra columns needed by home/site reports
    if( 'home' == $db_qnaire->type )
    {
      $sql .= 
        'CONCAT_WS( '.
          '", ", '.
          'IF( address2 IS NULL, address1, CONCAT( address1, " ", address2 ) ), '.
          'city, '.
          'abbreviation, '.
          'postcode '.
        ') AS Address, ';
      if( is_null( $db_user ) )
      {
        $sql .=
          'CONCAT_WS( '.
            '" ", '.
            'user.first_name, '.
            'user.last_name '.
          ') AS interviewer, ';
      }
    }
    else // site appointments
    {
      // include home-interviewer details (if the previous qnaire has type home)
      if( !is_null( $db_prev_qnaire ) && 'home' == $db_prev_qnaire->type )
        $sql .=
          'CONCAT_WS( '.
            '" ", '.
            'user.first_name, '.
            'user.last_name '.
          ') AS home_interviewer, ';
    }

    $sql .=
      sprintf(
        'IFNULL( '.
          'GROUP_CONCAT( '.
            'CONCAT( phone.type, ": ", phone.number ) '.
            'ORDER BY phone.rank SEPARATOR "; " ), '.
          '"no phone numbers" '.
        ') AS Phone '.
        'FROM appointment '.
        'JOIN queue_has_participant '.
        'ON appointment.participant_id = queue_has_participant.participant_id '.
        'JOIN participant '.
        'ON appointment.participant_id = participant.id '.
        'JOIN participant_site '.
        'ON participant.id = participant_site.participant_id '.
        'AND participant_site.service_id = %s '.
        'LEFT JOIN phone '.
        'ON participant.person_id = phone.person_id '.
        'AND phone.active = true ',
        $db_service->id
      );

    // add extra tables needed by home/site reports
    if( 'home' == $db_qnaire->type )
    {
      $sql .= 'JOIN address ON appointment.address_id = address.id '.
              'JOIN region ON address.region_id = region.id ';

      if( is_null( $db_user ) )
        $sql .= 'JOIN user ON appointment.user_id = user.id ';
    }
    else // site appointments
    {
      // include home-interviewer details (if the previous qnaire has type home)
      if( !is_null( $db_prev_qnaire ) && 'home' == $db_prev_qnaire->type )
      {
        $sql .=
          'JOIN participant_last_home_appointment '.
          'ON participant.id = participant_last_home_appointment.participant_id '.
          'JOIN appointment AS home_appointment '.
          'ON participant_last_home_appointment.appointment_id = home_appointment.id '.
          'JOIN user ON home_appointment.user_id = user.id ';
      }
    }

    $sql .= $modifier->get_sql();

    $header = array();
    $contents = array();
    foreach( $appointment_class_name::db()->get_all( $sql ) as $row )
    {
      if( 0 == count( $header ) )
        foreach( $row as $column => $value )
          $header[] = ucwords( str_replace( '_', ' ', $column ) );

      $contents[] = array_values( $row );
    }

    $this->add_table( NULL, $header, $contents );
  }
}
