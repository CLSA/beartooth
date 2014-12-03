<?php
/**
 * interview_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget interview view
 */
class interview_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'interview', 'view', $args );
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

    // create an associative array with everything we want to display about the interview
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'participant', 'constant', 'Participant' );
    $this->add_item( 'qnaire', 'constant', 'Questionnaire' );
    $this->add_item( 'completed', 'constant', 'Completed' );

    // create the assignment sub-list widget      
    $this->assignment_list = lib::create( 'ui\widget\assignment_list', $this->arguments );
    $this->assignment_list->set_parent( $this );
    $this->assignment_list->set_heading( 'Assignments associated with this interview' );
  
    // create the appointment sub-list widget
    $this->appointment_list = lib::create( 'ui\widget\appointment_list', $this->arguments );
    $this->appointment_list->set_parent( $this );
    $this->appointment_list->set_heading( 'Appointments associated with this interview' );
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
       
    $db_participant = $this->get_record()->get_participant();
    $participant = sprintf( '%s, %s', $db_participant->last_name, $db_participant->first_name );

    // set the view's items
    $this->set_item( 'uid', $db_participant->uid );
    $this->set_item( 'participant', $participant );
    $this->set_item( 'qnaire', $this->get_record()->get_qnaire()->name );
    $this->set_item( 'completed', $this->get_record()->completed ? 'Yes' : 'No' );

    // process the child widgets
    try
    {
      $this->assignment_list->process();
      $this->assignment_list->remove_column( 'uid' );
      $this->assignment_list->execute();
      $this->set_variable( 'assignment_list', $this->assignment_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    try 
    {   
      $this->appointment_list->process();
      $this->set_variable( 'appointment_list', $this->appointment_list->get_variables() );
    }   
    catch( \cenozo\exception\permission $e ) {}

    // add an action to view the participant's details
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'view' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) ) 
      $this->add_action(
        'view_participant',
        'View Participant',
        NULL,
        'View the participant\'s details' );
    $this->set_variable( 'participant_id', $db_participant->id );
  }
  
  /**
   * The interview list widget.
   * @var assignment_list
   * @access protected
   */
  protected $assignment_list = NULL;

  /**
   * The appointment list widget.
   * @var appointment_list
   * @access protected
   */
  protected $appointment_list = NULL;
}
