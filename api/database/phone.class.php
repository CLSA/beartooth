<?php
/**
 * phone.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\exception as exc;

/**
 * phone: record
 *
 * @package beartooth\database
 */
class phone extends has_rank
{
  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'participant';
}
?>
