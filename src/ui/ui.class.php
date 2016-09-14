<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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
  protected function get_module_list( $modifier = NULL )
  {
    $module_list = parent::get_module_list( $modifier );
    $db_role = lib::create( 'business\session' )->get_role();

    // remove all lists from the interviewer role
    if( 'interviewer' == $db_role->name )
    {
      foreach( $module_list as $name => &$module )
        if( 'participant' != $name ) $module['list_menu'] = false;
    }

    // add child actions to certain modules
    if( array_key_exists( 'appointment', $module_list ) )
    {
      if( array_key_exists( 'add', $module_list['appointment']['actions'] ) )
        $module_list['appointment']['actions']['add'] = '?{site}';
      if( array_key_exists( 'list', $module_list['appointment']['actions'] ) )
        $module_list['appointment']['actions']['list'] =
          '/{type}/{identifier}'.$module_list['appointment']['actions']['list'];
      if( array_key_exists( 'view', $module_list['appointment']['actions'] ) )
       $module_list['appointment']['actions']['view'] =
         sprintf( '/{type}%s?{site}', $module_list['appointment']['actions']['view'] );
    }
    if( array_key_exists( 'interview', $module_list ) )
    {
      if( array_key_exists( 'view', $module_list['interview']['actions'] ) )
        $module_list['interview']['actions']['view'] = '/{type}'.$module_list['interview']['actions']['view'];
      array_unshift( $module_list['interview']['children'], 'appointment' );
    }
    if( array_key_exists( 'onyx_instance', $module_list ) )
    {
      $module_list['onyx_instance']['children'] = array( 'activity' );
    }
    if( array_key_exists( 'participant', $module_list ) )
    {
      // add extra types to history
      $module_list['participant']['actions']['history'] .= '&{appointment}';
    }
    if( array_key_exists( 'qnaire', $module_list ) )
    {
      $module_list['qnaire']['children'] = array( 'appointment_type' );
      $module_list['qnaire']['choosing'] = array( 'script', 'site', 'quota' );
    }
    if( array_key_exists( 'queue', $module_list ) )
    {
      $module_list['queue']['list_menu'] = true; // always show the queue list
      $module_list['queue']['choosing'] = array( 'participant' );

      // add special query parameters to queue-view
      if( array_key_exists( 'view', $module_list['queue']['actions'] ) )
        $module_list['queue']['actions']['view'] .= '?{restrict}&{order}&{reverse}';
    }
    if( array_key_exists( 'quota', $module_list ) )
    {
      $module_list['quota']['choosing'] = array( 'qnaire' );
    }
    // interviewers do not get access to participant search
    if( array_key_exists( 'search_result', $module_list ) && 'interviewer' == $db_role->name )
    {
      $module_list['search_result']['actions'] = array();
    }
    if( array_key_exists( 'site', $module_list ) )
    {
      $module_list['site']['choosing'] = array( 'qnaire' );
    }

    return $module_list;
  }

  /**
   * Extends the parent method
   */
  protected function get_list_items( $module_list )
  {
    $list = parent::get_list_items( $module_list );
    $db_role = lib::create( 'business\session' )->get_role();

    if( in_array( $db_role->name, array( 'interviewer', 'interviewer+' ) ) )
    {
      $list['My Participants'] = $list['Participants'];
      unset( $list['Participants'] );
    }

    // add application-specific states to the base list
    if( array_key_exists( 'Interviews', $list ) &&
        !in_array( $db_role->name, array( 'interviewer', 'interviewer+' ) ) )
      unset( $list['Interviews'] );
    if( array_key_exists( 'onyx_instance', $module_list ) && $module_list['onyx_instance']['list_menu'] )
      $list['Onyx Instances'] = 'onyx_instance';
    if( array_key_exists( 'qnaire', $module_list ) && $module_list['qnaire']['list_menu'] )
      $list['Questionnaires'] = 'qnaire';
    if( array_key_exists( 'queue', $module_list ) && $module_list['queue']['list_menu'] )
      $list['Queues'] = 'queue';

    return $list;
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
        $list[ucwords( $type ).' Assignment Control'] = array(
          'subject' => 'assignment',
          'action' => 'control',
          'query' => '/{type}?{restrict}&{order}&{reverse}',
          'values' => sprintf( '{type:"%s"}', $type ) );
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
