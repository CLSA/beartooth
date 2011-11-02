<?php
/**
 * onyx_instance_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget onyx_instance list
 * 
 * @package beartooth\ui
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
    
    $this->add_column( 'user.name', 'string', 'Name', true );
    $this->add_column( 'site.name', 'string', 'Site', true );
    $this->add_column( 'instance', 'string', 'Instance', false );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_interviewer_user = $record->get_interviewer_user();
      $instance = is_null( $db_interviewer_user )
                        ? 'site'
                        : $db_interviewer_user->name;

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'user.name' => $record->get_user()->name,
               'site.name' => $record->get_site()->name,
               'instance' => $instance ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to also include onyx instances with no site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new db\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    return base_list_widget::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method based on the restrict site member.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new db\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    return base_list_widget::determine_record_list( $modifier );
  }
}
?>
