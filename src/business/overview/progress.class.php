<?php
/**
 * overview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\business\overview;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * overview: progress
 */
class progress extends \cenozo\business\overview\base_overview
{
  /**
   * Implements abstract method
   */
  protected function build()
  {
    $state_class_name = lib::get_class_name( 'database\state' );
    $session = lib::create( 'business\session' );
    $db = $session->get_database();
    $db_application = $session->get_application();
    $db_role = $session->get_role();
    $db_site = $session->get_site();
    $db_site = $session->get_site();
    $db_user = $session->get_user();

    $data = array();

    // create temporary table of this application's participants and their effective site
    /////////////////////////////////////////////////////////////////////////////////////////////
    $select = lib::create( 'database\select' );
    $modifier = lib::create( 'database\modifier' );

    $select->from( 'participant' );
    $select->add_column( 'id' );
    $select->add_column( 'state_id' );
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
      'ADD INDEX dk_site ( site ), '.
      'ADD INDEX dk_state_id ( state_id ), '.
      'ADD INDEX dk_callback ( callback )' ) );

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

    $list = array();
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['site'], $list ) )
        $list[$row['site']] = array( 'all' => 0, 'withdrawn' => 0 );

      if( false == $row['consent'] ) $list[$row['site']]['withdrawn'] += $row['count'];
      $list[$row['site']]['all'] += $row['count'];
    }

    $site_node_lookup = array();
    foreach( $list as $site => $data )
    {
      $node = $this->add_root_item( $site );
      $this->add_item( $node, 'All Participants', $data['all'] );
      $this->add_item( $node, 'Withdrawn', $data['withdrawn'] );
      $this->add_item( $node, 'Conditions' );
      $home_node = $this->add_item( $node, 'Home Interview' );
      $this->add_item( $home_node, 'Scheduled callbacks', 0 );
      $this->add_item( $home_node, 'Callbacks this week', 0 );
      $this->add_item( $home_node, 'Upcoming appointments', 0 );
      $this->add_item( $home_node, 'Appointments this week', 0 );
      $this->add_item( $home_node, 'Participants never assigned', 0 );
      $this->add_item( $home_node, 'Participants previously assigned', 0 );
      $this->add_item( $home_node, 'Completed Interviews', 0 );
      $site_node = $this->add_item( $node, 'Site Interview' );
      $this->add_item( $site_node, 'Scheduled callbacks', 0 );
      $this->add_item( $site_node, 'Callbacks this week', 0 );
      $this->add_item( $site_node, 'Upcoming appointments', 0 );
      $this->add_item( $site_node, 'Appointments this week', 0 );
      $this->add_item( $site_node, 'Participants never assigned', 0 );
      $this->add_item( $site_node, 'Participants previously assigned', 0 );
      $this->add_item( $site_node, 'Completed Interviews', 0 );
      $site_node_lookup[$site] = $node;
    }

    // get state data
    /////////////////////////////////////////////////////////////////////////////////////////////
    $select = lib::create( 'database\select' );
    $modifier = lib::create( 'database\modifier' );

    $select->from( 'overview_participant' );
    $select->add_table_column( 'state', 'name', 'state' );
    $select->add_column( 'site' );
    $select->add_column( 'COUNT(*)', 'count', false );

    $modifier->group( 'site' );

    // join to and group by state
    $modifier->join( 'state', 'overview_participant.state_id', 'state.id' );
    $modifier->group( 'state' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      $node = $site_node_lookup[$row['site']]->find_node( 'Conditions' );
      $this->add_item( $node, $row['state'], $row['count'] );
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

    $list = array( 'home' => array(), 'site' => array() );
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['type'], $list ) ) $list[$row['type']] = array();
      if( !array_key_exists( $row['site'], $list[$row['type']] ) )
        $list[$row['type']][$row['site']] = array( 'all' => 0, 'week' => 0 );

      if( $row['week'] ) $list[$row['type']][$row['site']]['week'] += $row['count'];
      $list[$row['type']][$row['site']]['all'] += $row['count'];
    }

    foreach( $list as $type => $sub_list )
    {
      foreach( $sub_list as $site => $values )
      {
        $parent_node = $site_node_lookup[$site]->find_node( ucWords( $type ).' Interview' );
        foreach( $values as $cat => $value )
        {
          $node = $parent_node->find_node( 'all' == $cat ? 'Scheduled callbacks' : 'Callbacks this week' );
          $node->set_value( $value );
        }
      }
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

    $list = array( 'home' => array(), 'site' => array() );
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['site'], $list[$row['type']] ) ) $list[$row['type']][$row['site']] = 0;
      $list[$row['type']][$row['site']] += $row['count'];
    }

    foreach( $list as $type => $sub_list )
    {
      foreach( $sub_list as $site => $value )
      {
        $parent_node = $site_node_lookup[$site]->find_node( ucWords( $type ).' Interview' );
        $node = $parent_node->find_node( 'Upcoming appointments' );
        $node->set_value( $value );
      }
    }

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

    $list = array( 'home' => array(), 'site' => array() );
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['site'], $list[$row['type']] ) ) $list[$row['type']][$row['site']] = 0;
      $list[$row['type']][$row['site']] += $row['count'];
    }

    foreach( $list as $type => $sub_list )
    {
      foreach( $sub_list as $site => $value )
      {
        $parent_node = $site_node_lookup[$site]->find_node( ucWords( $type ).' Interview' );
        $node = $parent_node->find_node( 'Appointments this week' );
        $node->set_value( $value );
      }
    }

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

    $list = array( 'home' => array(), 'site' => array() );
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['site'], $list[$row['type']] ) ) $list[$row['type']][$row['site']] = 0;
      $list[$row['type']][$row['site']] += $row['count'];
    }

    foreach( $list as $type => $sub_list )
    {
      foreach( $sub_list as $site => $value )
      {
        $parent_node = $site_node_lookup[$site]->find_node( ucWords( $type ).' Interview' );
        $node = $parent_node->find_node( 'Participants never assigned' );
        $node->set_value( $value );
      }
    }

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

    $list = array( 'home' => array(), 'site' => array() );
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['site'], $list[$row['type']] ) ) $list[$row['type']][$row['site']] = 0;
      $list[$row['type']][$row['site']] += $row['count'];
    }

    foreach( $list as $type => $sub_list )
    {
      foreach( $sub_list as $site => $value )
      {
        $parent_node = $site_node_lookup[$site]->find_node( ucWords( $type ).' Interview' );
        $node = $parent_node->find_node( 'Participants previously assigned' );
        $node->set_value( $value );
      }
    }

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

    $list = array( 'home' => array(), 'site' => array() );
    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) ) as $row )
    {
      if( !array_key_exists( $row['site'], $list[$row['type']] ) ) $list[$row['type']][$row['site']] = 0;
      $list[$row['type']][$row['site']] += $row['count'];
    }

    foreach( $list as $type => $sub_list )
    {
      foreach( $sub_list as $site => $value )
      {
        $parent_node = $site_node_lookup[$site]->find_node( ucWords( $type ).' Interview' );
        $node = $parent_node->find_node( 'Completed Interviews' );
        $node->set_value( $value );
      }
    }

    if( $db_role->all_sites )
    {
      // create a summary node of all sites
      $summary_node = $this->root_node->get_summary_node();

      // sort the conditions
      $condition_node = $summary_node->find_node( 'Conditions' );
      $condition_node->sort_children(
        function( $node1, $node2 ) { return $node1->get_label() > $node2->get_label(); }
      );

      $this->root_node->add_child( $summary_node, true );
    }
  }
}
