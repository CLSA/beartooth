<?php
/**
 * self_menu.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget self menu
 * 
 * @package beartooth\ui
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

    $exclude = array(
      'address',
      'appointment',
      'availability',
      'consent',
      'interviewer',
      'phase',
      'phone',
      'phone_call' );

    if( 'interviewer' == lib::create( 'business\session' )->get_role()->name )
      $exclude[] = 'assignment';

    $this->exclude_widget_list = array_merge( $this->exclude_widget_list, $exclude );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $utilities = $this->get_variable( 'utilities' );
    $session = lib::create( 'business\session' );
    
    // insert the participant tree into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'tree' );
    if( $session->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Tree',
                            'type' => 'widget',
                            'subject' => 'participant',
                            'name' => 'tree' );

    // insert the participant sync operation into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'sync' );
    if( $session->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Sync',
                            'type' => 'widget',
                            'subject' => 'participant',
                            'name' => 'sync' );

    // insert the assignment begin operation into the utilities
    $db_operation = $operation_class_name::get_operation( 'push', 'home_assignment', 'begin' );
    if( $session->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Home Assignment',
                            'type' => 'push',
                            'subject' => 'home_assignment',
                            'name' => 'begin' );

    $db_operation = $operation_class_name::get_operation( 'push', 'site_assignment', 'begin' );
    if( $session->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Site Assignment',
                            'type' => 'push',
                            'subject' => 'site_assignment',
                            'name' => 'begin' );

    $this->set_variable( 'utilities', $utilities );
  }
}
?>
