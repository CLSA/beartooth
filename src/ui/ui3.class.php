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
class ui3 extends \cenozo\ui\ui3
{
  /**
   * Extends the parent method
   */
  protected function generate_modules()
  {
    parent::generate_modules();

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    $module = $this->get_module( 'appointment' );
    if( !is_null( $module ) ) $module->add_action( 'calendar', '/{identifier}?{qnaire_type}&{calendar}' );

    $module = $this->get_module( 'assignment' );
    if( !is_null( $module ) )
    {
      if( in_array( $db_role->name, [ 'helpline', 'interviewer', 'interviewer+', 'coordinator' ] ) )
        $module->add_action( 'control', '?{qnaire_type}{tables}' );
    }

    $module = $this->get_module( 'interview' );
    if( !is_null( $module ) ) $module->add_child( 'appointment' );

    // add the study parameter (used by the progress review only)
    $module = $this->get_module( 'overview' );
    if( !is_null( $module ) ) $module->append_action_query( 'view', '?{study_id}' );

    $module = $this->get_module( 'participant' );
    if( !is_null( $module ) ) $module->append_action_query( 'history', '&{appointment}' );

    $module = $this->get_module( 'qnaire' );
    if( !is_null( $module ) )
    {
      $module->add_choose( 'collection' );
      $module->add_choose( 'hold_type' );
      $module->add_choose( 'site' );
      $module->add_choose( 'stratum' );
      $module->add_choose( 'alternate_type' );
      $module->add_action( 'mass_method', '/{identifier}' );
    }

    $module = $this->get_module( 'queue' );
    if( !is_null( $module ) )
    {
      $module->set_list_menu( true ); // always show the queue list
      $module->add_choose( 'participant' );

      // add special query parameters to queue-view
      $module->append_action_query( 'view', '?{tables}' );

      $query = '?{qnaire_id}&{language_id}';
      if( $db_role->all_sites ) $query .= '&{site_id}';
      $module->add_action( 'tree', $query );
    }

    $module = $this->get_module( 'stratum' );
    if( !is_null( $module ) ) $module->add_choose( 'qnaire' );

    $module = $this->get_module( 'site' );
    if( !is_null( $module ) )
    {
      $module->add_child( 'appointment_mail' );
      $module->add_choose( 'qnaire' );
    }

    // remove the hold_type list from the interviewer+ role
    if( 'interviewer+' == $db_role->name )
    {
      $module = $this->get_module( 'hold_type' );
      if( !is_null( $module ) ) $module->set_list_menu( false );

      // remove the trace_type list from the interviewer+ role
      $module = $this->get_module( 'trace_type' );
      if( !is_null( $module ) ) $module->set_list_menu( false );

      // remove the proxy_type list from the interviewer+ role
      $module = $this->get_module( 'proxy_type' );
      if( !is_null( $module ) ) $module->set_list_menu( false );
    }

    $module = $this->get_module( 'user' );
    if( !is_null( $module ) )
    {
      // remove the user list from the interviewer+ role
      if( 'interviewer+' == $db_role->name ) $module->set_list_menu( false );
    }
  }

  /**
   * Extends the parent method
   */
  protected function generate_menus()
  {
    parent::generate_menus();

    $session = lib::create( 'business\session' );
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    if( 'interviewer' == $db_role->name ) $this->remove_all_menu_items( 'list' );

    $this->add_menu_item( 'list', 'Questionnaires', 'qnaire' );
    $this->add_menu_item( 'list', 'Queues', 'queue' );
    if( 'interviewer' == $db_role->name ) $this->remove_menu_item( 'utility', 'Participant Search' );
    if( in_array( $db_role->name, [ 'helpline', 'interviewer', 'interviewer+', 'coordinator' ] ) )
    {
      $this->add_menu_item( 'utility', 'Home Assignment Control', 'assignment', 'control', '?qnaire_type=home' );
      $this->add_menu_item( 'utility', 'Site Assignment Control', 'assignment', 'control', '?qnaire_type=site' );
    }
    if( 2 <= $db_role->tier ) $this->add_menu_item( 'utility', 'Queue Tree', 'queue', 'tree' );
    if( !$db_role->all_sites && 1 < $db_role->tier )
      $this->add_menu_item( 'utility', 'Site Details', 'site', 'view', sprintf( '/%d', $db_site->id ) );

    if( !$db_role->all_sites || 'helpline' == $db_role->name )
    {
      $this->add_menu_item(
        'utility',
        'Home Appointment Calendar',
        'appointment',
        'calendar',
        sprintf( '/site_id=%d?qnaire_type=home', $db_site->id )
      );
      $this->add_menu_item(
        'utility',
        'Site Appointment Calendar',
        'appointment',
        'calendar',
        sprintf( '/site_id=%d?qnaire_type=site', $db_site->id )
      );
    }
  }
}
