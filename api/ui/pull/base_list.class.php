<?php
/**
 * base_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\pull;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * Base class for all list pull operations.
 * 
 * @abstract
 * @package beartooth\ui
 */
abstract class base_list extends \beartooth\ui\pull
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'list', $args );
  }

  /**
   * Returns a list of all records in the list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    // TODO: make use of concepts in ui\widget\base_list_widget

    // create a list of records
    $modifier = new db\modifier();
    $class_name = '\\bearetooth\\database\\'.$this->get_subject();
    $list = array();
    foreach( $class_name::select( $modifier ) as $record )
    {
      $item = array();
      foreach( $record->get_column_names() as $column ) $item[$column] = $record->$column;
      $list[] = $item;
    }

    return $list;
  }
  
  /**
   * Lists are always returned in JSON format.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type() { return "json"; }
}
?>
