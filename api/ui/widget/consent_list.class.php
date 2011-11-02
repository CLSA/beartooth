<?php
/**
 * consent_list.class.php
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
 * widget consent list
 * 
 * @package beartooth\ui
 */
class consent_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the consent list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'consent', $args );
    
    $this->add_column( 'event', 'string', 'Event', true );
    $this->add_column( 'date', 'datetime', 'Date', true );
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
      $this->add_row( $record->id,
        array( 'event' => $record->event,
               'date' => $record->date ) );
    }

    $this->finish_setting_rows();
  }
}
?>
