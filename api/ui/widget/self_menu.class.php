<?php
/**
 * self_menu.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget self menu
 */
class self_menu extends \cenozo\ui\widget\self_menu
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
    parent::__construct( $args );
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

    $this->exclude_list( array(
      'appointment',
      'callback',
      'interviewer',
      'phase',
      'phone_call',
      'appointment',
      'survey' ) );

    // remove the assignment list from interviewers
    if( 'interviewer' == lib::create( 'business\session' )->get_role()->name )
      $this->exclude_list( 'assignment' );
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

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $utilities = $this->get_variable( 'utilities' );
    $session = lib::create( 'business\session' );
    
    // insert the participant tree into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'tree' );
    if( $session->is_operation_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Tree',
                            'type' => 'widget',
                            'subject' => 'participant',
                            'name' => 'tree' );

    // insert the assignment begin operation into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'home_assignment', 'select' );
    if( $session->is_operation_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Home Assignment',
                            'type' => 'widget',
                            'subject' => 'home_assignment',
                            'name' => 'select' );

    $db_operation = $operation_class_name::get_operation( 'widget', 'site_assignment', 'select' );
    if( $session->is_operation_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Site Assignment',
                            'type' => 'widget',
                            'subject' => 'site_assignment',
                            'name' => 'select' );
    
    $this->set_variable( 'utilities', $utilities );

    // if this is an interviewer then change the participant heading
    if( 'interviewer' == lib::create( 'business\session' )->get_role()->name )
    {
      $lists = $this->get_variable( 'lists' );
      foreach( $lists as $index => $list )
      {
        if( 0 == strcasecmp( 'participants', $list['heading'] ) )
        {
          $lists[$index]['heading'] = 'My Participants';
          break;
        }
      }
      $this->set_variable( 'lists', $lists );
    }
  }
}
