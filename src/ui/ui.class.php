<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\ui;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Application extension to ui class
 */
class ui extends \cenozo\ui\ui
{
  /**
   * Extends the parent method
   */
  protected function build_module_list()
  {
    parent::build_module_list();

    $db_role = lib::create( 'business\session' )->get_role();

    // remove all lists from the interviewer role except for participant
    if( 'interviewer' == $db_role->name )
    {
      $this->set_all_list_menu( false );
      $this->get_module( 'participant' )->set_list_menu( true );
    }

    // add child actions to certain modules

    $module = $this->get_module( 'appointment' );
    if( !is_null( $module ) )
    {
      // add type (home|site) to list and view states, and identifier (site) to list state
      $module->prepend_action_query( 'list', '/{type}/{identifier}' );
      $module->prepend_action_query( 'view', '/{type}' );

      // add site parameter to add and view states
      $module->append_action_query( 'add', '?{site}' );
      $module->append_action_query( 'view', '?{site}' );
    }

    $module = $this->get_module( 'interview' );
    if( !is_null( $module ) )
    {
      $module->prepend_action_query( 'view', '/{type}' );
      $module->add_child( 'appointment', 0 );
    }

    $module = $this->get_module( 'onyx_instance' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'activity' );
      $module->add_child( 'writelog' );
    }

    $module = $this->get_module( 'participant' );
    if( !is_null( $module ) ) $module->append_action_query( 'history', '&{appointment}' );

    $module = $this->get_module( 'qnaire' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'appointment_type' );
      $module->add_choose( 'collection' );
      $module->add_choose( 'hold_type' );
      $module->add_choose( 'script' );
      $module->add_choose( 'stratum' );
    }

    $module = $this->get_module( 'queue' );
    if( !is_null( $module ) )
    {
      $module->set_list_menu( true ); // always show the queue list
      $module->add_choose( 'participant' );
      // add special query parameters to queue-view
      $module->append_action_query( 'view', '?{restrict}&{order}&{reverse}' );
    }

    $module = $this->get_module( 'stratum' );
    if( !is_null( $module ) ) $module->add_choose( 'qnaire' );

    // interviewers do not get access to participant search
    $module = $this->get_module( 'search_result' );
    if( !is_null( $module ) && 'interviewer' == $db_role->name ) $module->remove_all_actions();

    $module = $this->get_module( 'site' );
    if( !is_null( $module ) ) $module->add_child( 'appointment_mail' );
  }

  /**
   * Extends the parent method
   */
  protected function build_listitem_list()
  {
    parent::build_listitem_list();

    $db_role = lib::create( 'business\session' )->get_role();

    if( 'interviewer' == $db_role->name )
    {
      $this->add_listitem( 'My Participants', 'participant' );
      $this->remove_listitem( 'Participants' );
      $this->remove_listitem( 'Interviews' );
    }

    // add application-specific states to the base list
    $this->add_listitem( 'Onyx Instances', 'onyx_instance' );
    $this->add_listitem( 'Questionnaires', 'qnaire' );
    $this->add_listitem( 'Queues', 'queue' );
  }

  /**
   * Extends the parent method
   */
  protected function get_utility_items()
  {
    $list = parent::get_utility_items();
    $db_site = lib::create( 'business\session' )->get_site();
    $db_role = lib::create( 'business\session' )->get_role();

    // interviewers get no list items
    if( 'interviewer' == $db_role->name ) unset( $list['Participant Search'] );

    // add application-specific states to the base list
    if( 2 <= $db_role->tier )
    {
      $query = '?{qnaire}&{language}';
      if( $db_role->all_sites ) $query .= '&{site}';
      $list['Queue Tree'] = array(
        'subject' => 'queue',
        'action' => 'tree',
        'query' => $query );
    }
    if( !$db_role->all_sites && 1 < $db_role->tier )
    {
      $list['Site Details'] = array(
        'subject' => 'site',
        'action' => 'view',
        'query' => '/{identifier}',
        'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
    }
    foreach( array( 'home', 'site' ) as $type )
    {
      if( in_array( $db_role->name, array( 'helpline', 'coordinator', 'interviewer', 'interviewer+' ) ) )
      {
        $list[ucWords( $type ).' Assignment Control'] = array(
          'subject' => 'assignment',
          'action' => $type.'_control',
          'query' => '?{restrict}&{order}&{reverse}'
        );
      }
      if( !$db_role->all_sites || 'helpline' == $db_role->name )
      {
        $list[ucwords( $type ).' Appointment Calendar'] = array(
          'subject' => 'appointment',
          'action' => 'calendar',
          'query' => '/{type}/{identifier}',
          'values' => sprintf( '{type:"%s",identifier:"name=%s"}', $type, $db_site->name ) );
      }
    }

    return $list;
  }
}
