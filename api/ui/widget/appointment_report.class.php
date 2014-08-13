<?php
/**
 * appointment_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget appointment report
 */
class appointment_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->add_restriction( 'qnaire' );
    $this->add_restriction( 'dates' );

    if( 'interviewer' == lib::create( 'business\session' )->get_role()->name )
    {
      $this->add_parameter( 'user_id', 'hidden' );
    }
    else
    {
      $this->add_parameter( 'user_id', 'enum', 'Interviewer',
        'This parameter is only used when selecting home interviews' );
    }

    $this->add_parameter( 'completed', 'boolean', 'Completed',
      'Leaving this blank will include completed and incompleted appointments.' );

    $this->set_variable( 'description',
      'This report provides a list of all appointments for a particular questionnaire '.
      'including the date and time of the appointment, the participant\'s name, unique '.
      'identifier, age and phone number(s).' );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $role_class_name = lib::create( 'database\role' );
    $session = lib::create( 'business\session' );

    if( 'interviewer' == $session->get_role()->name )
    {
      $this->set_parameter( 'user_id', $session->get_user()->id );
    }
    else
    {
      $db_role = $role_class_name::get_unique_record( 'name', 'interviewer' );
      $db_site = $session->get_site();
      $user_mod = lib::create( 'database\modifier' );
      $user_mod->where( 'access.role_id', '=', $db_role->id );
      foreach( $db_site->get_user_list( $user_mod ) as $db_user )
        $user_list[$db_user->id] =
          sprintf( '%s %s (%s)', $db_user->first_name, $db_user->last_name, $db_user->name );

      $this->set_parameter( 'user_id', NULL, false, $user_list );
    }

    $this->set_parameter( 'completed', false, false );
  }
}
