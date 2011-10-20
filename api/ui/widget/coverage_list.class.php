<?php
/**
 * coverage_list.class.php
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
 * widget coverage list
 * 
 * @package beartooth\ui
 */
class coverage_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the coverage list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'coverage', $args );
    
    $session = bus\session::self();

    $this->add_column( 'username', 'string', 'Interviewer', false );
    $this->add_column( 'postcode_mask', 'string', 'Postal Code', true );
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
      // format the postcode LIKE-based statement
      $postcode_mask = str_replace( '%', '', $record->postcode_mask );
      $length = strlen( $postcode_mask );
      for( $index = $length; $index < 7; $index++ ) $postcode_mask .= 3 == $index ? ' ' : '?';

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'username' => $record->get_access()->get_user()->name,
               'postcode_mask' => $postcode_mask ) );
    }

    $this->finish_setting_rows();
  }
  
  /**
   * Overrides the parent class method since the record count depends on the user's site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'access.site_id', '=', bus\session::self()->get_site()->id );
    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method since the record count depends on the user's site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'access.site_id', '=', bus\session::self()->get_site()->id );
    return parent::determine_record_list( $modifier );
  }
}
?>
