<?php
/**
 * users.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database\limesurvey;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * users: record
 *
 * @package beartooth\database
 */
class users extends record
{
  protected static $primary_key_name = 'uid';
}
?>
