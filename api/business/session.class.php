<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\business
 * @filesource
 */

namespace beartooth\business;
use beartooth\log, beartooth\util;

/**
 * Extends Cenozo's session class with custom functionality
 *
 * @package beartooth\business
 */
class session extends \cenozo\business\session
{
  /**
   * Initializes the session.
   * 
   * This method should be called immediately after initial construct of the session.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\permission
   * @access public
   */
  public function initialize()
  {
    // don't initialize more than once
    if( $this->is_initialized() ) return;

    parent::initialize();

    $setting_manager = util::create( 'business\setting_manager' );

    // create the databases
    $this->survey_database = new db\database(
      $setting_manager->get_setting( 'survey_db', 'driver' ),
      $setting_manager->get_setting( 'survey_db', 'server' ),
      $setting_manager->get_setting( 'survey_db', 'username' ),
      $setting_manager->get_setting( 'survey_db', 'password' ),
      $setting_manager->get_setting( 'survey_db', 'database' ),
      $setting_manager->get_setting( 'survey_db', 'prefix' ) );
    if( $setting_manager->get_setting( 'audit_db', 'enabled' ) )
    {
      // If not set then the audit database settings use the same as limesurvey,
      // with the exception of the prefix
      $this->audit_database = new db\database(
        $setting_manager->get_setting( 'audit_db', 'driver' ),
        $setting_manager->get_setting( 'audit_db', 'server' ),
        $setting_manager->get_setting( 'audit_db', 'username' ),
        $setting_manager->get_setting( 'audit_db', 'password' ),
        $setting_manager->get_setting( 'audit_db', 'database' ),
        $setting_manager->get_setting( 'audit_db', 'prefix' ) );
    }
  }
  
  /**
   * Get the survey database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @access public
   */
  public function get_survey_database()
  {
    return $this->survey_database;
  }

  /**
   * Get the audit database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @access public
   */
  public function get_audit_database()
  {
    return $this->audit_database;
  }

  /**
   * Get the user's current assignment.
   * Should only be called if the user is an interviewer, otherwise an exception will be thrown.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\assignment
   * @throws exception\runtime
   * @access public
   */
  public function get_current_assignment()
  {
    // make sure the user is an interviewer
    if( 'interviewer' != $this->get_role()->name )
      throw util::create( 'exception\runtime', 'Tried to get assignment for non-interviewer.', __METHOD__ );
    
    // query for assignments which do not have a end time
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '=', NULL );
    $assignment_list = $this->get_user()->get_assignment_list( $modifier );

    // only one assignment should ever be open at a time, warn if this isn't the case
    if( 1 < count( $assignment_list ) )
      log::crit(
        sprintf( 'Current interviewer (id: %d, name: %s), has more than one active assignment!',
                 $this->get_user()->id,
                 $this->get_user()->name ) );

    return 1 == count( $assignment_list ) ? current( $assignment_list ) : NULL;
  }

  /**
   * Get the user's current phone call.
   * Should only be called if the user is an interviewer, otherwise an exception will be thrown.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\phone_call
   * @throws exception\runtime
   * @access public
   */
  public function get_current_phone_call()
  {
    // make sure the user is an interviewer
    if( 'interviewer' != $this->get_role()->name )
      throw util::create( 'exception\runtime', 'Tried to get phone call for non-interviewer.', __METHOD__ );
    
    // without an assignment there can be no current call
    $db_assignment = $this->get_current_assignment();
    if( is_null( $db_assignment) ) return NULL;

    // query for phone calls which do not have a end time
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '=', NULL );
    $phone_call_list = $db_assignment->get_phone_call_list( $modifier );

    // only one phone call should ever be open at a time, warn if this isn't the case
    if( 1 < count( $phone_call_list ) )
      log::crit(
        sprintf( 'Current interviewer (id: %d, name: %s), has more than one active phone call!',
                 $this->get_user()->id,
                 $this->get_user()->name ) );

    return 1 == count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
  }

  /**
   * Determines whether the user is allowed to make calls.  This depends on whether a SIP
   * is detected and whether or not interviewers are allowed to make calls without using VoIP
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_allow_call()
  {
    $allow = false;
    if( !setting_manager::self()->get_setting( 'voip', 'enabled' ) )
    { // if voip is not enabled then allow calls
      $allow = true;
    }
    else if( voip_manager::self()->get_sip_enabled() )
    { // voip is enabled, so make sure sip is also enabled
      $allow = true;
    }
    else
    { // check to see if we can call without a SIP connection
      $allow = setting_manager::self()->get_setting( 'voip', 'survey without sip' );
    }

    return $allow;
  }

  /**
   * Extends the parent method to facilitate interview assignments
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $slot The name of the slot.
   * @return array The name and arguments of the widget or NULL if the stack is empty.
   *               The associative array includes:
                   "name" => string,
                   "args" => associative array
   * @access public
   */
  public function slot_current( $slot )
  {
    return 'main' == $slot &&
           'interviewer' == $this->get_role()->name &&
           $this->get_current_assignment()
         ? array( 'name' => 'interviewer_assignment', 'args' => NULL )
         : parent::slot_current( $slot );
  }

  /**
   * Gets the current survey state.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_survey_url()
  {
    // only interviewers can fill out surveys
    if( 'interviewer' != $this->role->name ) return false;
    
    // must have an assignment
    $db_assignment = $this->get_current_assignment();
    if( is_null( $db_assignment ) ) return false;
    
    // the assignment must have an open call
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '=', NULL );
    $call_list = $db_assignment->get_phone_call_list( $modifier );
    if( 0 == count( $call_list ) ) return false;

    // determine the current sid and token
    $sid = $db_assignment->get_current_sid();
    $token = $db_assignment->get_current_token();
    if( false === $sid || false == $token ) return false;
    
    // determine which language to use
    $lang = $db_assignment->get_interview()->get_participant()->language;
    if( !$lang ) $lang = 'en';
    
    return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s', $sid, $lang, $token );
  }

  /**
   * The survey database object.
   * @var database
   * @access private
   */
  private $survey_database = NULL;

  /**
   * The survey database object.
   * @var database
   * @access private
   */
  private $audit_database = NULL;
}
?>
