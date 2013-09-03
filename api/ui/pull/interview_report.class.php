<?php
/**
 * interview.class.php
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
class interview_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'interview', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $site_class_name = lib::create( 'database\site' );
    $event_class_name = lib::create( 'database\event' );

    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = lib::create( 'database\modifier' );
    $site_mod->order( 'name' );
    if( $restrict_site_id ) $site_mod->where( 'id', '=', $restrict_site_id );
      
    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    // validate the dates
    if( $restrict_start_date )
    {
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
      if( $start_datetime_obj > $now_datetime_obj ) $start_datetime_obj = clone $now_datetime_obj;
    }
    
    if( $restrict_end_date )
    {
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
      if( $end_datetime_obj > $now_datetime_obj ) $end_datetime_obj = clone $now_datetime_obj;
    }
    else
    {
      $end_datetime_obj = $now_datetime_obj;
    }

    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    // if there is no start date then start with the earliest completed interview
    if( is_null( $start_datetime_obj ) )
    {
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->order( 'datetime' );
      $event_mod->limit( 1 );
      $event_mod->where( 'event_type.name', 'IN',
        array( 'completed (Baseline Home)', 'completed (Baseline Site)' ) );
      $event_list = $event_class_name->select( $event_mod );
      $db_event = current( $event_list );
      $start_datetime_obj = util::get_datetime_object( $db_event->datetime );
    }

    // we only care about what months have been selected, set days of month appropriately
    // such that the for loop below will include the start and end date's months
    $start_datetime_obj->setDate(
      $start_datetime_obj->format( 'Y' ),
      $start_datetime_obj->format( 'n' ),
      1 );
    $end_datetime_obj->setDate(
      $end_datetime_obj->format( 'Y' ),
      $end_datetime_obj->format( 'n' ),
      2 );
    
    $home_contents = array();
    $site_contents = array();
    $interval = new \DateInterval( 'P1M' );
    for( $from_datetime_obj = clone $start_datetime_obj;
         $from_datetime_obj < $end_datetime_obj;
         $from_datetime_obj->add( $interval ) )
    {
      $to_datetime_obj = clone $from_datetime_obj;
      $to_datetime_obj->add( $interval );

      $home_content =
        array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );
      $site_content =
        array( $from_datetime_obj->format( 'Y' ), $from_datetime_obj->format( 'F' ) );

      foreach( $site_class_name::select( $site_mod ) as $db_site )
      {
        $home_event_mod = lib::create( 'database\modifier' );
        $home_event_mod->where( 'event.participant_id', '=', 'participant_site.participant_id', false );
        $home_event_mod->where( 'participant_site.site_id', '=', $db_site->id );
        $home_event_mod->where( 'datetime', '>=', $from_datetime_obj->format( 'Y-m-d' ) );
        $home_event_mod->where( 'datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
        $home_event_mod->where( 'event_type.name', '=', 'completed (Baseline Home)' );
        $home_content[] = $event_class_name::count( $home_event_mod );

        $site_event_mod = lib::create( 'database\modifier' );
        $site_event_mod->where( 'event.participant_id', '=', 'participant_site.participant_id', false );
        $site_event_mod->where( 'participant_site.site_id', '=', $db_site->id );
        $site_event_mod->where( 'datetime', '>=', $from_datetime_obj->format( 'Y-m-d' ) );
        $site_event_mod->where( 'datetime', '<', $to_datetime_obj->format( 'Y-m-d' ) );
        $site_event_mod->where( 'event_type.name', '=', 'completed (Baseline Site)' );
        $site_content[] = $event_class_name::count( $site_event_mod );
      }

      $home_contents[] = $home_content;
      $site_contents[] = $site_content;
    }

    $header = array( 'Year', 'Month' );
    $footer = array( '', '' );
    foreach( $site_class_name::select( $site_mod ) as $db_site )
    {
      $header[] = $db_site->name;
      $footer[] = 'sum()';
    }

    $this->add_table( 'Completed Home Interviews', $header, $home_contents, $footer );
    $this->add_table( 'Completed Site Interviews', $header, $site_contents, $footer );
  }
}
