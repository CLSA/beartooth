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
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    $data = array();

    // get a list of all states
    $state_sel = lib::create( 'database\select' );
    $state_sel->add_column( 'name' );
    $state_mod = lib::create( 'database\modifier' );
    $state_mod->order( 'name' );
    $state_list = array();
    foreach( $state_class_name::select( $state_sel, $state_mod ) as $state ) $state_list[] = $state['name'];

    // create generic select and modifier objects which can be re-used
    $select = lib::create( 'database\select' );
    $select->from( 'queue_has_participant' );
    $select->add_table_column( 'site', 'IFNULL( site.name, "(none)" )', 'site', false );
    $select->add_column( 'COUNT(*)', 'total', false );

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
    $modifier->left_join( 'site', 'queue_has_participant.site_id', 'site.id' );
    $modifier->left_join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    if( !$db_role->all_sites ) $modifier->where( 'site.id', '=', $db_site->id );
    $modifier->group( 'queue_has_participant.site_id' );

    // start with the participant totals
    /////////////////////////////////////////////////////////////////////////////////////////////
    $all_mod = clone $modifier;
    $all_mod->where( 'queue.name', '=', 'all' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $all_mod->get_sql() ) ) as $row )
    {
      $node = $this->add_root_item( $row['site'] );
      $this->add_item( $node, 'All Participants', $row['total'] );
      $this->add_item( $node, 'Inactive', 0 );
      $this->add_item( $node, 'Refused Consent', 0 );
      $state_node = $this->add_item( $node, 'Conditions' );
      foreach( $state_list as $state ) $this->add_item( $state_node, $state, 0 );
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
      $site_node_lookup[$row['site']] = $node;
    }

    // inactive participants
    /////////////////////////////////////////////////////////////////////////////////////////////
    $inactive_mod = clone $modifier;
    $inactive_mod->where( 'queue.name', '=', 'inactive' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $inactive_mod->get_sql() ) ) as $row )
    {
      $node = $site_node_lookup[$row['site']]->find_node( 'Inactive' );
      $node->set_value( $row['total'] );
    }

    // withdrawn participants
    /////////////////////////////////////////////////////////////////////////////////////////////
    $refused_mod = clone $modifier;
    $refused_mod->where( 'queue.name', '=', 'refused consent' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $refused_mod->get_sql() ) ) as $row )
    {
      $node = $site_node_lookup[$row['site']]->find_node( 'Refused Consent' );
      $node->set_value( $row['total'] );
    }

    // states
    /////////////////////////////////////////////////////////////////////////////////////////////
    $state_sel = clone $select;
    $state_sel->add_table_column( 'qnaire', 'type' );
    $state_sel->add_table_column( 'state', 'name', 'state' );

    $state_mod = clone $modifier;
    $state_mod->where( 'queue.name', '=', 'condition' );
    $state_mod->join( 'participant', 'queue_has_participant.participant_id', 'participant.id' );
    $state_mod->join( 'state', 'participant.state_id', 'state.id' );
    $state_mod->group( 'participant.state_id' );

    foreach( $db->get_all( sprintf( '%s %s', $state_sel->get_sql(), $state_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( 'Conditions' );
      $node = $parent_node->find_node( $row['state'] );
      $node->set_value( $row['total'] );
    }

    // callbacks
    /////////////////////////////////////////////////////////////////////////////////////////////
    $week_sql = sprintf(
      '('."\n".
      '  DATE( CONVERT_TZ( participant.callback, "UTC", "%s" ) ) >='."\n".
      '  DATE_SUB( CURDATE(), INTERVAL WEEKDAY( CURDATE() ) DAY ) AND'."\n".
      '  DATE( CONVERT_TZ( participant.callback, "UTC", "%s" ) ) <'."\n".
      '  DATE_ADD( CURDATE(), INTERVAL 7 - WEEKDAY( CURDATE() ) DAY )'."\n".
      ')',"\n".
      $db_user->timezone,
      $db_user->timezone
    );

    $callback_sel = clone $select;
    $callback_sel->add_table_column( 'qnaire', 'type' );
    $callback_sel->add_column( $week_sql, 'week', false );

    $callback_mod = clone $modifier;
    $callback_mod->where( 'queue.name', '=', 'callback' );
    $callback_mod->join( 'participant', 'queue_has_participant.participant_id', 'participant.id' );
    $callback_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $callback_mod->group( 'qnaire.type' );
    $callback_mod->group( $week_sql );

    foreach( $db->get_all( sprintf( '%s %s', $callback_sel->get_sql(), $callback_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['type'] ).' Interview' );
      $all_node = $parent_node->find_node( 'Scheduled callbacks' );
      $all_node->set_value( $all_node->get_value() + $row['total'] );
      $week_node = $parent_node->find_node( 'Callbacks this week' );
      if( $row['week'] ) $week_node->set_value( $week_node->get_value() + $row['total'] );
    }

    // appointments
    /////////////////////////////////////////////////////////////////////////////////////////////
    $week_sql = sprintf(
      '('."\n".
      '  DATE( CONVERT_TZ( appointment.datetime, "UTC", "%s" ) ) >='."\n".
      '  DATE_SUB( CURDATE(), INTERVAL WEEKDAY( CURDATE() ) DAY ) AND '."\n".
      '  DATE( CONVERT_TZ( appointment.datetime, "UTC", "%s" ) ) <'."\n".
      '  DATE_ADD( CURDATE(), INTERVAL 7 - WEEKDAY( CURDATE() ) DAY )'."\n".
      ')',
      $db_user->timezone,
      $db_user->timezone
    );

    $appointment_sel = clone $select;
    $appointment_sel->add_table_column( 'qnaire', 'type' );
    $appointment_sel->add_column( $week_sql, 'week', false );

    $appointment_mod = clone $modifier;
    $appointment_mod->where( 'queue.name', '=', 'appointment' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'queue_has_participant.participant_id', '=', 'interview.participant_id', false );
    $join_mod->where( 'queue_has_participant.qnaire_id', '=', 'interview.qnaire_id', false );
    $appointment_mod->join_modifier( 'interview', $join_mod );
    $appointment_mod->join(
      'interview_last_appointment', 'interview.id', 'interview_last_appointment.interview_id' );
    $appointment_mod->join( 'appointment', 'interview_last_appointment.appointment_id', 'appointment.id' );
    $appointment_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $appointment_mod->group( 'qnaire.type' );
    $appointment_mod->group( $week_sql );

    foreach( $db->get_all( sprintf( '%s %s', $appointment_sel->get_sql(), $appointment_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['type'] ).' Interview' );
      $all_node = $parent_node->find_node( 'Upcoming appointments' );
      $all_node->set_value( $all_node->get_value() + $row['total'] );
      $week_node = $parent_node->find_node( 'Appointments this week' );
      if( $row['week'] ) $week_node->set_value( $week_node->get_value() + $row['total'] );
    }

    // never assigned
    /////////////////////////////////////////////////////////////////////////////////////////////
    $old_sel = clone $select;
    $old_sel->add_table_column( 'qnaire', 'type' );

    $old_mod = clone $modifier;
    $old_mod->where( 'queue.name', '=', 'new participant' );
    $old_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $old_mod->group( 'qnaire.type' );

    foreach( $db->get_all( sprintf( '%s %s', $old_sel->get_sql(), $old_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['type'] ).' Interview' );
      $node = $parent_node->find_node( 'Participants never assigned' );
      $node->set_value( $row['total'] );
    }

    // previously assigned
    /////////////////////////////////////////////////////////////////////////////////////////////
    $new_sel = clone $select;
    $new_sel->add_table_column( 'qnaire', 'type' );

    $new_mod = clone $modifier;
    $new_mod->where( 'queue.name', '=', 'old participant' );
    $old_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $old_mod->group( 'qnaire.type' );

    foreach( $db->get_all( sprintf( '%s %s', $new_sel->get_sql(), $new_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['type'] ).' Interview' );
      $node = $parent_node->find_node( 'Participants previously assigned' );
      $node->set_value( $row['total'] );
    }

    // completed
    /////////////////////////////////////////////////////////////////////////////////////////////
    $completed_sel = clone $select;
    $completed_sel->add_table_column( 'qnaire', 'type' );

    $completed_mod = clone $modifier;
    $completed_mod->where( 'queue.name', '=', 'all' );
    $completed_mod->join( 'interview', 'queue_has_participant.participant_id', 'interview.participant_id' );
    $completed_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $completed_mod->where( 'interview.end_datetime', '!=', NULL );
    $completed_mod->group( 'qnaire.type' );

    foreach( $db->get_all( sprintf( '%s %s', $completed_sel->get_sql(), $completed_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['type'] ).' Interview' );
      $node = $parent_node->find_node( 'Completed Interviews' );
      $node->set_value( $row['total'] );
    }

    // create summary node and finish
    /////////////////////////////////////////////////////////////////////////////////////////////
    if( $db_role->all_sites )
    {
      // create a summary node of all sites
      $first_node = $this->root_node->get_summary_node();
      $this->root_node->add_child( $first_node, true );
    }
    else
    {
      $first_node = $this->root_node->find_node( $db_site->name );
    }

    // go through the first node and remove all states with a value of 0
    $state_node = $first_node->find_node( 'Conditions' );
    $removed_label_list = $state_node->remove_empty_children();

    // and remove them from other nodes as well
    $this->root_node->each( function( $node ) use( $removed_label_list ) {
      $state_node = $node->find_node( 'Conditions' );
      $state_node->remove_child_by_label( $removed_label_list );
    } );
  }
}
