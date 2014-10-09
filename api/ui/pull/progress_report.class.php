<?php
/**
 * progress.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Consent form report data.
 * 
 * @abstract
 */
class progress_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'progress', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $interview_class_name = lib::get_class_name( 'database\interview' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $datetime_obj = util::get_datetime_object();

    $days_since_monday = $datetime_obj->format( 'N' ) - 1;
    $this_monday_datetime_obj = clone $datetime_obj;
    $this_monday_datetime_obj->sub( new \DateInterval( sprintf( 'P%dD', $days_since_monday ) ) );
    $last_monday_datetime_obj = clone $this_monday_datetime_obj;
    $last_monday_datetime_obj->sub( new \DateInterval( 'P7D' ) );
    $next_monday_datetime_obj = clone $this_monday_datetime_obj;
    $next_monday_datetime_obj->add( new \DateInterval( 'P7D' ) );

    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id ) $site_mod->where( 'id', '=', $restrict_site_id );
    
    $site_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id ) $site_mod->where( 'id', '=', $restrict_site_id );
    foreach( $site_class_name::select( $site_mod ) as $db_site )
    {
      // all participants
      $db_queue = $queue_class_name::get_unique_record( 'name', 'all' );
      $db_queue->set_site( $db_site );
      $site_totals[ 'All' ] = $db_queue->get_participant_count();

      // ineligible
      $db_queue = $queue_class_name::get_unique_record( 'name', 'refused consent' );
      $db_queue->set_site( $db_site );
      $site_totals[ 'Refused Consent' ] = $db_queue->get_participant_count();

      $space = '';
      $qnaire_mod = lib::create( 'database\modifier' );
      $qnaire_mod->order( 'rank' );
      foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
      {
        // empty space (keep incrementing key by a single space)
        $site_totals[ $space .= ' ' ] = '';

        $event_name = sprintf( 'completed (%s)', $db_qnaire->name );
        $db_event_type =
          $event_type_class_name::get_unique_record( 'name', $event_name );

        // this week's callbacks
        $category = sprintf( 'Callbacks scheduled this week (%s)', $db_qnaire->name );
        $db_queue = $queue_class_name::get_unique_record( 'name', 'callback' );
        $db_queue->set_site( $db_site );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $queue_mod->where(
          'callback.participant_id', '=', 'queue_has_participant.participant_id', false );
        $queue_mod->where(
          'callback.datetime', '>=', $this_monday_datetime_obj->format( 'Y-m-d 0:00:00' ) );
        $queue_mod->where(
          'callback.datetime', '<', $next_monday_datetime_obj->format( 'Y-m-d 0:00:00' ) );
        $site_totals[ $category ] = $db_queue->get_participant_count( $queue_mod );

        // total callbacks
        $category = sprintf( 'Total Callbacks scheduled (%s)', $db_qnaire->name );
        $db_queue = $queue_class_name::get_unique_record( 'name', 'callback' );
        $db_queue->set_site( $db_site );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $site_totals[ $category ] = $db_queue->get_participant_count( $queue_mod );

        // this week's appointments
        $category = sprintf( 'Appointments scheduled this week (%s)', $db_qnaire->name );
        $db_queue = $queue_class_name::get_unique_record( 'name', 'appointment' );
        $db_queue->set_site( $db_site );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $queue_mod->where(
          'appointment.participant_id', '=', 'queue_has_participant.participant_id', false );
        $queue_mod->where(
          'datetime', '>=', $this_monday_datetime_obj->format( 'Y-m-d 0:00:00' ) );
        $queue_mod->where(
          'datetime', '<', $next_monday_datetime_obj->format( 'Y-m-d 0:00:00' ) );
        $site_totals[ $category ] = $db_queue->get_participant_count( $queue_mod );

        // total appointments
        $category = sprintf( 'Total Appointments scheduled (%s)', $db_qnaire->name );
        $db_queue = $queue_class_name::get_unique_record( 'name', 'appointment' );
        $db_queue->set_site( $db_site );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $site_totals[ $category ] = $db_queue->get_participant_count( $queue_mod );

        // never assigned
        $category = sprintf( 'Never assigned (%s)', $db_qnaire->name );
        $db_queue = $queue_class_name::get_unique_record( 'name', 'new participant' );
        $db_queue->set_site( $db_site );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $site_totals[ $category ] = $db_queue->get_participant_count( $queue_mod );

        // previously assigned
        $category = sprintf( 'Previously assigned (%s)', $db_qnaire->name );
        $db_queue = $queue_class_name::get_unique_record( 'name', 'old participant' );
        $db_queue->set_site( $db_site );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $site_totals[ $category ] = $db_queue->get_participant_count( $queue_mod );

        if( !is_null( $db_event_type ) )
        {
          // completed last week
          $category = sprintf( 'Completed Last Week (%s)', $db_qnaire->name );
          $participant_mod = lib::create( 'database\modifier' );
          $participant_mod->where( 'participant_site.site_id', '=', $db_site->id );
          $participant_mod->where( 'event.event_type_id', '=', $db_event_type->id );
          $participant_mod->where(
            'event.datetime', '>=', $last_monday_datetime_obj->format( 'Y-m-d' ) );
          $participant_mod->where(
            'event.datetime', '<', $this_monday_datetime_obj->format( 'Y-m-d' ) );
          $site_totals[ $category ] = $participant_class_name::count( $participant_mod );
        }

        // total completed
        $category = sprintf( 'Total Completed (%s)', $db_qnaire->name );
        $interview_mod = lib::create( 'database\modifier' );
        $interview_mod->where( 'participant_site.site_id', '=', $db_site->id );
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $interview_mod->where( 'completed', '=', true );
        $site_totals[ $category ] = $interview_class_name::count( $interview_mod );
      }

      $site_totals_list[ $db_site->name ] = $site_totals;
    }

    $header = array( '' );

    if( 1 < count( $site_totals_list ) )
    { // calculate a grand total column if we have more than one totals column
      $site_totals_list['Grand Total'] = array();
      foreach( $site_totals_list as $site => $site_totals )
      {
        foreach( $site_totals as $category => $total )
        {
          if( !array_key_exists( $category, $site_totals_list['Grand Total'] ) )
            $site_totals_list['Grand Total'][$category] = 0;
          $site_totals_list['Grand Total'][$category] += $total;
        }
      }
    }
    else
    { // change the column titme from site to participants since it's the only column
      $key = key( $site_totals_list );
      $site_totals_list['Participants'] = $site_totals_list[$key];
      unset( $site_totals_list[$key] );
    }

    // build the final 2D content array
    $temp_content = array( array_keys( current( $site_totals_list ) ) );
    foreach( $site_totals_list as $site => $totals )
    {
      $header[] = $site;
      $temp_content[] = array_values( $totals );
    }

    // transpose from column-wise to row-wise
    $content = array();
    foreach( $temp_content as $key => $subarr )
      foreach( $subarr as $subkey => $subvalue )
        $content[ $subkey ][ $key ] = $subvalue;

    $this->add_table( NULL, $header, $content, NULL );
  }
}
