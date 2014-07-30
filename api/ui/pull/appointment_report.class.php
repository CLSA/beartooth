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
    $database_class_name = lib::get_class_name( 'database\database' );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );

    $user_id = $this->get_argument( 'user_id' );
    $db_user = $user_id ? lib::create( 'database\user', $user_id ) : NULL;
    $db_site = lib::create( 'business\session' )->get_site();
    $db_service = lib::create( 'business\session' )->get_service();
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $db_queue = $queue_class_name::get_unique_record( 'name', 'qnaire' );
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
    $modifier->where( 'queue_has_participant.qnaire_id', '=', $db_qnaire->id );
    $modifier->where( 'queue_has_participant.queue_id', '=', $db_queue->id );
    $modifier->where( 'participant_site.site_id', '=', $db_site->id );

    if( 'home' == $db_qnaire->type && !is_null( $db_user ) )
      $modifier->where( 'appointment.user_id', '=', $db_user->id );

    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    if( $restrict_start_date )
    {
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
      if( $start_datetime_obj > $now_datetime_obj )
        $start_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_end_date )
    {
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
      if( $end_datetime_obj > $now_datetime_obj )
        $end_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    if( $restrict_start_date )
      $modifier->where( 'appointment.datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
    if( $restrict_end_date )
      $modifier->where( 'appointment.datetime', '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );

    if( !is_null( $completed ) ) $modifier->where( 'completed', '=', $completed );

    $timezone = lib::create( 'business\session' )->get_site()->timezone;
    $sql = sprintf(
      'SELECT CONCAT( participant.first_name, " ", participant.last_name ) AS Name, '.
      'participant.uid AS UID, '.
      'DATE_FORMAT( CONVERT_TZ( appointment.datetime, %s, "UTC" ), "%%W, %%M %%D" ) AS Date, '.
      'DATE_FORMAT( CONVERT_TZ( appointment.datetime, %s, "UTC" ), "%%l:%%i %%p" ) AS Time, '.
      'YEAR( FROM_DAYS( DATEDIFF( NOW(), date_of_birth ) ) ) AS Age, ',
      $database_class_name::format_string( $db_site->timezone ),
      $database_class_name::format_string( $db_site->timezone ) );

    if( 'home' == $db_qnaire->type )
      $sql .= 
        'CONCAT_WS( '.
          '", ", '.
          'IF( address2 IS NULL, address1, CONCAT( address1, " ", address2 ) ), '.
          'city, '.
          'abbreviation, '.
          'postcode '.
        ') AS Address, ';

    $sql .=
      sprintf(
        'IFNULL( '.
          'GROUP_CONCAT( CONCAT( phone.type, ": ", phone.number ) ORDER BY phone.rank SEPARATOR "; " ), '.
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

    if( 'home' == $db_qnaire->type )
      $sql .= 'JOIN address ON appointment.address_id = address.id '.
              'JOIN region ON address.region_id = region.id ';

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
