<?php
/**
 * sample.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget sample report
 */
class sample_report extends base_report
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
    parent::__construct( 'sample', $args );
    $this->use_cache = true;
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

    $this->add_restriction( 'site' );
    $this->add_parameter( 'quota_id', 'enum', 'Quota' );

    $this->set_variable( 'description',
      'This report contains details used to help manage the sample with respect to quotas.' );
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

    $quota_class_name = lib::get_class_name( 'database\quota' );

    // get a list of all possible sites from this application's quotas
    $quota_mod = lib::create( 'database\modifier' );
    $quota_mod->where(
      'site.service_id', '=', lib::create( 'business\session' )->get_service()->id );
    $quota_mod->group( 'site.name' );

    // get a list of all possible age group / gender pairs from this application's quotas
    $quota_mod = lib::create( 'database\modifier' );
    $quota_mod->where(
      'site.service_id', '=', lib::create( 'business\session' )->get_service()->id );
    $quota_mod->group( 'age_group_id' );
    $quota_mod->group( 'gender' );
    $quota_list = array();
    foreach( $quota_class_name::select( $quota_mod ) as $db_quota )
      $quota_list[$db_quota->id] =
        sprintf( '%s, %s',
                 $db_quota->gender,
                 $db_quota->get_age_group()->to_string() );

    $this->set_parameter( 'quota_id', NULL, true, $quota_list );
  }
}
