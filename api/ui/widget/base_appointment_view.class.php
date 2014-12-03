<?php
/**
 * base_appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * base class for appointment view/add classes
 */
abstract class base_appointment_view extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $name, $args )
  {
    parent::__construct( 'appointment', $name, $args );
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

    // create the site calendar widget
    $this->site_appointment_calendar =
      lib::create( 'ui\widget\site_appointment_calendar', $this->arguments );
    $this->site_appointment_calendar->set_parent( $this );
    $this->site_appointment_calendar->set_editable( false );

    // create the home calendar widget
    $this->home_appointment_calendar =
      lib::create( 'ui\widget\home_appointment_calendar', $this->arguments );
    $this->home_appointment_calendar->set_parent( $this );
    $this->home_appointment_calendar->set_editable( false );

    // get and store the participant and interview objects
    $subject = $this->parent->get_subject();
    if( 'appointment' == $subject )
    {
      $this->db_interview = $this->get_record()->get_interview();
      $this->db_participant = $this->db_interview->get_participant();
    }
    else if( 'interview' == $subject )
    {
      $this->db_interview = $this->parent->get_record();
      $this->db_participant = $this->db_interview->get_participant();
    }
    else if( 'participant' == $subject )
    {
      $this->db_participant = $this->parent->get_record();
      $this->db_interview = $this->db_participant->get_effective_interview();
    }
  }

  /**
   * Validate the operation.  If validation fails this method will throw a notice exception.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws excpetion\argument, exception\permission
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure the subject is either participant or interview
    $subject = $this->parent->get_subject();
    if( 'appointment' != $subject &&
        'interview' != $subject &&
        'participant' != $subject )
      throw lib::create( 'exception\runtime',
        'Appointment widget must have a parent with participant or interview as the subject.',
        __METHOD__ );

    // make sure the interview isn't null (can happen if effective interview is null)
    if( is_null( $this->db_interview ) )
      throw lib::create( 'exception\notice',
        'Cannot create appointment since the participant has no interviews to complete.',
        __METHOD__ );
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

    // set up the site calendar if editing is enabled
    if( $this->get_editable() || 'add' == $this->get_name() )
    {
      try
      {
        $this->site_appointment_calendar->process();
        $this->set_variable( 'site_appointment_calendar',
          $this->site_appointment_calendar->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}

      try
      {
        $this->home_appointment_calendar->process();
        $this->set_variable( 'home_appointment_calendar',
          $this->home_appointment_calendar->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }
  }

  /**
   * Site calendar used to help find appointment availability
   * @var site_appointment_calendar $site_appointment_calendar
   * @access protected
   */
  protected $site_appointment_calendar = NULL;

  /**
   * Site calendar used to help find appointment availability
   * @var home_appointment_calendar $home_appointment_calendar
   * @access protected
   */
  protected $home_appointment_calendar = NULL;

  /**
   * The participant that the appointment belongs to
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;

  /**
   * The interview that the appointment belongs to
   * @var database\interview $db_interview
   * @access protected
   */
  protected $db_interview = NULL;
}
