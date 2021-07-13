<?php
/**
 * overview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\business\overview;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * overview: withdraw
 */
class appointment_type extends \cenozo\business\overview\base_overview
{
  /**
   * Implements abstract method
   */
  protected function build()
  {
    $session = lib::create( 'business\session' );
    $db_application = $session->get_application();
    $db_role = $session->get_role();
    $db_site = $session->get_site();
    $atype_class_name = lib::get_class_name( 'database\appointment_type' );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );

    $atype_mod = lib::create( 'database\modifier' );
    $atype_mod->order( 'name' );
    $atype_sel = lib::create( 'database\select' );
    $atype_sel->add_column( 'name' );
    $atype_list = array();
    foreach( $atype_class_name::select( $atype_sel, $atype_mod ) as $atype ) $atype_list[] = $atype['name'];

    // create an entry for each site
    $site_mod = lib::create( 'database\modifier' );
    $site_mod->order( 'name' );
    if( !$db_role->all_sites ) $site_mod->where( 'site.id', '=', $db_site->id );
    $site_sel = lib::create( 'database\select' );
    $site_sel->add_table_column( 'site', 'name' );
    $site_node_list = array();
    foreach( $db_application->get_site_list( $site_sel, $site_mod ) as $site )
    {
      $node = $this->add_root_item( $site['name'] );
      foreach( $atype_list as $atype ) $this->add_item( $node, $atype, 0 );
      $site_node_list[$site['name']] = $node;
    }

    // add the not-site entry
    $node = $this->add_root_item( 'No Site' );
    foreach( $atype_list as $atype ) $this->add_item( $node, $atype, 0 );
    $site_node_list['No Site'] = $node;

    // now fill in the count values
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->join( 'appointment_type', 'appointment.appointment_type_id', 'appointment_type.id' );
    $appointment_mod->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $appointment_mod->join( 'participant', 'interview.participant_id', 'participant.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
    $join_mod->where( 'participant_site.application_id', '=', $db_application->id );
    $appointment_mod->join_modifier( 'participant_site', $join_mod );
    $appointment_mod->left_join( 'site', 'participant_site.site_id', 'site.id' );
    $appointment_mod->where( 'appointment.outcome', '=', 'completed' );
    if( !$db_role->all_sites ) $appointment_mod->where( 'site.id', '=', $db_site->id );
    $appointment_mod->group( 'participant_site.site_id' );
    $appointment_mod->group( 'appointment_type.id' );
    $appointment_mod->order( 'site.name' );

    $appointment_sel = lib::create( 'database\select' );
    $appointment_sel->from( 'appointment' );
    $appointment_sel->add_table_column( 'site', 'name', 'site' );
    $appointment_sel->add_table_column( 'appointment_type', 'name', 'atype' );
    $appointment_sel->add_column( 'COUNT(*)', 'total', false );

    foreach( $appointment_class_name::select( $appointment_sel, $appointment_mod ) as $row )
    {
      $site = is_null( $row['site'] ) ? 'No Site' : $row['site'];
      $node = $site_node_list[$site]->find_node( $row['atype'] );
      $node->set_value( $row['total'] );
    }

    // create a summary node
    $first_node = NULL;
    if( $db_role->all_sites )
    {
      // create a summary node of all sites
      $first_node = $this->root_node->get_summary_node();
      if( !is_null( $first_node ) )
      {
        $first_node->set_label( 'Summary of All Sites' );
        $this->root_node->add_child( $first_node, true );
      }
    }
  }
}
