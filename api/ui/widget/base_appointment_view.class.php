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
}
?>
