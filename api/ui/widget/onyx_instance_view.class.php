<?php
/**
 * onyx_instance_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget onyx_instance view
 */
class onyx_instance_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'onyx_instance', 'view', $args );
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

    // define all columns defining this record
    $this->add_item( 'username', 'constant', 'Username' );
    $this->add_item( 'site', 'constant', 'Site' );
    $this->add_item( 'interviewer_user_id', 'enum', 'Instance' );
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'last_activity', 'constant', 'Last activity' );

    // create the activity sub-list widget
    $this->activity_list = lib::create( 'ui\widget\activity_list', $this->arguments );
    $this->activity_list->set_parent( $this );
    $this->activity_list->set_heading( 'Onyx instance activity' );
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

    $session = lib::create( 'business\session' );

    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'interviewer' );

    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'site_id', '=', $this->get_record()->site_id );
    $user_mod->where( 'role_id', '=', $db_role->id );
    $interviewers = array( 'NULL' => 'site' );
    $class_name = lib::get_class_name( 'database\user' );
    foreach( $class_name::select( $user_mod ) as $db_user )
      $interviewers[$db_user->id] = $db_user->name;

    // set the view's items
    $this->set_item( 'username', $this->get_record()->get_user()->name, true );
    $this->set_item( 'site', $this->get_record()->get_site()->name );
    $this->set_item(
      'interviewer_user_id', $this->get_record()->interviewer_user_id, true, $interviewers );
    $this->set_item( 'active', $this->get_record()->get_user()->active, true );
    
    $db_activity = $this->get_record()->get_user()->get_last_activity();
    $last = util::get_fuzzy_period_ago( is_null( $db_activity ) ? null : $db_activity->datetime );
    $this->set_item( 'last_activity', $last );

    try
    {
      $this->activity_list->process();
      $this->set_variable( 'activity_list', $this->activity_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * Overrides the activity list widget's list to get records from instance's user
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @activity protected
   */
  public function determine_activity_count( $modifier = NULL )
  {
    return $this->get_record()->get_user()->get_activity_count( $modifier );
  }

  /**
   * Overrides the activity list widget's method to only include this queue's activity.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @activity protected
   */
  public function determine_activity_list( $modifier = NULL )
  {
    return $this->get_record()->get_user()->get_activity_list( $modifier );
  }

  /** 
   * The activity list widget.
   * @var activity_list
   * @access protected
   */
  protected $activity_list = NULL;
}
?>
