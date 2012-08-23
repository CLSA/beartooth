<?php
/**
 * onyx_instance_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget onyx_instance list
 */
class onyx_instance_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the onyx_instance list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx_instance', $args );
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
    
    $this->add_column( 'user.name', 'string', 'Name', false );
    $this->add_column( 'site.name', 'string', 'Site', true );
    $this->add_column( 'instance', 'string', 'Instance', false );
    $this->add_column( 'active', 'boolean', 'Active', true );
    $this->add_column( 'last_activity', 'fuzzy', 'Last activity', false );
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
    
    foreach( $this->get_record_list() as $record )
    {
      $db_user = $record->get_user();

      $db_interviewer_user = $record->get_interviewer_user();
      $instance = is_null( $db_interviewer_user )
                        ? 'site'
                        : $db_interviewer_user->name;

      // determine the last activity
      $db_activity = $db_user->get_last_activity();
      $last = is_null( $db_activity ) ? null : $db_activity->datetime;

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'user.name' => $db_user->name,
               'site.name' => $record->get_site()->name,
               'instance' => $instance,
               'active' => $db_user->active,
               'last_activity' => $last ) );
    }
  }

  /**
   * Overrides the parent class method to also include onyx instances with no site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    $class_name = lib::get_class_name( 'ui\widget\base_list' );
    return $class_name::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method based on the restrict site member.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_record_list( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    $class_name = lib::get_class_name( 'ui\widget\base_list' );
    return $class_name::determine_record_list( $modifier );
  }
}
?>
