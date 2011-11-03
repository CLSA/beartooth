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
}
?>
