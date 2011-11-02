<?php
/**
 * access_list.class.php
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
 * widget access list
 * 
 * @package beartooth\ui
 */
class access_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the access list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'access', $args );
    
    $this->add_column( 'user.name', 'string', 'User', true );
    $this->add_column( 'role.name', 'string', 'Role', true );
    $this->add_column( 'site.name', 'string', 'Site', true );
  }

  /**
   * Finish setting the variables in the list widget, including filling in the rows.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'user.name' => $record->get_user()->name,
               'role.name' => $record->get_role()->name,
               'site.name' => $record->get_site()->name ) );
    }

    $this->finish_setting_rows();
  }
  
  /**
   * Overrides the parent class method to prevent counting onyx users
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $db_role = db\role::get_unique_record( 'name', 'onyx' );
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'role_id', '!=', $db_role->id );

    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method to prevent onyx users from being included in the lsit
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    $db_role = db\role::get_unique_record( 'name', 'onyx' );
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'role_id', '!=', $db_role->id );

    return parent::determine_record_list( $modifier );
  }
}
?>
