<?php
/**
 * user_list.class.php
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
 * Class for user list pull operations.
 * 
 * @abstract
 * @package beartooth\ui
 */
class user_list extends base_list
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', $args );
  }

  /**
   * The start date/time of the appointment list
   * @var string
   * @access protected
   */
  protected $start_datetime = NULL;
  
  /**
   * The end date/time of the appointment list
   * @var string
   * @access protected
   */
  protected $end_datetime = NULL;
}
?>
