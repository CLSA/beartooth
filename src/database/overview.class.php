<?php
/**
 * overview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * overview: record
 */
class overview extends \cenozo\database\overview
{
  /**
   * Returns the overview's data
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return mixed
   */
  public function get_data()
  {
    $session = lib::create( 'business\session' );
    $db = $session->get_database();
    $db_application = $session->get_application();
    $db_role = $session->get_role();
    $db_site = $session->get_site();
    $db_site = $session->get_site();
    $db_user = $session->get_user();
    
    // TODO: implement overview data fetching here
    if( 'Progress' == $this->title )
    {
      $data = array();

      // create temporary table of this application's participants and their effective site
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'participant' );
      $select->add_column( 'id' );
      $select->add_column( 'callback' );
      $select->add_table_column( 'site', 'IFNULL( site.name, "None" )', 'site', false );

      // restrict to this application
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'application_has_participant.participant_id', '=', 'participant.id', false );
      $join_mod->where( 'application_has_participant.application_id', '=', $db_application->id );
      $modifier->join_modifier( 'application_has_participant', $join_mod );
      $modifier->where( 'application_has_participant.datetime', '!=', NULL );

      // group by site
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant_site.participant_id', '=', 'participant.id', false );
      $join_mod->where( 'participant_site.application_id', '=', $db_application->id );
      $modifier->join_modifier( 'participant_site', $join_mod );
      $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );
      if( !$db_role->all_sites ) $modifier->where( 'site.id', '=', $db_site->id );

      $db->execute( sprintf(
        'CREATE TEMPORARY TABLE overview_participant %s %s',
        $select->get_sql(),
        $modifier->get_sql() ) );
      $db->execute( sprintf(
        'ALTER TABLE overview_participant '.
        'ADD INDEX dk_id ( id ), '.
        'ADD INDEX dk_site ( site )' ) );

      // get total and no consent counts
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'overview_participant' );
      $select->add_table_column( 'consent', 'IFNULL( consent.accept, true )', 'consent', false );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // group by negative participant consent
      $modifier->join(
        'participant_last_consent', 'overview_participant.id', 'participant_last_consent.participant_id' );
      $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
      $modifier->where( 'consent_type.name', '=', 'participation' );
      $modifier->left_join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
      $modifier->group( 'IFNULL( consent.accept, true )' );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
      {
        if( !array_key_exists( $row['site'], $data ) ) $data[$row['site']] = array(
          'All Participants' => 0,
          'Withdrawn' => 0,
          'home' => array(
            'Scheduled callbacks' => 0,
            'Callbacks this week' => 0,
            'Upcoming appointments' => 0,
            'Appointments this week' => 0,
            'Participants never assigned' => 0,
            'Participants previously assigned' => 0,
            'Completed Interviews' => 0
          ),
          'site' => array(
            'Scheduled callbacks' => 0,
            'Callbacks this week' => 0,
            'Upcoming appointments' => 0,
            'Appointments this week' => 0,
            'Participants never assigned' => 0,
            'Participants previously assigned' => 0,
            'Completed Interviews' => 0
          )
        );
        if( false == $row['consent'] ) $data[$row['site']]['Withdrawn'] += $row['count'];
        $data[$row['site']]['All Participants'] += $row['count'];
      }

      // get callback data
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $week_sql = sprintf(
        '( DATE( CONVERT_TZ( overview_participant.callback, "UTC", "%s" ) ) >= '.
          'DATE_SUB( CURDATE(), INTERVAL WEEKDAY( CURDATE() ) DAY ) AND '.
          'DATE( CONVERT_TZ( overview_participant.callback, "UTC", "%s" ) ) < '.
          'DATE_ADD( CURDATE(), INTERVAL 7 - WEEKDAY( CURDATE() ) DAY ) )',
        $db_user->timezone,
        $db_user->timezone ); 

      $select->from( 'overview_participant' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_column( $week_sql, 'week', false );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // join to queue_has_participant and group by qnaire
      $modifier->join(
        'queue_has_participant', 'overview_participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->group( 'qnaire.type' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->where( 'queue.name', '=', 'qnaire' );

      // group by whether the callback is this week or not
      $modifier->where( 'overview_participant.callback', '!=', NULL );
      $modifier->group( $week_sql );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
      {
        if( !array_key_exists( 'Scheduled callbacks', $data[$row['site']] ) )
        {
          $data[$row['site']][$row['type']]['Scheduled callbacks'] = 0;
          $data[$row['site']][$row['type']]['Callbacks this week'] = 0;
        }
        if( $row['week'] ) $data[$row['site']][$row['type']]['Callbacks this week'] += $row['count'];
        $data[$row['site']][$row['type']]['Scheduled callbacks'] += $row['count'];
      }

      // get upcomming appointment data
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'overview_participant' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // join to interview, appointment and qnaire, and group by qnaire
      $modifier->join( 'interview', 'overview_participant.id', 'interview.participant_id' );
      $modifier->join( 'appointment', 'interview.id', 'appointment.interview_id' );
      $modifier->where( 'appointment.outcome', '=', NULL );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $modifier->group( 'qnaire.type' );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
        $data[$row['site']][$row['type']]['Upcoming appointments'] += $row['count'];

      // get this week's appointment data
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'overview_participant' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // join to appointment and group by week
      $modifier->join( 'interview', 'overview_participant.id', 'interview.participant_id' );
      $modifier->join( 'appointment', 'interview.id', 'appointment.interview_id' );
      $modifier->where(
        sprintf(
          'DATE( CONVERT_TZ( appointment.datetime, "UTC", "%s" ) )',
          $db_user->timezone
        ),
        '>=',
        'DATE_SUB( CURDATE(), INTERVAL WEEKDAY( CURDATE() ) DAY )',
        false );
      $modifier->where(
        sprintf(
          'DATE( CONVERT_TZ( appointment.datetime, "UTC", "%s" ) )',
          $db_user->timezone
        ),
        '<',
        'DATE_ADD( CURDATE(), INTERVAL 7 - WEEKDAY( CURDATE() ) DAY )',
        false );

      // join to qnaire and group by type
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $modifier->group( 'qnaire.type' );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
        $data[$row['site']][$row['type']]['Appointments this week'] += $row['count'];

      // get participants never assigned (never called)
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'overview_participant' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // join to queue_has_participant and group by qnaire
      $modifier->join(
        'queue_has_participant', 'overview_participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->group( 'qnaire.type' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->where( 'queue.name', '=', 'new participant' );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
        $data[$row['site']][$row['type']]['Participants never assigned'] += $row['count'];

      // get participants previously assigned (never called)
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'overview_participant' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // join to queue_has_participant and group by qnaire
      $modifier->join(
        'queue_has_participant', 'overview_participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->group( 'qnaire.type' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->where( 'queue.name', '=', 'old participant' );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
        $data[$row['site']][$row['type']]['Participants previously assigned'] += $row['count'];

      // get completed participants
      /////////////////////////////////////////////////////////////////////////////////////////////
      $select = lib::create( 'database\select' );
      $modifier = lib::create( 'database\modifier' );

      $select->from( 'overview_participant' );
      $select->add_table_column( 'qnaire', 'type' );
      $select->add_column( 'site' );
      $select->add_column( 'COUNT(*)', 'count', false );

      $modifier->group( 'site' );

      // join to interview and qnaire, and group by qnaire
      $modifier->join( 'interview', 'overview_participant.id', 'interview.participant_id' );
      $modifier->where( 'interview.end_datetime', '!=', NULL );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $modifier->group( 'qnaire.type' );

      foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
        $data[$row['site']][$row['type']]['Completed Interviews'] += $row['count'];

      // now transform data into the correct format
      $formatted_data = array();
      foreach( $data as $site => $site_data )
      {
        $array = array( 'label' => $site, 'list' => array() );
        
        foreach( $site_data as $label => $value )
        {
          if( 'home' == $label || 'site' == $label )
          {
            $sub_array = array( 'label' => ucwords( $label ).' Interview', 'list' => array() );
            foreach( $value as $label => $sub_value )
              $sub_array['list'][] = array( 'label' => $label, 'value' => (string) $sub_value );
            $array['list'][] = $sub_array;
          }
          else
          {
            $array['list'][] = array( 'label' => $label, 'value' => (string) $value );
          }
        }

        $formatted_data[] = $array;
      }

      return $formatted_data;
    }
    
    // if we get here then the overview must be defined in the framework class
    return static::get_data();
  }
}
